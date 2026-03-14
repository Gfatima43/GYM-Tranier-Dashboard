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
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
            header('Location: ../client-dashboard.php');
        } else {
            header('Location: ../dashboard.php');
        }
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

                // Clear any error/success messages
                unset($_SESSION['error']);
                unset($_SESSION['success']);

                header('Location: ../dashboard.php');
                exit();
            } else {
                $_SESSION['error'] = 'Invalid email or password';
                header('Location: ../login.php');
                exit();
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
                $_SESSION['error'] = "Email already exists.";
                header("Location: ../login.php?form=register");
                exit();
            }
            
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hashed]);
            $_SESSION['success'] = "Account created successfully! Please login.";
            header('Location: ../login.php');
            exit();
        
        }
    }
}
?>