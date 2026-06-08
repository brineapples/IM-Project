<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if (userCount() === 0) {
    redirect('setup.php');
}

$ctaPath = isLoggedIn() ? 'sessions.php' : 'apply.php';
$ctaText = isLoggedIn() ? 'View Sessions' : 'Apply Now';

renderPublicHeader('Home', 'home');
?>
<section class="landing-hero">
    <video autoplay muted loop playsinline>
        <source src="<?= e(asset('images/gym-video.mp4')) ?>" type="video/mp4">
    </video>
    <div class="landing-hero-overlay"></div>
    <div class="container landing-hero-content">
        <p class="landing-eyebrow">ILHF ZEST</p>
        <h1>Lorem ipsum <em>dolor sit</em> amet</h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae libero sed arcu facilisis gravida.</p>
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
            <h2>Lorem ipsum dolor sit amet</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas lacinia, augue vitae tincidunt suscipit, arcu sem luctus libero, vitae cursus lorem lectus non justo.</p>
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
                <h1>Lorem <em>Ipsum</em></h1>
                <p class="app-muted mb-0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae arcu sed justo cursus bibendum.</p>
            </div>
        </div>

        <div class="landing-coach-grid">
            <article class="landing-coach-card">
                <img src="<?= e(asset('images/first-trainer.jpg')) ?>" alt="">
                <span>Chapter President</span>
                <h2>Anabelle Reloj</h2>
                <div class="landing-coach-links">
                    <a href="#" aria-label="Anabelle Reloj Facebook link placeholder">Facebook</a>
                    <a href="#" aria-label="Anabelle Reloj Messenger link placeholder">Messenger</a>
                </div>
            </article>
            <article class="landing-coach-card">
                <img src="<?= e(asset('images/second-trainer.jpg')) ?>" alt="">
                <span>Consectetur</span>
                <h2>Amet Elit</h2>
            </article>
            <article class="landing-coach-card">
                <img src="<?= e(asset('images/third-trainer.jpg')) ?>" alt="">
                <span>Adipiscing</span>
                <h2>Tempor Incididunt</h2>
            </article>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
