<?php
// Simple Random Video Chat - Ticket queue (robust alternative)
// Table: random_ticket (id, channel, owner_user_id, peer_user_id, filled, created_on)
// Actions: find, status, next, leave

$result = ['success'=>false, 'error_key'=>'something_went_wrong', 'error_message'=>Registry::load('strings')->went_wrong ?? 'Something went wrong'];

if (!Registry::load('current_user')->logged_in) { return; }

$me = (int)Registry::load('current_user')->id;
$db = DB::connect();
error_log('[random_simple] request start user='.$me);

// Resolve real table name with DB prefix
$db_prefix = '';
try { $db_prefix = Registry::load('config')->database['prefix'] ?? ''; } catch (Exception $e) { $db_prefix = ''; }
$table_random_ticket = $db_prefix.'random_ticket';

// Create table if missing (using prefixed table name)
$create_sql = "CREATE TABLE IF NOT EXISTS `{$table_random_ticket}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel` VARCHAR(128) NOT NULL,
  `owner_user_id` BIGINT UNSIGNED NOT NULL,
  `peer_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `filled` TINYINT(1) NOT NULL DEFAULT 0,
  `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_filled` (`filled`,`created_on`),
  KEY `idx_owner` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
try { $db->query($create_sql); error_log('[random_simple] ensured table '.$table_random_ticket); } catch (Exception $e) { error_log('[random_simple] table create failed: '.$e->getMessage()); $result=['success'=>false,'error_key'=>'table_create_failed','error_message'=>$e->getMessage()]; return; }

function rs_token_bundle($channel_name) {
    include_once('fns/video_chat/load.php');
    return video_chat_module(['generate_token' => [
        'channel_name' => $channel_name,
        'channel_admin' => false,
        'one_to_one' => true,
        'viewer_mode' => false
    ]]);
}

$action = '';
if (isset($data['action']) && !empty($data['action'])) { $action = preg_replace('/[^a-z_]+/i','',$data['action']); }
else if (isset($_POST['action']) && !empty($_POST['action'])) { $action = preg_replace('/[^a-z_]+/i','',$_POST['action']); }

error_log('[random_simple] action='.$action);

if ($action === 'leave') {
    try {
        // Delete any open ticket owned by me
        $deleted = $db->delete('random_ticket', ['owner_user_id'=>$me, 'filled'=>0]);
        error_log('[random_simple] leave deleted='.json_encode($deleted));
        $result = ['success'=>true,'left'=>true]; return;
    } catch (Exception $e) {
        error_log('[random_simple] leave failed: '.$e->getMessage());
        $result = ['success'=>false,'error_key'=>'leave_failed','error_message'=>$e->getMessage()]; return;
    }
}

if ($action === 'next') {
    try {
        $db->delete('random_ticket', ['owner_user_id'=>$me, 'filled'=>0]);
    } catch (Exception $e) {}
    $action = 'find';
}

if ($action === 'status') {
    try {
        $row = $db->get('random_ticket', ['id','channel','peer_user_id','filled'], ['owner_user_id'=>$me, 'ORDER'=>['id'=>'DESC']]);
        error_log('[random_simple] status row='.json_encode($row));
        if ($row && (int)$row['filled']===1 && !empty($row['channel'])) {
            $bundle = rs_token_bundle($row['channel']);
            error_log('[random_simple] status bundle='.json_encode($bundle));
            if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
                $bundle['matched'] = true; $bundle['peer_id'] = (int)$row['peer_user_id']; $bundle['success']=true; $result=$bundle; return;
            }
        }
        $result = ['success'=>true,'matched'=>false]; return;
    } catch (Exception $e) { error_log('[random_simple] status failed: '.$e->getMessage()); $result=['success'=>false,'error_key'=>'status_failed','error_message'=>$e->getMessage()]; return; }
}

if ($action === 'find') {
    try {
        // Try to claim an existing open ticket not owned by me
        $cand = $db->get('random_ticket', ['id','channel','owner_user_id','filled'], [
            'filled'=>0,
            'owner_user_id[!]'=>$me,
            'ORDER'=>['id'=>'ASC']
        ]);
        error_log('[random_simple] find candidate='.json_encode($cand));
        if ($cand && isset($cand['id'])) {
            // Use prefixed table name for raw SQL update
            $stmt = $db->pdo->prepare("UPDATE `{$table_random_ticket}` SET filled=1, peer_user_id=:me WHERE id=:id AND filled=0");
            $stmt->execute([':me'=>$me, ':id'=>$cand['id']]);
            error_log('[random_simple] claim rowCount='.$stmt->rowCount());
            if ($stmt->rowCount()===1) {
                $bundle = rs_token_bundle($cand['channel']);
                error_log('[random_simple] find bundle='.json_encode($bundle));
                if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) { $bundle['matched']=true; $bundle['peer_id']=(int)$cand['owner_user_id']; $bundle['success']=true; $result=$bundle; return; }
            }
        }

        // Ensure I have (or create) my own open ticket
        $own = $db->get('random_ticket', ['id','channel','filled'], ['owner_user_id'=>$me, 'filled'=>0, 'ORDER'=>['id'=>'DESC']]);
        if (!$own) {
            $channel = 'random_'.time().'_'.mt_rand(1000,9999);
            $db->insert('random_ticket', ['channel'=>$channel,'owner_user_id'=>$me,'filled'=>0]);
            error_log('[random_simple] created own ticket channel='.$channel.' id='.$db->id());
            $own = ['id'=>$db->id(),'channel'=>$channel,'filled'=>0];
        } else { error_log('[random_simple] own open ticket exists id='.$own['id'].' channel='.$own['channel']); }
        $result = ['success'=>true,'matched'=>false,'channel'=>$own['channel']]; return;
    } catch (Exception $e) { error_log('[random_simple] find failed: '.$e->getMessage()); $result=['success'=>false,'error_key'=>'find_failed','error_message'=>$e->getMessage()]; return; }
}

return;
?>
