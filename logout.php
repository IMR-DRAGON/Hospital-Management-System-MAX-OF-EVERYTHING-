<?php 
require_once __DIR__ . '/includes/init.php';
if(!empty($_SESSION['name']))
{
	unset($_SESSION['name']);
}
header('Location: login.php');
exit();
?>