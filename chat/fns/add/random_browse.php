<?php
// Random Browse & Match API (separate from existing chat)
// Table: gr_random_ticket (prefixed) extended to support browsing & pairing
// Columns used:
//  id (PK), owner_user_id (BIGINT), peer_user_id (BIGINT NULL),
//  status ENUM('waiting','paired') DEFAULT 'waiting',
//  preview_channel VARCHAR(128) NULL, call_channel VARCHAR(128) NULL,
//  created_on DATETIME DEFAULT CURRENT_TIMESTAMP
//
// Actions:
//  - enter: ensure a waiting ticket for me; set/generate preview_channel; return token to publish preview
//  - list: list other waiting users with their preview channels
//  - watch_token: get token to subscribe to a candidate's preview channel
//  - match: atomically pair with a candidate and set call_channel; return call token for caller
//  - status: poll; when paired return call token; when waiting return waiting
//  - leave: set me back to waiting (clear peer and call)

$__rb_now = gmdate('Y-m-d H:i:s');
function rb_with_time($arr){
    global $__rb_now; return $arr + ['server_time'=>$__rb_now,'server_time_unix'=>strtotime($__rb_now)];
}
$result = rb_with_time(['success'=>false,'error_key'=>'something_went_wrong','error_message'=>Registry::load('strings')->went_wrong ?? 'Something went wrong']);
if (!Registry::load('current_user')->logged_in) { return; }

$me = (int)Registry::load('current_user')->id;
$db = DB::connect();

// Resolve prefixed table name
$prefix = '';
try { $prefix = Registry::load('config')->database['prefix'] ?? ''; } catch (Exception $e) {}
$table = $prefix.'random_ticket';

// Ensure base table exists
$create_sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` BIGINT UNSIGNED NOT NULL,
  `peer_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('waiting','paired') NOT NULL DEFAULT 'waiting',
  `preview_channel` VARCHAR(128) DEFAULT NULL,
  `call_channel` VARCHAR(128) DEFAULT NULL,
  `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_owner` (`owner_user_id`),
  KEY `idx_status` (`status`,`created_on`),
  KEY `idx_peer` (`peer_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
try { $db->query($create_sql); } catch (Exception $e) { $result=['success'=>false,'error_key'=>'table_create_failed','error_message'=>$e->getMessage()]; return; }

// Best-effort: add columns if this table already existed with the simpler schema
function rb_add_column_if_missing($db, $table, $column, $definition) {
    try {
        $stmt = $db->pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '".$column."'");
        $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$col) { $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}"); }
    } catch (Exception $e) { /* ignore */ }
}
rb_add_column_if_missing($db, $table, 'status', "ENUM('waiting','paired') NOT NULL DEFAULT 'waiting'");
rb_add_column_if_missing($db, $table, 'preview_channel', "VARCHAR(128) DEFAULT NULL");
rb_add_column_if_missing($db, $table, 'call_channel', "VARCHAR(128) DEFAULT NULL");
rb_add_column_if_missing($db, $table, 'updated_on', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

// If legacy schema had a single 'channel' column or 'filled' flag keep compatibility by adding missing modern columns (already done above)
// Now ensure we do NOT have duplicate rows (older installs without unique index may have them)
try {
    // Remove duplicate owner_user_id rows keeping the smallest id
    $db->pdo->exec("DELETE t1 FROM `{$table}` t1 INNER JOIN `{$table}` t2 ON t1.owner_user_id=t2.owner_user_id AND t1.id>t2.id");
} catch (Exception $e) { /* ignore */ }

// Ensure unique index exists (some early versions might lack it)
try {
    $idx = $db->pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name='uq_owner'");
    $exists = $idx && $idx->fetch(PDO::FETCH_ASSOC);
    if (!$exists) {
        // Try to add unique index; if duplicates still exist this will error and we attempt another cleanup
        try { $db->pdo->exec("ALTER TABLE `{$table}` ADD UNIQUE KEY `uq_owner` (`owner_user_id`)"); }
        catch (Exception $e) {
            if (strpos($e->getMessage(),'Duplicate')!==false) {
                // Run another aggressive duplicate cleanup then retry once
                try { $db->pdo->exec("DELETE t1 FROM `{$table}` t1 INNER JOIN `{$table}` t2 ON t1.owner_user_id=t2.owner_user_id AND t1.id>t2.id"); } catch(Exception $e2) {}
                try { $db->pdo->exec("ALTER TABLE `{$table}` ADD UNIQUE KEY `uq_owner` (`owner_user_id`)"); } catch(Exception $e3) { /* give up silently */ }
            }
        }
    }
} catch (Exception $e) { /* ignore */ }

// Cleanup pass 1: remove stale waiting tickets older than 15 minutes (safety net)
try {
    $db->pdo->exec("DELETE FROM `{$table}` WHERE `status`='waiting' AND ((`updated_on` IS NOT NULL AND `updated_on` < (NOW() - INTERVAL 15 MINUTE)) OR (`updated_on` IS NULL AND `created_on` < (NOW() - INTERVAL 15 MINUTE)))");
} catch (Exception $e) { /* ignore */ }
// Cleanup pass 2 (aggressive): remove long-disconnected previews (>2 minutes idle)
try {
    $db->pdo->exec("DELETE FROM `{$table}` WHERE `status`='waiting' AND `updated_on` < (NOW() - INTERVAL 2 MINUTE)");
} catch (Exception $e) { /* ignore */ }
// Cleanup pass 3: paired rows where counterpart is gone for >5 minutes revert to waiting
try {
    // If I'm paired but my peer has no row or is not paired with me and my updated_on is stale => reset
    $db->pdo->exec("UPDATE `{$table}` t SET t.status='waiting', t.peer_user_id=NULL, t.call_channel=NULL
        WHERE t.status='paired' AND t.updated_on < (NOW() - INTERVAL 5 MINUTE) AND NOT EXISTS (
            SELECT 1 FROM `{$table}` p WHERE p.owner_user_id=t.peer_user_id AND p.peer_user_id=t.owner_user_id AND p.status='paired'
        )");
} catch (Exception $e) { /* ignore */ }

function rb_token_bundle($channel_name, $viewer_mode=false) {
    include_once('fns/video_chat/load.php');
    return video_chat_module(['generate_token' => [
        'channel_name' => $channel_name,
        'channel_admin' => false,
        'one_to_one' => true,
        'viewer_mode' => $viewer_mode
    ]]);
}

// Read action and optional params
$action = '';
if (isset($data['action']) && !empty($data['action'])) { $action = preg_replace('/[^a-z_]+/i','',$data['action']); }
else if (isset($_POST['action']) && !empty($_POST['action'])) { $action = preg_replace('/[^a-z_]+/i','',$_POST['action']); }

$candidate_id = 0;
if (isset($data['user_id'])) { $candidate_id = (int)$data['user_id']; }
else if (isset($_POST['user_id'])) { $candidate_id = (int)$_POST['user_id']; }

// enter: ensure waiting ticket and return preview publish token
if ($action === 'enter') {
    try {
        $preview = 'rv_prev_'.$me; // deterministic per-user channel
        // Atomic upsert using ON DUPLICATE KEY to avoid race duplicates
        $stmt = $db->pdo->prepare("INSERT INTO `{$table}` (owner_user_id,status,peer_user_id,preview_channel,call_channel) VALUES (:me,'waiting',NULL,:preview,NULL) 
            ON DUPLICATE KEY UPDATE status='waiting', peer_user_id=NULL, call_channel=NULL, preview_channel=VALUES(preview_channel)");
        $stmt->execute([':me'=>$me, ':preview'=>$preview]);
        $bundle = rb_token_bundle($preview, false);
        if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
            $result = rb_with_time(['success'=>true,'mode'=>'waiting','preview'=>$bundle,'me_user_id'=>$me]); return;
        }
        $result=rb_with_time(['success'=>false,'error_key'=>'token_failed','error_message'=>'Could not create preview token']); return;
    } catch (Exception $e) { $result=rb_with_time(['success'=>false,'error_key'=>'enter_failed','error_message'=>$e->getMessage()]); return; }
}

// list: list other waiting users and their preview channels
if ($action === 'list') {
    try {
        // Touch my ticket as activity heartbeat (without changing status if paired)
        try { $db->update('random_ticket', ['updated_on'=>date('Y-m-d H:i:s')], ['owner_user_id'=>$me]); } catch (Exception $e) {}
        $rows = $db->select('random_ticket', ['owner_user_id','preview_channel','updated_on'], [
            'status'=>'waiting', 'owner_user_id[!]'=>$me, 'ORDER'=>['id'=>'ASC'], 'LIMIT'=>50
        ]);
        $result = rb_with_time(['success'=>true,'candidates'=>$rows ?: []]); return;
    } catch (Exception $e) { $result=rb_with_time(['success'=>false,'error_key'=>'list_failed','error_message'=>$e->getMessage()]); return; }
}

// watch_token: token to view candidate's preview (no publish)
if ($action === 'watch_token') {
    try {
        if ($candidate_id <= 0) { $result=['success'=>false,'error_key'=>'invalid_user','error_message'=>'Invalid user']; return; }
        $row = $db->get('random_ticket', ['owner_user_id','status','preview_channel'], ['owner_user_id'=>$candidate_id]);
    if (!$row || $row['status']!=='waiting' || empty($row['preview_channel'])) { $result=rb_with_time(['success'=>false,'error_key'=>'not_waiting','error_message'=>'Candidate not available']); return; }
        $bundle = rb_token_bundle($row['preview_channel'], true);
        if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
            $result = rb_with_time(['success'=>true,'preview'=>$bundle]); return;
        }
        $result=rb_with_time(['success'=>false,'error_key'=>'token_failed','error_message'=>'Could not create watch token']); return;
    } catch (Exception $e) { $result=rb_with_time(['success'=>false,'error_key'=>'watch_failed','error_message'=>$e->getMessage()]); return; }
}

// match: pair with a candidate and set call channel; return call token for caller
if ($action === 'match') {
    try {
        if ($candidate_id <= 0) { $result=['success'=>false,'error_key'=>'invalid_user','error_message'=>'Invalid user']; return; }
        $call = 'rv_call_'.$me.'_'.$candidate_id.'_'.time();
        // Claim candidate: set to paired if still waiting
        $stmt = $db->pdo->prepare("UPDATE `{$table}` SET status='paired', peer_user_id=:me, call_channel=:call WHERE owner_user_id=:cand AND status='waiting'");
        $stmt->execute([':me'=>$me, ':call'=>$call, ':cand'=>$candidate_id]);
    if ($stmt->rowCount()!==1) { $result=rb_with_time(['success'=>false,'error_key'=>'claim_failed','error_message'=>'Candidate already matched']); return; }
        // Upsert my row to paired
        $mine = $db->get('random_ticket', ['id'], ['owner_user_id'=>$me]);
        if ($mine) {
            $db->update('random_ticket', ['status'=>'paired','peer_user_id'=>$candidate_id,'call_channel'=>$call], ['owner_user_id'=>$me]);
        } else {
            $db->insert('random_ticket', ['owner_user_id'=>$me,'status'=>'paired','peer_user_id'=>$candidate_id,'call_channel'=>$call,'preview_channel'=>'rv_prev_'.$me]);
        }
        $bundle = rb_token_bundle($call, false);
        if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
            $bundle['matched']=true; $bundle['peer_id']=$candidate_id; $bundle['success']=true; $result=rb_with_time($bundle); return;
        }
        $result=rb_with_time(['success'=>false,'error_key'=>'call_token_failed','error_message'=>'Could not create call token']); return;
    } catch (Exception $e) { $result=rb_with_time(['success'=>false,'error_key'=>'match_failed','error_message'=>$e->getMessage()]); return; }
}

// status: check my state, return call token if paired
if ($action === 'status') {
    try {
        // Touch my ticket so active users are not cleaned up
        try { $db->update('random_ticket', ['updated_on'=>date('Y-m-d H:i:s')], ['owner_user_id'=>$me]); } catch (Exception $e) {}
        $row = $db->get('random_ticket', ['status','peer_user_id','call_channel'], ['owner_user_id'=>$me]);
        if ($row && $row['status']==='paired' && !empty($row['call_channel'])) {
            $bundle = rb_token_bundle($row['call_channel'], false);
            if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
                $bundle['matched']=true; $bundle['peer_id']=(int)$row['peer_user_id']; $bundle['success']=true; $result=rb_with_time($bundle); return;
            }
        }
        $result=rb_with_time(['success'=>true,'matched'=>false,'mode'=>'waiting']); return;
    } catch (Exception $e) { $result=rb_with_time(['success'=>false,'error_key'=>'status_failed','error_message'=>$e->getMessage()]); return; }
}

// leave: delete my ticket (lighter footprint) so re-enter is a fresh row
if ($action === 'leave') {
    try {
        $db->delete('random_ticket', ['owner_user_id'=>$me]);
        $result=rb_with_time(['success'=>true,'left'=>true,'deleted'=>true]); return;
    } catch (Exception $e) { $result=rb_with_time(['success'=>false,'error_key'=>'leave_failed','error_message'=>$e->getMessage()]); return; }
}

// status_fix: client indicates call ended unexpectedly; force revert to waiting
if ($action === 'status_fix') {
    try {
        $db->update('random_ticket', ['status'=>'waiting','peer_user_id'=>null,'call_channel'=>null], ['owner_user_id'=>$me]);
        $result=rb_with_time(['success'=>true,'fixed'=>true]); return;
    } catch (Exception $e) { $result=rb_with_time(['success'=>false,'error_key'=>'status_fix_failed','error_message'=>$e->getMessage()]); return; }
}

return;
?>
