<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requirePermission('APPLICATION', 'READ');

$applicationId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT ma.*, aps.status_name AS application_status_name, ch.chapter_name, g.gender_name,
            ms.status_name AS member_status_name, ea.level_name,
            reviewer.username AS reviewer_username
     FROM MEMBERSHIP_APPLICATION ma
     INNER JOIN APPLICATION_STATUS aps ON aps.application_status_id = ma.application_status_id
     INNER JOIN CHAPTER ch ON ch.chapter_id = ma.chapter_id
     INNER JOIN GENDER g ON g.gender_id = ma.gender_id
     INNER JOIN MEMBER_STATUS ms ON ms.member_status_id = ma.member_status_id
     INNER JOIN EDUCATIONAL_ATTAINMENT ea ON ea.educational_attainment_id = ma.educational_attainment_id
     LEFT JOIN USER_ACCOUNT reviewer ON reviewer.user_id = ma.reviewed_by_user_id
     WHERE ma.application_id = ?'
);
$stmt->execute([$applicationId]);
$application = $stmt->fetch();

if (!$application) {
    flash('danger', 'Application not found.');
    redirect('applications/index.php');
}

$isPending = $application['application_status_name'] === 'Pending';

renderHeader('Application Details', 'applications');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>Application <em>Details</em></h1>
                    <p class="app-muted mb-0">Application #<?= e((string) $application['application_id']) ?></p>
                </div>
                <div class="actions">
                    <a class="btn btn-outline-secondary" href="<?= e(url('applications/index.php')) ?>">Back</a>
                    <?php if ($isPending && hasPermission('APPLICATION', 'UPDATE')): ?>
                        <form method="post" action="<?= e(url('applications/review.php')) ?>" onsubmit="return confirm('Approve this application and create the member account?');">
                            <input type="hidden" name="application_id" value="<?= e((string) $application['application_id']) ?>">
                            <input type="hidden" name="decision" value="approve">
                            <button class="btn btn-primary" type="submit">Approve</button>
                        </form>
                        <form method="post" action="<?= e(url('applications/review.php')) ?>" onsubmit="return confirm('Reject this application?');">
                            <input type="hidden" name="application_id" value="<?= e((string) $application['application_id']) ?>">
                            <input type="hidden" name="decision" value="reject">
                            <button class="btn btn-outline-danger" type="submit">Reject</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="application-detail-status">
                <span class="application-status application-status-<?= e(strtolower($application['application_status_name'])) ?>">
                    <?= e($application['application_status_name']) ?>
                </span>
                <?php if ($application['reviewer_username']): ?>
                    <span class="app-muted">Reviewed by <?= e($application['reviewer_username']) ?> on <?= e(date('M d, Y g:i A', strtotime($application['reviewed_at']))) ?></span>
                <?php endif; ?>
            </div>

            <h2 class="form-section-title">Account Request</h2>
            <dl class="row">
                <dt class="col-sm-3">Requested Username</dt>
                <dd class="col-sm-9"><?= e($application['desired_username']) ?></dd>
                <dt class="col-sm-3">Submitted</dt>
                <dd class="col-sm-9"><?= e(date('F d, Y g:i A', strtotime($application['created_at']))) ?></dd>
            </dl>

            <h2 class="form-section-title">Personal Details</h2>
            <dl class="row">
                <dt class="col-sm-3">Full Name</dt>
                <dd class="col-sm-9"><?= e(trim($application['first_name'] . ' ' . ($application['middle_name'] ?? '') . ' ' . $application['last_name'])) ?></dd>
                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9"><?= e($application['address']) ?></dd>
                <dt class="col-sm-3">Birthday</dt>
                <dd class="col-sm-9"><?= e(date('F d, Y', strtotime($application['birthday']))) ?></dd>
                <dt class="col-sm-3">Age</dt>
                <dd class="col-sm-9"><?= e((string) calculateAge($application['birthday'])) ?></dd>
                <dt class="col-sm-3">Phone Number</dt>
                <dd class="col-sm-9"><?= e($application['phone_number']) ?></dd>
                <dt class="col-sm-3">Barangay</dt>
                <dd class="col-sm-9"><?= e(barangayName((int) $application['barangay_id'])) ?></dd>
                <dt class="col-sm-3">Chapter</dt>
                <dd class="col-sm-9"><?= e($application['chapter_name']) ?></dd>
                <dt class="col-sm-3">Gender</dt>
                <dd class="col-sm-9"><?= e($application['gender_name']) ?></dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9"><?= e($application['member_status_name']) ?></dd>
                <dt class="col-sm-3">Educational Attainment</dt>
                <dd class="col-sm-9"><?= e($application['level_name']) ?></dd>
            </dl>

            <h2 class="form-section-title">Education</h2>
            <dl class="row">
                <dt class="col-sm-3">Primary School</dt>
                <dd class="col-sm-9"><?= e($application['primary_school'] ?: 'Not provided') ?></dd>
                <dt class="col-sm-3">Primary Year Graduated</dt>
                <dd class="col-sm-9"><?= e($application['primary_year_graduated'] ?: 'Not provided') ?></dd>
                <dt class="col-sm-3">Secondary School</dt>
                <dd class="col-sm-9"><?= e($application['secondary_school'] ?: 'Not provided') ?></dd>
                <dt class="col-sm-3">Secondary Year Graduated</dt>
                <dd class="col-sm-9"><?= e($application['secondary_year_graduated'] ?: 'Not provided') ?></dd>
                <dt class="col-sm-3">College School</dt>
                <dd class="col-sm-9"><?= e($application['college_school'] ?: 'Not provided') ?></dd>
                <dt class="col-sm-3">College Year Graduated</dt>
                <dd class="col-sm-9"><?= e($application['college_year_graduated'] ?: 'Not provided') ?></dd>
            </dl>

        </div>
    </div>
</section>
<?php renderFooter(); ?>
