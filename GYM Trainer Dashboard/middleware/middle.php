<?php
require_once './bootstrap.php';

// Check if user is authenticated
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    // redirect to login
    header('Location: ../login.php');
    exit();
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // redirect to home
    header('Location: ../dashboard.php');
    exit();
}

// Check if user is trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    // redirect to home
    header('Location: ../dashboard.php');
    exit();
}