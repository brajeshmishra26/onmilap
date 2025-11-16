<?php

if (role(['permissions' => ['wallet' => 'manage_withdrawals']])) {
    $private_data["manage_withdrawals"] = true;
    include('fns/load/withdrawals.php');
}
?>