<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

// -----------------------------
// Page Setup
// -----------------------------

$documents = policyDocuments();
$selectedType = $_GET['document'] ?? '';

if (!isset($documents[$selectedType])) {
    $selectedType = 'terms_of_use';
}

// -----------------------------
// HTML Output
// -----------------------------

renderPublicHeader('Legal Documents');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel legal-panel">
            <div class="app-heading">
                <div>
                    <h1>Legal <em>Documents</em></h1>
                    <p class="app-muted mb-0">Current policy version <?= e(POLICY_VERSION) ?>, effective <?= e(date('F j, Y', strtotime(POLICY_EFFECTIVE_DATE))) ?>.</p>
                </div>
            </div>

            <div class="legal-tabs">
                <?php foreach ($documents as $type => $document): ?>
                    <a class="<?= $type === $selectedType ? 'active' : '' ?>" href="<?= e(url('legal.php?document=' . $type)) ?>">
                        <?= e($document['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php foreach ($documents as $type => $document): ?>
                <article class="legal-document <?= $type === $selectedType ? 'active' : '' ?>" id="<?= e($type) ?>">
                    <h2><?= e($document['title']) ?></h2>
                    <p class="app-muted">Version <?= e($document['version']) ?> | Effective <?= e(date('F j, Y', strtotime($document['effective_date']))) ?></p>
                    <div class="legal-document-content">
                        <?= nl2br(e($document['content'])) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
