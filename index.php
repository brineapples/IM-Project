<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

// -----------------------------
// Page Setup
// -----------------------------

if (userCount() === 0) {
    redirect('setup.php');
}

// -----------------------------
// Data Preparation
// -----------------------------

$ctaPath = isLoggedIn() ? 'sessions.php' : 'apply.php';
$ctaText = isLoggedIn() ? 'View Sessions' : 'Apply Now';
$placeholderImage = url('assets/elementor-placeholder-image.png');

// -----------------------------
// HTML Output
// -----------------------------

renderPublicHeader('Home', 'home');
?>
<section class="landing-hero">
    <img class="landing-hero-media" src="<?= e($placeholderImage) ?>" alt="">
    <div class="landing-hero-overlay"></div>
    <div class="container landing-hero-content">
        <p class="landing-eyebrow">ILHF Santo Nino Chapter</p>
        <h1>Join the Movement. <em>Dance, Sweat,</em> and Stay Active.</h1>
        <p>View upcoming Zumba sessions, submit your application, and stay connected with the I Love Health &amp; Fitness community.</p>
        <div class="landing-actions">
            <a class="btn btn-primary" href="<?= e(url($ctaPath)) ?>"><?= e($ctaText) ?></a>
            <?php if (!isLoggedIn()): ?>
                <a class="btn btn-outline-light" href="<?= e(url('login.php')) ?>">Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="landing-band">
    <div class="container">
        <div class="landing-band-content">
            <h2>Ready to join our next Zumba session?</h2>
            <p>Check available schedules, send your application, and stay informed about chapter activities in one simple system.</p>
            <div class="landing-actions landing-actions-center">
                <a class="btn btn-primary" href="<?= e(url($ctaPath)) ?>"><?= e($ctaText) ?></a>
                <?php if (!isLoggedIn()): ?>
                    <a class="btn btn-outline-light" href="<?= e(url('login.php')) ?>">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section app-section landing-coaches" id="trainer-profiles">
    <div class="container">
        <div class="app-heading landing-heading">
            <div>
                <h1>Contact <em>Our Chapter</em></h1>
                <p class="app-muted mb-0">Reach the people who help coordinate sessions, applications, and member support for ILHF Santo Nino Chapter.</p>
            </div>
        </div>

        <div class="landing-coach-grid">
            <article class="landing-coach-card">
                <img src="<?= e($placeholderImage) ?>" alt="">
                <span> </span>
                <h2> </h2>
                <div class="landing-coach-links">
                    <a href="#" aria-label=" ">&nbsp;</a>
                    <a href="#" aria-label=" ">&nbsp;</a>
                </div>
            </article>
            <article class="landing-coach-card">
                <img src="<?= e($placeholderImage) ?>" alt="">
                <span> </span>
                <h2> </h2>
            </article>
            <article class="landing-coach-card">
                <img src="<?= e($placeholderImage) ?>" alt="">
                <span> </span>
                <h2> </h2>
            </article>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
