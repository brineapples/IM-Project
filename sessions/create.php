<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requireAdminArea();
requirePermission('SESSION', 'CREATE');

$errors = [];
$session = [
    'session_title' => trim($_POST['session_title'] ?? ''),
    'session_date' => $_POST['session_date'] ?? '',
    'start_time' => $_POST['start_time'] ?? '',
    'end_time' => $_POST['end_time'] ?? '',
    'location' => trim($_POST['location'] ?? ''),
    'coach_user_id' => (int) ($_POST['coach_user_id'] ?? 0),
];
$coaches = getCoaches();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($session['session_title'] === '') {
        $errors[] = 'Session title is required.';
    }
    if ($session['session_date'] === '') {
        $errors[] = 'Session date is required.';
    }
    if ($session['start_time'] === '' || $session['end_time'] === '') {
        $errors[] = 'Start time and end time are required.';
    }
    if ($session['start_time'] !== '' && $session['end_time'] !== '' && $session['end_time'] <= $session['start_time']) {
        $errors[] = 'End time must be after start time.';
    }
    if ($session['location'] === '') {
        $errors[] = 'Location is required.';
    }
    if ($session['coach_user_id'] <= 0) {
        $errors[] = 'Coach is required.';
    }

    if (!$errors) {
        $statusId = getSessionStatusId(resolveSessionStatusName($session['session_date'], $session['start_time'], $session['end_time']));
        $stmt = db()->prepare(
            'INSERT INTO `SESSION` (session_title, session_date, start_time, end_time, location, instructor_user_id, status_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $session['session_title'],
            $session['session_date'],
            $session['start_time'],
            $session['end_time'],
            $session['location'],
            $session['coach_user_id'],
            $statusId,
        ]);

        logCurrentUserActivity('CREATE_SESSION', 'User created session ' . $session['session_title'] . ' on ' . $session['session_date'] . '.');
        flash('success', 'Session created.');
        redirect('sessions/index.php');
    }
}

renderAdminHeader('Create Session', 'sessions');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <h1>Create <em>Session</em></h1>
                <a class="btn btn-outline-secondary" href="<?= e(url('sessions/index.php')) ?>">Back</a>
            </div>

            <?php require __DIR__ . '/form.php'; ?>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
