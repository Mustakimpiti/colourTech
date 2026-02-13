<?php
/**
 * ColourTech Industries - Admin Logout
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

if (isLoggedIn()) {
    // Log activity
    logActivity($_SESSION['admin_id'], 'logout', 'admins', $_SESSION['admin_id'], 'Admin logged out');
}

// Destroy session
session_destroy();

// Redirect to login
redirect('login.php');