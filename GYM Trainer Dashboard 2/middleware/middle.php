<?php
function requireAuth(): void
{
    if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || !isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function requireRole(string $role): void
{
    requireAuth();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
            header('Location: client-dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    }
}

function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isTrainer(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'trainer';
}

function isClient(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'client';
}