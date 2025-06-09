<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Professional Service Management Platform' ?></title>
    <meta name="description" content="<?= $meta_description ?? 'Professional service management platform for businesses' ?>">
    <meta name="keywords" content="<?= $meta_keywords ?? 'service management, business platform, professional services' ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --success-color: #059669;
            --warning-color: #d97706;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #374151;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            padding: 1rem 0;
        }

        .navbar.scrolled {
            padding: 0.5rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            font-weight: 500;
            color: #374151 !important;
            margin: 0 0.5rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.1)" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-stats {
            margin-top: 3rem;
        }

        .stat-item {
            text-align: center;
            color: white;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Services Section */
        .services {
            padding: 5rem 0;
            background: var(--light-color);
        }

        .service-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            height: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 1.5rem;
        }

        .service-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .service-card p {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }

        .price-range {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Testimonials */
        .testimonials {
            padding: 5rem 0;
            background: white;
        }

        .testimonial-card {
            background: var(--light-color);
            border-radius: 16px;
            padding: 2rem;
            height: 100%;
            position: relative;
            border: 1px solid #e5e7eb;
        }

        .testimonial-quote {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            font-style: italic;
            color: #374151;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .testimonial-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 1rem;
        }

        .testimonial-info h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .testimonial-info small {
            color: #6b7280;
        }

        .rating {
            color: #fbbf24;
            margin-bottom: 1rem;
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, var(--dark-color), #374151);
            padding: 5rem 0;
            color: white;
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer h5 {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer a {
            color: #d1d5db;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            margin-top: 2rem;
            padding-top: 2rem;
            text-align: center;
            color: #9ca3af;
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .service-card {
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="/">
            <i class="fas fa-cogs me-2"></i>
            ServicePro
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/services">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pricing">Pricing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/blog">Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/contact">Contact</a>
                </li>
                <li class="nav-item ms-2">
                    <a class="btn btn-outline-primary" href="/auth/signin">Login</a>
                </li>
                <li class="nav-item ms-2">
                    <a class="btn btn-primary" href="/get-started">Get Started</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content" data-aos="fade-right">
                    <h1>Professional Service Management Made Simple</h1>
                    <p>Streamline your business operations with our comprehensive platform designed for service professionals, technicians, and growing businesses.</p>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="/get-started" class="btn btn-primary btn-lg">
                            <i class="fas fa-rocket me-2"></i>
                            Start Free Trial
                        </a>
                        <a href="/services" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-play me-2"></i>
                            Learn More
                        </a>
                    </div>

                    <div class="hero-stats">
                        <div class="row">
                            <div class="col-3">
                                <div class="stat-item">
                                    <span class="stat-number"><?= number_format($stats['satisfied_clients']) ?>+</span>
                                    <span class="stat-label">Happy Clients</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-item">
                                    <span class="stat-number"><?= number_format($stats['projects_completed']) ?>+</span>
                                    <span class="stat-label">Projects Done</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $stats['years_experience'] ?>+</span>
                                    <span class="stat-label">Years Experience</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $stats['team_members'] ?>+</span>
                                    <span class="stat-label">Team Members</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-center" data-aos="fade-left">
                    <i class="fas fa-laptop-code" style="font-size: 15rem; opacity: 0.3; color: white;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold mb-3">Our Professional Services</h2>
                <p class="lead">We provide comprehensive solutions to help your business grow and succeed in today's competitive market.</p>
            </div>
        </div>

        <div class="row">
            <?php foreach($services as $index => $service): ?>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="<?= $service['icon'] ?>"></i>
                        </div>
                        <h3><?= esc($service['title']) ?></h3>
                        <p><?= esc($service['description']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price-range"><?= esc($service['price_range']) ?></span>
                            <a href="/services" class="btn btn-outline-primary btn-sm">Learn More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold mb-3">What Our Clients Say</h2>
                <p class="lead">Don't just take our word for it. Here's what our satisfied clients have to say about our services.</p>
            </div>
        </div>

        <div class="row">
            <?php foreach($testimonials as $index => $testimonial): ?>
                <div class="col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="<?= $index * 200 ?>">
                    <div class="testimonial-card">
                        <div class="rating mb-3">
                            <?php for($i = 0; $i < $testimonial['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="testimonial-quote">"<?= esc($testimonial['testimonial']) ?>"</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">
                                <?= strtoupper(substr($testimonial['name'], 0, 1)) ?>
                            </div>
                            <div class="testimonial-info">
                                <h5><?= esc($testimonial['name']) ?></h5>
                                <small><?= esc($testimonial['position']) ?> at <?= esc($testimonial['company']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                <h2>Ready to Transform Your Business?</h2>
                <p>Join thousands of satisfied customers who have streamlined their operations with our platform.</p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="/get-started" class="btn btn-primary btn-lg">
                        <i class="fas fa-rocket me-2"></i>
                        Start Your Free Trial
                    </a>
                    <a href="/contact" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-phone me-2"></i>
                        Talk to Sales
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5>
                    <i class="fas fa-cogs me-2"></i>
                    ServicePro
                </h5>
                <p>Professional service management platform designed to help businesses grow and succeed.</p>
                <div class="social-links">
                    <a href="#" class="me-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-instagram fa-lg"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Company</h5>
                <ul class="list-unstyled">
                    <li><a href="/about">About Us</a></li>
                    <li><a href="/services">Services</a></li>
                    <li><a href="/pricing">Pricing</a></li>
                    <li><a href="/contact">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Platform</h5>
                <ul class="list-unstyled">
                    <li><a href="/auth/signin">Login</a></li>
                    <li><a href="/get-started">Sign Up</a></li>
                    <li><a href="/dashboard">Dashboard</a></li>
                    <li><a href="/customer/help">Help Center</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Resources</h5>
                <ul class="list-unstyled">
                    <li><a href="/blog">Blog</a></li>
                    <li><a href="/customer/help">Documentation</a></li>
                    <li><a href="/api">API</a></li>
                    <li><a href="/sitemap.xml">Sitemap</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Legal</h5>
                <ul class="list-unstyled">
                    <li><a href="/privacy">Privacy Policy</a></li>
                    <li><a href="/terms">Terms of Service</a></li>
                    <li><a href="/contact">Support</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> ServicePro. All rights reserved. Built with ❤️ for growing businesses.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<!-- AOS Animation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<script>
    // Initialize AOS
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('mainNav');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Newsletter subscription form (if added)
    function subscribeNewsletter(email) {
        fetch('/newsletter/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ email: email })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for subscribing!');
                } else {
                    alert(data.message || 'Subscription failed. Please try again.');
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again later.');
            });
    }

    // Add newsletter form if needed
    function addNewsletterForm() {
        const ctaSection = document.querySelector('.cta .container .row .col-lg-8');
        if (ctaSection) {
            const newsletterHTML = `
                    <div class="newsletter-signup mt-4">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="email" class="form-control" placeholder="Enter your email" id="newsletterEmail">
                                    <button class="btn btn-outline-light" onclick="subscribeNewsletter(document.getElementById('newsletterEmail').value)">
                                        Subscribe
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            ctaSection.insertAdjacentHTML('beforeend', newsletterHTML);
        }
    }

    // Uncomment to add newsletter form
    // addNewsletterForm();
</script>
</body>
</html>