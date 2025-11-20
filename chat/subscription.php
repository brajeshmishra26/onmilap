<?php
/**
 * Wrapper page so links inside the chat panel can reach the public subscription screen
 * without duplicating markup or breaking asset paths. It immediately redirects
 * to the root-level subscription.php we created earlier.
 */
$target = '../subscription.php';
if (!empty($_SERVER['QUERY_STRING'])) {
	$target .= '?'.$_SERVER['QUERY_STRING'];
}
header('Location: '.$target);
exit;
