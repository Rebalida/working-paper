<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Auth.php';

Auth::init();

// If already logged in, go to dashboard
if (Auth::check()) {
    header('Location: /public/admin/dashboard.php');
} else {
    // Otherwise go to login
    header('Location: /public/login.php');
}
exit;