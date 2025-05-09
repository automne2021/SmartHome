<?php
require_once '../src/controllers/AuthController.php';

$authController = new AuthController();
$authController->logout();

// Redirect to login page
header('Location: login.php');
exit;
