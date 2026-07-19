<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

header('Location: ' . (isLoggedIn() ? loginRedirectPath(getUserType()) : '/login.php'));
exit;
