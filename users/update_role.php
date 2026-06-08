<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

// -----------------------------
// Authentication Check
// -----------------------------

requireAdminArea();
requirePermission('USER_ACCOUNT', 'UPDATE');
requirePost();

// -----------------------------
// Request Data
// -----------------------------

$userId = (int) ($_POST['user_id'] ?? 0);
$roleId = (int) ($_POST['role_id'] ?? 0);

// -----------------------------
// Database Queries
// -----------------------------

$stmt = db()->prepare(
    'SELECT ua.username, r.role_name
     FROM USER_ACCOUNT ua
     INNER JOIN ROLE r ON r.role_id = ua.role_id
     WHERE ua.user_id = ?'
);
$stmt->execute([$userId]);
$account = $stmt->fetch();

$stmt = db()->prepare('SELECT role_name FROM ROLE WHERE role_id = ?');
$stmt->execute([$roleId]);
$newRole = $stmt->fetchColumn();

if (!$account || !$newRole) {
    flash('danger', 'User or role not found.');
    redirect('users/index.php');
}

$stmt = db()->prepare('UPDATE USER_ACCOUNT SET role_id = ? WHERE user_id = ?');
$stmt->execute([$roleId, $userId]);

// -----------------------------
// Redirect
// -----------------------------

logCurrentUserActivity('UPDATE_USER_ROLE', 'User changed account ' . $account['username'] . ' role from ' . $account['role_name'] . ' to ' . $newRole . '.');
flash('success', 'User role updated.');
redirect('users/index.php');
