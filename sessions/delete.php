<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

requireAdminArea();
requirePermission('SESSION', 'DELETE');
requirePost();

$sessionId = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT session_title, session_date FROM `SESSION` WHERE session_id = ?');
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    flash('danger', 'Session not found.');
    redirect('sessions/index.php');
}

$stmt = db()->prepare('DELETE FROM `SESSION` WHERE session_id = ?');
$stmt->execute([$sessionId]);

logCurrentUserActivity('DELETE_SESSION', 'User deleted session ' . $session['session_title'] . ' on ' . $session['session_date'] . '.');
flash('success', 'Session deleted.');
redirect('sessions/index.php');
