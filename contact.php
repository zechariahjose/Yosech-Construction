<?php
include("includes/header.php");
include("includes/navbar.php");
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">

<div class="container">
    <div class="ysc-page-header">
        <h1 class="ysc-page-title">Contact Us</h1>
        <p class="ysc-page-sub">Reach Yosech Construction by phone, email, or our social channels. This website does not include messaging — please use the contact details below.</p>
    </div>

    <div class="row g-4 pb-5">
        <div class="col-lg-4">
            <div class="home-panel h-100">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div class="home-panel-title mb-2">Office Location</div>
                <p class="small text-muted mb-0" style="line-height:1.7;">
                    Yosech Construction &amp; Civil Engineering<br>
                    Dipolog City, 7100<br>
                    Zamboanga del Norte, Philippines
                </p>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="home-panel h-100">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div class="home-panel-title mb-2">Phone &amp; Email</div>
                <ul class="list-unstyled small mb-0" style="line-height:1.9;">
                    <li><span class="text-muted">Phone:</span> <a href="tel:+630652124588">+63 (065) 212 4588</a></li>
                    <li><span class="text-muted">Email:</span> <a href="mailto:info@yosechconstruction.com">info@yosechconstruction.com</a></li>
                </ul>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="home-panel h-100">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div class="home-panel-title mb-2">Business Hours</div>
                <p class="small text-muted mb-0" style="line-height:1.7;">
                    Monday – Friday: 8:00 AM – 5:00 PM<br>
                    Saturday: 8:00 AM – 12:00 PM<br>
                    Sunday &amp; holidays: Closed
                </p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="home-panel">
                <div class="home-panel-title mb-3">Connect With Us</div>
                <p class="small text-muted mb-4">Follow Yosech Construction on social media for project updates and company news. Links open in a new tab — there is no on-site chat or messaging.</p>

                <div class="d-flex flex-wrap gap-3">
                    <a href="https://facebook.com/" class="ysc-btn-outline" target="_blank" rel="noopener noreferrer">Facebook</a>
                    <a href="https://instagram.com/" class="ysc-btn-outline" target="_blank" rel="noopener noreferrer">Instagram</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="home-panel h-100">
                <div class="home-panel-title mb-3">Project &amp; Rental Inquiries</div>
                <p class="small text-muted mb-4">For formal project proposals or equipment rental requests, use our online application form after signing in.</p>
                <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-primary w-100 text-center">Go to Application Form</a>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
