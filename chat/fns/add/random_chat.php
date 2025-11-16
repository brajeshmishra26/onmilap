<?php
// Random Video Chat - DB-backed queue
// Table: random_queue (id, user_id, status, connected_user, channel, created_on, updated_on)
// Actions: join, status, next, leave

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong ?? 'Something went wrong';
$result['error_key'] = 'something_went_wrong';

if (!Registry::load('current_user')->logged_in) {
    return;
}

$user_id = (int)Registry::load('current_user')->id;
$time_now = Registry::load('current_user')->time_stamp ?? date('Y-m-d H:i:s');
$db = DB::connect();

// Create table if missing
$create_sql = "CREATE TABLE IF NOT EXISTS `random_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('waiting','paired') NOT NULL DEFAULT 'waiting',
  `connected_user` BIGINT UNSIGNED DEFAULT NULL,
  `channel` VARCHAR(128) DEFAULT NULL,
  `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_connected_user` (`connected_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try { $db->query($create_sql); } catch (Exception $e) { $result = ['success'=>false,'error_key'=>'table_create_failed','error_message'=>$e->getMessage()]; return; }

function rc_token_bundle($channel_name) {
    include_once('fns/video_chat/load.php');
    return video_chat_module(['generate_token' => [
        'channel_name' => $channel_name,
        'channel_admin' => false,
        'one_to_one' => true,
        'viewer_mode' => false
    ]]);
}

$action = '';
if (isset($data['action']) && !empty($data['action'])) {
    $action = preg_replace('/[^a-z_]+/i', '', $data['action']);
} else if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = preg_replace('/[^a-z_]+/i', '', $_POST['action']);
}
error_log("RandomChat: action='$action', data=" . json_encode($data));

// List waiting candidates (basic list of user_ids); excludes current user
if ($action === 'list') {
    try {
        // Debug: Log current user and query
        error_log("RandomChat list: current_user_id=$user_id");
        
        // First, let's see ALL rows in the table
        $all_rows = $db->select('random_queue', ['user_id', 'status', 'created_on'], [
            'ORDER' => ['created_on' => 'ASC']
        ]);
        error_log("RandomChat list: all_rows_in_table=" . json_encode($all_rows));
        
        // Then check waiting rows specifically
        $waiting_rows = $db->select('random_queue', ['user_id', 'status'], [
            'status' => 'waiting',
            'ORDER' => ['created_on' => 'ASC']
        ]);
        error_log("RandomChat list: all_waiting_rows=" . json_encode($waiting_rows));
        
        // Some DB layers return flat array for single-column select; others return array of assoc rows
        $rows = $db->select('random_queue', ['user_id'], [
            'status' => 'waiting',
            'user_id[!]' => $user_id,
            'ORDER' => ['created_on' => 'ASC'],
            'LIMIT' => 50
        ]);
        
        // Debug: Log raw query result
        error_log("RandomChat list: raw_rows=" . json_encode($rows));
        
        $ids = [];
        if (is_array($rows)) {
            if (!empty($rows) && is_array($rows[0]) && isset($rows[0]['user_id'])) {
                foreach ($rows as $r) { $ids[] = (int)$r['user_id']; }
            } else {
                foreach ($rows as $v) { $ids[] = (int)$v; }
            }
        }
        
        // Debug: Log final candidate list
        error_log("RandomChat list: final_ids=" . json_encode($ids));
        
        $result = ['success'=>true, 'candidates'=>$ids];
        return;
    } catch (Exception $e) {
        error_log("RandomChat list error: " . $e->getMessage());
        $result = ['success'=>false, 'error_key'=>'list_failed', 'error_message'=>$e->getMessage()];
        return;
    }
}

// Attempt to match with a specific peer id
if ($action === 'match_with') {
    $peer_id = 0;
    if (isset($data['peer_id'])) { 
        $peer_id = (int)$data['peer_id']; 
    } else if (isset($_POST['peer_id'])) {
        $peer_id = (int)$_POST['peer_id'];
    }
    error_log("RandomChat match_with: peer_id=$peer_id");
    if ($peer_id <= 0 || $peer_id === $user_id) {
        $result = ['success'=>false, 'error_key'=>'invalid_peer', 'error_message'=>'Invalid peer'];
        return;
    }
    try {
        // Ensure both rows exist as waiting
        $stmt = $db->pdo->prepare("INSERT INTO random_queue (user_id,status,connected_user,channel,created_on,updated_on) VALUES (:uid,'waiting',NULL,NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE status='waiting', connected_user=NULL, channel=NULL, updated_on=NOW()");
        $stmt->execute([':uid'=>$user_id]);

        $channel_name = 'random_'.time().'_'.mt_rand(1000,9999);
        $sql = "UPDATE random_queue r
                JOIN random_queue p ON p.user_id=? AND p.status='waiting'
                SET r.status='paired',
                    r.connected_user = CASE WHEN r.user_id=? THEN ? ELSE ? END,
                    r.channel = ?
                WHERE r.user_id IN (?, ?) AND r.status='waiting'";
        $upd = $db->pdo->prepare($sql);
        $upd->execute([$peer_id, $user_id, $peer_id, $user_id, $channel_name, $user_id, $peer_id]);

        if ($upd->rowCount() >= 2) {
            $bundle = rc_token_bundle($channel_name);
            if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
                $bundle['matched'] = true;
                $bundle['peer_id'] = $peer_id;
                $bundle['success'] = true; $result = $bundle; return;
            }
        }
        $result = ['success'=>true, 'matched'=>false];
        return;
    } catch (Exception $e) {
        $result = ['success'=>false,'error_key'=>'match_failed','error_message'=>$e->getMessage()];
        return;
    }
}

if ($action === 'leave') {
    try {
        // Remove or reset the user's row
        $db->delete('random_queue', ['user_id' => $user_id]);
        $result = ['success'=>true,'left'=>true];
        return;
    } catch (Exception $e) {
        $result = ['success'=>false,'error_key'=>'leave_failed','error_message'=>$e->getMessage()];
        return;
    }
}

if ($action === 'next') {
    // Reset current user's row to waiting
    try {
        // If paired, also gently free the peer if pointing to us
        $row = $db->get('random_queue', ['status','connected_user'], ['user_id'=>$user_id]);
        if ($row && $row['status']==='paired' && !empty($row['connected_user'])) {
            $db->update('random_queue', ['status'=>'waiting','connected_user'=>null,'channel'=>null], ['user_id'=>$row['connected_user'],'connected_user'=>$user_id]);
        }
        // Upsert user as waiting
    // Upsert current user as waiting (use prepared statement for compatibility)
    $stmt = $db->pdo->prepare("INSERT INTO random_queue (user_id,status,connected_user,channel,created_on,updated_on) VALUES (:uid,'waiting',NULL,NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE status='waiting', connected_user=NULL, channel=NULL, updated_on=NOW()");
    $stmt->execute([':uid'=>$user_id]);
    } catch (Exception $e) {
        $result = ['success'=>false,'error_key'=>'next_failed','error_message'=>$e->getMessage()];
        return;
    }
    // Continue into join logic
    $action = 'join';
}

if ($action === 'status') {
    try {
        $row = $db->get('random_queue', ['status','connected_user','channel'], ['user_id'=>$user_id]);
        if ($row && $row['status']==='paired' && !empty($row['channel'])) {
            $bundle = rc_token_bundle($row['channel']);
            if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
                $bundle['matched'] = true;
                $bundle['peer_id'] = (int)$row['connected_user'];
                $bundle['success'] = true;
                $result = $bundle; return;
            }
        }
        $result = ['success'=>true,'matched'=>false]; return;
    } catch (Exception $e) {
        $result = ['success'=>false,'error_key'=>'status_failed','error_message'=>$e->getMessage()]; return;
    }
}

if ($action === 'join') {
    error_log("RandomChat join: starting for user_id=$user_id");
    try {
        // If already paired, return token
        $row = $db->get('random_queue', ['status','connected_user','channel'], ['user_id'=>$user_id]);
        error_log("RandomChat join: existing_row=" . json_encode($row));
        if ($row && $row['status']==='paired' && !empty($row['channel'])) {
            $bundle = rc_token_bundle($row['channel']);
            if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
                $bundle['matched'] = true;
                $bundle['peer_id'] = (int)$row['connected_user'];
                $bundle['success'] = true; $result = $bundle; return;
            }
        }

        // Ensure current user is waiting
    // Ensure current user is waiting (prepared)
    error_log("RandomChat join: inserting/updating user $user_id as waiting");
    $stmt = $db->pdo->prepare("INSERT INTO random_queue (user_id,status,connected_user,channel,created_on,updated_on) VALUES (:uid,'waiting',NULL,NULL,NOW(),NOW()) ON DUPLICATE KEY UPDATE status='waiting', connected_user=NULL, channel=NULL, updated_on=NOW()");
    $stmt->execute([':uid'=>$user_id]);
    error_log("RandomChat join: upsert affected " . $stmt->rowCount() . " rows");

        // Find a candidate
        $candidate = $db->get('random_queue', ['id','user_id'], [
            'status' => 'waiting',
            'user_id[!]' => $user_id,
            'ORDER' => ['id' => 'ASC']
        ]);

        if (!empty($candidate) && isset($candidate['id'])) {
            $peer_id = (int)$candidate['user_id'];
            $channel_name = 'random_'.time().'_'.mt_rand(1000,9999);

            // Atomically set BOTH rows to paired only if both are currently waiting
            // This multi-table UPDATE will only proceed if the peer row is waiting
            $sql = "UPDATE random_queue r
                    JOIN random_queue p ON p.user_id=? AND p.status='waiting'
                    SET r.status='paired',
                        r.connected_user = CASE WHEN r.user_id=? THEN ? ELSE ? END,
                        r.channel = ?
                    WHERE r.user_id IN (?, ?) AND r.status='waiting'";
            $stmt = $db->pdo->prepare($sql);
            $stmt->execute([$peer_id, $user_id, $peer_id, $user_id, $channel_name, $user_id, $peer_id]);

            if ($stmt->rowCount() >= 2) {
                $bundle = rc_token_bundle($channel_name);
                if (!empty($bundle['token']) && !empty($bundle['channel']) && !empty($bundle['app_id'])) {
                    $bundle['matched'] = true;
                    $bundle['peer_id'] = $peer_id;
                    $bundle['success'] = true; $result = $bundle; return;
                }
            }
        }

        // Not yet matched
        $result = ['success'=>true,'matched'=>false]; return;
    } catch (Exception $e) {
        $result = ['success'=>false,'error_key'=>'join_failed','error_message'=>$e->getMessage()]; return;
    }
}

// Fallback
return;
?>
