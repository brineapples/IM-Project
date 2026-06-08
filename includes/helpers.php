<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = '/IM%20Project';
    return $base . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function asset(string $path): string
{
    return url('templatemo_548_training_studio/assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consumeFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user !== null && (int) $user['user_id'] === (int) $_SESSION['user_id']) {
        return $user;
    }

    $stmt = db()->prepare(
        'SELECT ua.user_id, ua.username, ua.role_id, r.role_name
         FROM USER_ACCOUNT ua
         INNER JOIN ROLE r ON r.role_id = ua.role_id
         WHERE ua.user_id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('warning', 'Please log in first.');
        redirect('login.php');
    }
}

function isSuperAdmin(?array $user = null): bool
{
    $user = $user ?: currentUser();
    return $user !== null && $user['role_name'] === 'Super Admin';
}

function displayRoleName(string $roleName): string
{
    return $roleName === 'Instructor' ? 'Coach' : $roleName;
}

function getBarangayOptions(): array
{
    return [
        1 => 'Bagumbayan',
        2 => 'Bambang',
        3 => 'Calzada',
        4 => 'Central Bicutan',
        5 => 'Central Signal Village',
        6 => 'Fort Bonifacio',
        7 => 'Hagonoy',
        8 => 'Ibayo-Tipas',
        9 => 'Katuparan',
        10 => 'Ligid-Tipas',
        11 => 'Lower Bicutan',
        12 => 'Maharlika Village',
        13 => 'Napindan',
        14 => 'New Lower Bicutan',
        15 => 'North Daang Hari',
        16 => 'North Signal Village',
        17 => 'Palingon',
        18 => 'Pinagsama',
        19 => 'San Miguel',
        20 => 'Santa Ana',
        21 => 'South Daang Hari',
        22 => 'South Signal Village',
        23 => 'Tanyag',
        24 => 'Tuktukan',
        25 => 'Upper Bicutan',
        26 => 'Ususan',
        27 => 'Wawa',
        28 => 'Western Bicutan',
    ];
}

function isValidBarangayId(int $barangayId): bool
{
    return array_key_exists($barangayId, getBarangayOptions());
}

function barangayName(?int $barangayId): string
{
    if ($barangayId === null) {
        return 'Unknown barangay';
    }

    return getBarangayOptions()[$barangayId] ?? 'Unknown barangay';
}

function hasPermission(string $moduleName, string $actionName, ?int $userId = null): bool
{
    $user = null;

    if ($userId === null) {
        $user = currentUser();
        if ($user === null) {
            return false;
        }
        $userId = (int) $user['user_id'];
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) AS allowed
         FROM USER_ACCOUNT ua
         INNER JOIN ROLE_PERMISSION rp ON rp.role_id = ua.role_id
         INNER JOIN PERMISSION p ON p.permission_id = rp.permission_id
         INNER JOIN MODULE m ON m.module_id = p.module_id
         INNER JOIN ACTION_TYPE a ON a.action_type_id = p.action_type_id
         WHERE ua.user_id = ?
           AND m.module_name = ?
           AND a.action_name = ?'
    );
    $stmt->execute([$userId, strtoupper($moduleName), strtoupper($actionName)]);
    return (int) $stmt->fetchColumn() > 0;
}

function requirePermission(string $moduleName, string $actionName): void
{
    requireLogin();

    if (!hasPermission($moduleName, $actionName)) {
        $user = currentUser();
        if ($user) {
            logActivity((int) $user['user_id'], 'PERMISSION_DENIED', 'User attempted ' . strtoupper($actionName) . ' on ' . strtoupper($moduleName) . ' without permission.');
        }
        http_response_code(403);
        require_once __DIR__ . '/../includes/layout.php';
        renderHeader('Access Denied');
        ?>
        <section class="section app-section">
            <div class="container">
                <div class="app-panel text-center">
                    <h2>Access denied</h2>
                    <p>Your role does not have permission to open this page.</p>
                    <a class="btn btn-primary" href="<?= e(url('sessions/index.php')) ?>">Back to sessions</a>
                </div>
            </div>
        </section>
        <?php
        renderFooter();
        exit;
    }
}

function userCanAccessAdminArea(?int $userId = null): bool
{
    $user = $userId === null ? currentUser() : null;
    if ($userId === null && $user === null) {
        return false;
    }

    $userId = $userId ?? (int) $user['user_id'];

    return hasPermission('SESSION', 'CREATE', $userId)
        || hasPermission('SESSION', 'UPDATE', $userId)
        || hasPermission('SESSION', 'DELETE', $userId)
        || hasPermission('APPLICATION', 'READ', $userId)
        || hasPermission('USER_ACCOUNT', 'READ', $userId)
        || hasPermission('ROLE', 'READ', $userId)
        || hasPermission('PERMISSION', 'READ', $userId)
        || hasPermission('ACTIVITY_LOG', 'READ', $userId);
}

function requireAdminArea(): void
{
    requireLogin();

    if (!userCanAccessAdminArea()) {
        logCurrentUserActivity('PERMISSION_DENIED', 'User attempted to open the admin area without admin permissions.');
        flash('warning', 'You do not have access to the admin area.');
        redirect('sessions.php');
    }
}

function requireSuperAdmin(): void
{
    requireLogin();

    if (!isSuperAdmin()) {
        logCurrentUserActivity('PERMISSION_DENIED', 'User attempted to open a Super Admin page.');
        http_response_code(403);
        require_once __DIR__ . '/../includes/layout.php';
        renderHeader('Access Denied');
        ?>
        <section class="section app-section">
            <div class="container">
                <div class="app-panel text-center">
                    <h2>Access denied</h2>
                    <p>Only Super Admin users can open this page.</p>
                    <a class="btn btn-primary" href="<?= e(url('sessions/index.php')) ?>">Back to sessions</a>
                </div>
            </div>
        </section>
        <?php
        renderFooter();
        exit;
    }
}

function logActivity(?int $userId, string $action, string $description): void
{
    $stmt = db()->prepare(
        'INSERT INTO ACTIVITY_LOG (user_id, action, description)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, strtoupper($action), $description]);
}

function logCurrentUserActivity(string $action, string $description): void
{
    $user = currentUser();
    if ($user !== null) {
        logActivity((int) $user['user_id'], $action, $description);
    }
}

function logSystemActivity(string $action, string $description): void
{
    logActivity(null, $action, $description);
}

function firstAuditUserId(): ?int
{
    $stmt = db()->query(
        "SELECT ua.user_id
         FROM USER_ACCOUNT ua
         INNER JOIN ROLE r ON r.role_id = ua.role_id
         WHERE r.role_name = 'Super Admin'
         ORDER BY ua.user_id
         LIMIT 1"
    );
    $userId = $stmt->fetchColumn();
    return $userId === false ? null : (int) $userId;
}

function logFailedLogin(string $username, ?int $matchedUserId = null): void
{
    $description = 'Failed login attempt for username ' . $username . '.';

    logActivity($matchedUserId, 'LOGIN_FAILED', $description);
}

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
}

function getCoaches(): array
{
    $stmt = db()->query(
        "SELECT ua.user_id, ua.username, r.role_name
         FROM USER_ACCOUNT ua
         INNER JOIN ROLE r ON r.role_id = ua.role_id
         WHERE r.role_name = 'Instructor'
         ORDER BY r.role_name, ua.username"
    );

    return $stmt->fetchAll();
}

function resolveSessionStatusName(string $date, string $startTime, string $endTime): string
{
    $now = new DateTimeImmutable('now');
    $start = new DateTimeImmutable($date . ' ' . $startTime);
    $end = new DateTimeImmutable($date . ' ' . $endTime);

    if ($now < $start) {
        return 'Incoming';
    }

    if ($now > $end) {
        return 'Finished';
    }

    return 'In Progress';
}

function getSessionStatusId(string $statusName): int
{
    $stmt = db()->prepare('SELECT status_id FROM SESSION_STATUS WHERE status_name = ?');
    $stmt->execute([$statusName]);
    $statusId = $stmt->fetchColumn();

    if ($statusId === false) {
        throw new RuntimeException('Missing session status: ' . $statusName);
    }

    return (int) $statusId;
}

function refreshSessionStatuses(): void
{
    db()->exec(
        "UPDATE `SESSION`
         SET status_id = CASE
             WHEN TIMESTAMP(session_date, start_time) > NOW() THEN (SELECT status_id FROM SESSION_STATUS WHERE status_name = 'Incoming')
             WHEN TIMESTAMP(session_date, end_time) < NOW() THEN (SELECT status_id FROM SESSION_STATUS WHERE status_name = 'Finished')
             ELSE (SELECT status_id FROM SESSION_STATUS WHERE status_name = 'In Progress')
         END"
    );
}

function getSessionStatuses(): array
{
    return db()->query('SELECT status_id, status_name FROM SESSION_STATUS ORDER BY FIELD(status_name, "Incoming", "In Progress", "Finished"), status_name')->fetchAll();
}

function getApplicationStatusId(string $statusName): int
{
    $stmt = db()->prepare('SELECT application_status_id FROM APPLICATION_STATUS WHERE status_name = ?');
    $stmt->execute([$statusName]);
    $statusId = $stmt->fetchColumn();

    if ($statusId === false) {
        throw new RuntimeException('Missing application status: ' . $statusName);
    }

    return (int) $statusId;
}

function getRoleId(string $roleName): int
{
    $stmt = db()->prepare('SELECT role_id FROM ROLE WHERE role_name = ?');
    $stmt->execute([$roleName]);
    $roleId = $stmt->fetchColumn();

    if ($roleId === false) {
        throw new RuntimeException('Missing role: ' . $roleName);
    }

    return (int) $roleId;
}

function calculateAge(string $birthday): int
{
    $birthDate = new DateTimeImmutable($birthday);
    $today = new DateTimeImmutable('today');

    return $birthDate->diff($today)->y;
}

function userCount(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM USER_ACCOUNT')->fetchColumn();
}
