<?php
session_start();
require 'app/bootstrap.php';

$title = 'Quick Links';

$content = function () {
    ?>
    <div class="container py-4">
        <h1 class="h4 mb-4">Manikya Market - Quick Access Links</h1>
        
        <div class="row g-3">
            <!-- Buyer Section -->
            <div class="col-lg-6">
                <div class="bg-white border rounded-4 p-4 mm-card">
                    <h2 class="h6 mb-3 text-primary">
                        <i data-lucide="shopping-bag" class="mm-icon" style="width: 18px; height: 18px;"></i>
                        Buyer Access
                    </h2>
                    <div class="space-y-2">
                        <div>
                            <a href="/?p=buyer/login" class="btn btn-sm btn-outline-primary">Buyer Login</a>
                            <code class="small text-muted">/?p=buyer/login</code>
                        </div>
                        <div>
                            <a href="/?p=buyer/signup" class="btn btn-sm btn-outline-primary">Create Account</a>
                            <code class="small text-muted">/?p=buyer/signup</code>
                        </div>
                        <div>
                            <small class="text-muted">
                                <strong>Test Credentials:</strong><br>
                                Email: prashanth4006@gmail.com<br>
                                Password: buyer123
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Merchant Section -->
            <div class="col-lg-6">
                <div class="bg-white border rounded-4 p-4 mm-card">
                    <h2 class="h6 mb-3 text-success">
                        <i data-lucide="store" class="mm-icon" style="width: 18px; height: 18px;"></i>
                        Seller Access
                    </h2>
                    <div class="space-y-2">
                        <div>
                            <a href="/?p=merchant/login" class="btn btn-sm btn-outline-success">Seller Login</a>
                            <code class="small text-muted">/?p=merchant/login</code>
                        </div>
                        <div>
                            <small class="text-muted">
                                <strong>Test Credentials:</strong><br>
                                Email: merchant@test.com<br>
                                Password: merchant
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logistics Section -->
            <div class="col-lg-6">
                <div class="bg-white border rounded-4 p-4 mm-card">
                    <h2 class="h6 mb-3 text-info">
                        <i data-lucide="truck" class="mm-icon" style="width: 18px; height: 18px;"></i>
                        Logistics Access
                    </h2>
                    <div class="space-y-2">
                        <div>
                            <a href="/?p=logistics/login" class="btn btn-sm btn-outline-info">Logistics Login</a>
                            <code class="small text-muted">/?p=logistics/login</code>
                        </div>
                        <div>
                            <small class="text-muted">
                                <strong>Test Credentials:</strong><br>
                                Email: logistics@test.com<br>
                                Password: logistics
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Public Pages -->
            <div class="col-lg-6">
                <div class="bg-white border rounded-4 p-4 mm-card">
                    <h2 class="h6 mb-3">
                        <i data-lucide="home" class="mm-icon" style="width: 18px; height: 18px;"></i>
                        Main Pages
                    </h2>
                    <div class="space-y-2">
                        <div>
                            <a href="/" class="btn btn-sm btn-outline-secondary">Home</a>
                            <code class="small text-muted">/</code>
                        </div>
                        <div>
                            <a href="/?p=cart" class="btn btn-sm btn-outline-secondary">Shopping Cart</a>
                            <code class="small text-muted">/?p=cart</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="alert alert-info">
            <strong>Note:</strong> All relative URLs should be prefixed with 
            <code>/king_mango</code> when accessing from outside the application.
            <br><br>
            Example: <code>https://kingmango.in/?p=logistics/login</code>
        </div>
    </div>

    <script>
        if (window.lucide) window.lucide.createIcons();
    </script>

    <style>
        .space-y-2 > * + * {
            margin-top: 0.5rem;
        }
    </style>
    <?php
};

require __DIR__ . '/app/views/layout.php';
