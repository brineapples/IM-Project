<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

requireAdminArea();
requirePermission('APPLICATION', 'UPDATE');
requirePost();

$applicationId = (int) ($_POST['application_id'] ?? 0);
$decision = $_POST['decision'] ?? '';
$user = currentUser();

if (!$user || !in_array($decision, ['approve', 'reject'], true)) {
    flash('danger', 'Invalid application review request.');
    redirect('applications/index.php');
}

db()->beginTransaction();

try {
    $stmt = db()->prepare(
        'SELECT ma.*, aps.status_name AS application_status_name
         FROM MEMBERSHIP_APPLICATION ma
         INNER JOIN APPLICATION_STATUS aps ON aps.application_status_id = ma.application_status_id
         WHERE ma.application_id = ?
         FOR UPDATE'
    );
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();

    if (!$application) {
        db()->rollBack();
        flash('danger', 'Application not found.');
        redirect('applications/index.php');
    }

    if ($application['application_status_name'] !== 'Pending') {
        db()->rollBack();
        flash('warning', 'This application has already been reviewed.');
        redirect('applications/view.php?id=' . $applicationId);
    }

    if ($decision === 'reject') {
        $stmt = db()->prepare(
            'UPDATE MEMBERSHIP_APPLICATION
             SET application_status_id = ?, reviewed_by_user_id = ?, reviewed_at = NOW()
             WHERE application_id = ?'
        );
        $stmt->execute([
            getApplicationStatusId('Rejected'),
            (int) $user['user_id'],
            $applicationId,
        ]);

        logCurrentUserActivity('REJECT_APPLICATION', 'User rejected membership application #' . $applicationId . '.');
        db()->commit();

        flash('success', 'Application rejected.');
        redirect('applications/view.php?id=' . $applicationId);
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM USER_ACCOUNT WHERE username = ?');
    $stmt->execute([$application['desired_username']]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new RuntimeException('Requested username is already in use.');
    }

    $stmt = db()->prepare('INSERT INTO USER_ACCOUNT (username, password, role_id) VALUES (?, ?, ?)');
    $stmt->execute([
        $application['desired_username'],
        $application['password'],
        getRoleId('Member'),
    ]);
    $newUserId = (int) db()->lastInsertId();

    $stmt = db()->prepare(
        'INSERT INTO MEMBER (
            user_id, first_name, middle_name, last_name, address, birthday, member_status_id,
            phone_number, barangay_id, chapter_id, gender_id, educational_attainment_id, primary_school,
            primary_year_graduated, secondary_school, secondary_year_graduated, college_school, college_year_graduated
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $newUserId,
        $application['first_name'],
        $application['middle_name'],
        $application['last_name'],
        $application['address'],
        $application['birthday'],
        (int) $application['member_status_id'],
        $application['phone_number'],
        (int) $application['barangay_id'],
        (int) $application['chapter_id'],
        (int) $application['gender_id'],
        (int) $application['educational_attainment_id'],
        $application['primary_school'],
        $application['primary_year_graduated'],
        $application['secondary_school'],
        $application['secondary_year_graduated'],
        $application['college_school'],
        $application['college_year_graduated'],
    ]);
    $memberId = (int) db()->lastInsertId();

    $stmt = db()->prepare(
        'UPDATE MEMBERSHIP_APPLICATION
         SET application_status_id = ?, reviewed_by_user_id = ?, reviewed_at = NOW()
         WHERE application_id = ?'
    );
    $stmt->execute([
        getApplicationStatusId('Approved'),
        (int) $user['user_id'],
        $applicationId,
    ]);

    logCurrentUserActivity('APPROVE_APPLICATION', 'User approved membership application #' . $applicationId . '.');
    logCurrentUserActivity('CREATE_USER_ACCOUNT', 'User account ' . $application['desired_username'] . ' was initialized from approved application.');
    logCurrentUserActivity('CREATE_MEMBER', 'Member #' . $memberId . ' was created from approved application #' . $applicationId . '.');

    db()->commit();

    flash('success', 'Application approved. Member account has been created.');
    redirect('applications/view.php?id=' . $applicationId);
} catch (Throwable $exception) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }

    flash('danger', 'Could not review application: ' . $exception->getMessage());
    redirect('applications/view.php?id=' . $applicationId);
}
