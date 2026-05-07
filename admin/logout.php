<?php
require_once __DIR__ . '/../lib/auth.php';
logoutUser();
header('Location: /admin/login.php'); exit;
