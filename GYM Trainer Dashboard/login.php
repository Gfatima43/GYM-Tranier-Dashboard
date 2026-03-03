<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] === true && isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error       = $error       ?? '';
$success     = $success     ?? '';
$active_form = $active_form ?? 'login';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Trainer - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="login-container">
        <!-- Background with overlay -->
        <div class="background-overlay"></div>

        <!-- Flip Container -->
        <div class="flip-container <?= $active_form === 'register' ? 'flipped' : '' ?>" id="flipContainer">
            <div class="flipper">
                <!-- Login Form (Front) -->
                <div class="front">
                    <div class="form-card p-4">
                        <?php if ($error && $active_form === 'login'): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success && $active_form === 'login'): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <!-- Logo -->
                        <div class="logo-section">
                            <img src="./images/fitness-logo-design-sports-logo_331749-164.avif" alt="Gym Logo" class="logo-img">
                        </div>

                        <!-- Title -->
                        <div class="text-center mb-4">
                            <h1 class="form-title">Trainer Login</h1>
                            <p class="form-subtitle">Welcome back to your fitness platform</p>
                        </div>

                        <!-- Form -->
                        <form id="loginForm" class="login-form" method="POST" action="Authority/auth.php">
                            <input type="hidden" name="action" value="login">
                            <!-- Email/Username -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email or Username</label>
                                <input type="text" class="form-control input-field" id="email" name="email" placeholder="Enter your email or username" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" class="form-control input-field" id="password" name="password" placeholder="Enter your password" required>
                                    <button type="button" class="toggle-password" id="toggleLogin">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>

                            <!-- Login Button -->
                            <button type="submit" class="btn btn-login w-100">Login</button>
                        </form>

                        <!-- Signup Link -->
                        <div class="text-center mt-4">
                            <p class="signup-link">Don't have an account?
                                <button type="button" class="btn-flip-link" id="signupBtn">Sign up</button>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Signup Form (Back) -->
                <div class="back">
                    <div class="form-card p-4">
                        <?php if ($error && $active_form === 'register'): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success && $active_form === 'register'): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <!-- Logo -->
                        <div class="logo-section">
                            <img src="images/fitness-logo-design-sports-logo_331749-164.avif" alt="Gym Logo" class="logo-img">
                        </div>

                        <!-- Title -->
                        <div class="text-center mb-4">
                            <h1 class="form-title">Create Account</h1>
                            <p class="form-subtitle">Join our fitness community today</p>
                        </div>

                        <!-- Form -->
                        <form id="signupForm" class="login-form" method="POST" action="Authority/auth.php">
                            <input type="hidden" name="action" value="register">
                            <!-- Full Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control input-field" id="name" name="name" placeholder="Enter your full name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="signup-email" class="form-label">Email</label>
                                <input type="email" class="form-control input-field" id="signup-email" name="email" placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="signup-password" class="form-label">Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" class="form-control input-field" id="signup-password" name="password" placeholder="Create a password" required>
                                    <button type="button" class="toggle-password" id="toggleSignupPass">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <label for="confirm-password" class="form-label">Confirm Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" class="form-control input-field" id="confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                                    <button type="button" class="toggle-password" id="toggleConfirmPass">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Signup Button -->
                            <button type="submit" class="btn btn-login w-100">Create Account</button>
                        </form>

                        <!-- Login Link -->
                        <div class="text-center mt-4">
                            <p class="signup-link">Already have an account?
                                <button type="button" class="btn-flip-link" id="loginBtn">Login</button>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/js/all.min.js" integrity="sha512-6BTOlkauINO65nLhXhthZMtepgJSghyimIalb+crKRPhvhmsCdnIuGcVbR5/aQY2A+260iC1OPy1oCdB6pSSwQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="js/script.js"></script>
</body>

</html>