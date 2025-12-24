<?php
require_once 'includes/auth.php';

logoutUser();
header('Location: index.html');
exit;
?>

