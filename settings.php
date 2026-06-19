<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

// -----------------------------
// Authentication Check
// -----------------------------

requireAcceptedPolicies();

$user = currentUser();
$acceptance = policyAcceptanceDetails($user ? (int) $user['user_id'] : null);
$documents = policyDocuments();

// -----------------------------
// HTML Output
// -----------------------------

renderPublicHeader('Settings');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>User <em>Settings</em></h1>
                    <p class="app-muted mb-0">Review account access and legal policy information.</p>
                </div>
            </div>

            <div class="settings-grid">
                <section class="settings-card">
                    <h2>Account</h2>
                    <p><strong>Username:</strong> <?= e($user['username'] ?? '') ?></p>
                    <p><strong>Role:</strong> <?= e(displayRoleName($user['role_name'] ?? '')) ?></p>
                </section>

                <section class="settings-card">
                    <h2>Legal &amp; Privacy</h2>
                    <p><strong>Current policy version:</strong> <?= e(POLICY_VERSION) ?></p>
                    <?php if ($acceptance): ?>
                        <p><strong>Accepted policy version:</strong> <?= e((string) $acceptance['policy_version']) ?></p>
                        <p><strong>Accepted date:</strong> <?= e($acceptance['accepted_at'] ? date('F j, Y g:i A', strtotime((string) $acceptance['accepted_at'])) : 'Recorded this session') ?></p>
                    <?php else: ?>
                        <p>Current policies not yet accepted.</p>
                    <?php endif; ?>
                    <p><strong>Contact email:</strong> <?= e(POLICY_CONTACT_EMAIL) ?></p>
                    <div class="legal-link-list">
                        <?php foreach ($documents as $type => $document): ?>
                            <a href="<?= e(url('legal.php?document=' . $type)) ?>"><?= e($document['title']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
