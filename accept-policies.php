<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

// -----------------------------
// Authentication Check
// -----------------------------

requireLogin();

if (hasAcceptedCurrentPolicies()) {
    redirect(policyRedirectAfterAcceptance());
}

$user = currentUser();
$documents = policyDocuments();
$errors = [];

// -----------------------------
// Form Handling
// -----------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedVersion = $_POST['policy_version'] ?? '';

    if ($submittedVersion !== POLICY_VERSION) {
        $errors[] = 'The policy version is no longer current. Please reload and review the latest policies.';
    }

    if (!$errors && $user !== null) {
        $accepted = setPolicyAcceptedForUser((int) $user['user_id']);
        logActivity((int) $user['user_id'], 'ACCEPT_POLICIES', 'Accepted ILHF ZEST policies version ' . $accepted['policy_version'] . '.');
        flash('success', 'Policies accepted. You may continue using ILHF ZEST.');
        redirect(policyRedirectAfterAcceptance());
    }
}

// -----------------------------
// HTML Output
// -----------------------------

renderPublicHeader('Review Required');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel legal-panel">
            <div class="app-heading">
                <div>
                    <h1>Review <em>Required</em></h1>
                    <p class="app-muted mb-0">Before continuing to ILHF ZEST, please review and accept the current policies.</p>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="policy-acceptance-form" data-policy-acceptance-form>
                <input type="hidden" name="policy_version" value="<?= e(POLICY_VERSION) ?>">

                <div class="policy-reader-grid">
                    <?php foreach ($documents as $type => $document): ?>
                        <section class="policy-reader-card">
                            <div class="policy-reader-heading">
                                <h2><?= e($document['title']) ?></h2>
                                <span class="policy-read-status" data-policy-status="<?= e($type) ?>">Scroll to the bottom to continue</span>
                            </div>
                            <div class="policy-scroll-box" data-policy-scroll="<?= e($type) ?>" tabindex="0">
                                <?= nl2br(e($document['content'])) ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>

                <div class="policy-accept-bar">
                    <p class="app-muted mb-0" data-policy-summary>Please finish reading all policy documents.</p>
                    <button class="btn btn-primary" type="submit" data-policy-submit disabled>Accept Policies</button>
                </div>
            </form>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
