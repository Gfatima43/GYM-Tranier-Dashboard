<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../login.php');
    exit();
}

$database = require '../bootstrap.php';
$pdo = $database->pdo;

$error = '';
$success = '';
$active_form = 'login'; // default shown form

// ─── AUTO-REDIRECT if already logged in ────
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit();
}

// ─── HANDLE FORM SUBMISSIONS ────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // LOGIN
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $active_form = 'login';

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter a valid email and password';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['auth'] = true;

                header('Location: ../dashboard.php');
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        }

        // REGISTER
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $active_form = 'register';

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($confirm_password)) {
            $error = 'Please fill in all required fields.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Check for duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'This email already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $hashed]);
                $success = "Created account successfully!";
                $active_form = 'login';
            }
        }
    }
}
?>