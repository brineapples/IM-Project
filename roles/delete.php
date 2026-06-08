<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

// -----------------------------
// Authentication Check
// -----------------------------

requireAdminArea();
requireSuperAdmin();
requirePost();

// -----------------------------
// Database Queries
// -----------------------------

$roleId = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT role_name FROM ROLE WHERE role_id = ?');
$stmt->execute([$roleId]);
$roleName = $stmt->fetchColumn();

if (!$roleName || $roleName === 'Super Admin') {
    flash('danger', 'Role cannot be deleted.');
    redirect('roles/index.php');
}

try {
    $stmt = db()->prepare('DELETE FROM ROLE WHERE role_id = ?');
    $stmt->execute([$roleId]);

    logCurrentUserActivity('DELETE_ROLE', 'User deleted role ' . $roleName . '.');
    flash('success', 'Role deleted.');
} catch (PDOException $exception) {
    flash('danger', 'Role cannot be deleted while it is assigned to users.');
}

// -----------------------------
// Redirect
// -----------------------------

redirect('roles/index.php');
