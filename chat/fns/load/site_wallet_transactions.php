<?php

if (role(['permissions' => ['wallet' => 'view_site_transactions']])) {
    $private_data["site_transactions"] = true;
    include('fns/load/wallet_transactions.php');
}
?>