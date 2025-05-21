<?php
session_start();
require_once '../functions/auth_functions.php';

logoutUser();
header('Location: /index.php');
exit();
?> 