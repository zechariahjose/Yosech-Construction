<?php
include("includes/header.php");
include("includes/navbar.php");
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/contact.css">

<!-- ── Hero ─────────────────────────────────────────────────── -->
<section class="ct-hero">
    <div class="ct-hero-bg" style="background-image:url('<?= BASE_URL ?>/assets/projects/multiPurposeBuilding.jpg');"></div>
    <div class="ct-hero-overlay"></div>
    <div class="container ct-hero-content">
        <div class="hero-eyebrow">Get in Touch</div>
        <h1 class="hero-title">We'd Love to Hear<br>From You.</h1>
        <p class="hero-sub">Reach Yosech Construction by phone, email, or visit our office in Dipolog City. No on-site messaging — use the details below.</p>
    </div>
</section>

<!-- ── Body ─────────────────────────────────────────────────── -->
<div class="container ct-body">

    <!-- Info cards -->
    <div class="ct-info-grid">

        <!-- Location -->
        <div class="ct-info-card">
            <div class="ct-info-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
                <div class="ct-info-label">Office Location</div>
                <div class="ct-info-body">
                    Yosech Construction &amp; Civil Engineering<br>
                    Dipolog City, 7100<br>
                    Zamboanga del Norte, Philippines
                </div>
            </div>
        </div>

        <!-- Phone & Email -->
        <div class="ct-info-card">
            <div class="ct-info-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
                <div class="ct-info-label">Phone &amp; Email</div>
                <div class="ct-info-body">
                    <a href="tel:+630652124588">+63 (065) 212 4588</a><br>
                    <a href="mailto:info@yosechconstruction.com">info@yosechconstruction.com</a>
                </div>
            </div>
        </div>

        <!-- Hours -->
        <div class="ct-info-card">
            <div class="ct-info-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </div>
            <div>
                <div class="ct-info-label">Business Hours</div>
                <div class="ct-info-body">
                    Monday – Friday: 8:00 AM – 5:00 PM<br>
                    Saturday: 8:00 AM – 12:00 PM<br>
                    Sunday &amp; Holidays: Closed
                </div>
            </div>
        </div>

    </div>

    <!-- Bottom row: social + apply CTA -->
    <div class="ct-bottom-grid">

        <!-- Social links -->
        <div class="ct-panel">
            <div class="ct-panel-label">Follow Us</div>
            <h2 class="ct-panel-title">Connect With Us</h2>
            <p class="ct-panel-desc">Follow Yosech Construction on social media for project updates, equipment availability, and company news.</p>

            <div class="ct-social-list">
                <a href="https://facebook.com/" class="ct-social-link" target="_blank" rel="noopener noreferrer">
                    <div class="ct-social-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </div>
                    <span class="ct-social-name">Facebook</span>
                    <span class="ct-social-handle">@yosechconstruction</span>
                </a>
                <a href="https://instagram.com/" class="ct-social-link" target="_blank" rel="noopener noreferrer">
                    <div class="ct-social-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </div>
                    <span class="ct-social-name">Instagram</span>
                    <span class="ct-social-handle">@yosechconstruction</span>
                </a>
            </div>
        </div>

        <!-- Apply CTA -->
        <div class="ct-panel ct-apply-panel">
            <div>
                <div class="ct-panel-label">Applications</div>
                <h2 class="ct-panel-title">Project &amp; Rental<br>Inquiries</h2>
                <p class="ct-panel-desc">For formal project proposals or equipment rental requests, submit an application through our online form.</p>
                <ul class="ct-apply-steps">
                    <li>
                        <span class="ct-step-num">1</span>
                        Create or log in to your client account.
                    </li>
                    <li>
                        <span class="ct-step-num">2</span>
                        Fill out the application form with your project details.
                    </li>
                    <li>
                        <span class="ct-step-num">3</span>
                        Our team reviews and responds within 2 business days.
                    </li>
                </ul>
            </div>
            <a href="<?= BASE_URL ?>/apply.php" class="ct-btn-apply">
                Go to Application Form →
            </a>
        </div>

    </div>

</div>

<?php include("includes/footer.php"); ?>
