<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Customer Dashboard' ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #8b5cf6;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --sidebar-width: 280px;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, #2d1b69 0%, #11047a 100%);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .topbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
        }

        .customer-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            height: 100%;
        }

        .customer-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            background: linear-gradient(135deg, var(--primary-color), #a855f7);
        }

        .service-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .ticket-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(139, 92, 246, 0.2);
            transition: all 0.3s ease;
        }

        .ticket-item:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateX(4px);
        }

        .nav-link {
            color: #e2e8f0;
            padding: 0.875rem 1.25rem;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            margin: 0.25rem 1rem;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }

        .nav-link i {
            margin-right: 1rem;
            width: 24px;
            font-size: 1.1rem;
        }

        .status-badge {
            padding: 0.35rem 0.85rem;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .status-pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .status-completed {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }

        .welcome-banner {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(168, 85, 247, 0.1));
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .action-btn {
            background: linear-gradient(135deg, var(--primary-color), #a855f7);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
            color: white;
        }

        .recent-activity {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.show {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="p-4">
        <h4 class="text-white mb-0">
            <i class="fas fa-user-circle me-2"></i>
            Customer Portal
        </h4>
        <p class="text-light opacity-75 mb-0 small">Welcome back!</p>
    </div>

    <nav class="mt-4">
        <a href="/dashboard" class="nav-link active">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a href="/customer/services" class="nav-link">
            <i class="fas fa-concierge-bell"></i>
            My Services
        </a>
        <a href="/customer/tickets" class="nav-link">
            <i class="fas fa-ticket-alt"></i>
            Support Tickets
        </a>
        <a href="/customer/orders" class="nav-link">
            <i class="fas fa-shopping-cart"></i>
            My Orders
        </a>
        <a href="/customer/billing" class="nav-link">
            <i class="fas fa-credit-card"></i>
            Billing
        </a>
        <a href="/customer/documents" class="nav-link">
            <i class="fas fa-file-alt"></i>
            Documents
        </a>

        <!-- Divider -->
        <div class="mx-3 my-3" style="border-top: 1px solid rgba(255,255,255,0.2);"></div>

        <a href="/profile" class="nav-link">
            <i class="fas fa-user-cog"></i>
            Profile Settings
        </a>
        <a href="/customer/help" class="nav-link">
            <i class="fas fa-question-circle"></i>
            Help & Support
        </a>
        <a href="/auth/logout" class="nav-link text-warning">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Bar -->
    <div class="topbar">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h5 class="mb-0">Hello, <?= esc($user['username']) ?>! 👋</h5>
                    <p class="text-muted mb-0 small">Welcome to your customer portal</p>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown me-3">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell me-1"></i>
                        Notifications
                        <?php if(!empty($notifications)): ?>
                            <span class="badge bg-danger"><?= count(array_filter($notifications, fn($n) => !$n['read'])) ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                        <?php if(empty($notifications)): ?>
                            <li class="dropdown-item text-muted">No new notifications</li>
                        <?php else: ?>
                            <?php foreach(array_slice($notifications, 0, 5) as $notification): ?>
                                <li class="dropdown-item">
                                    <div class="fw-semibold"><?= esc($notification['title']) ?></div>
                                    <div class="text-muted small"><?= esc($notification['message']) ?></div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <button class="btn btn-primary btn-sm action-btn" onclick="refreshDashboard()">
                    <i class="fas fa-sync me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-2">Welcome to Your Dashboard</h3>
                    <p class="text-muted mb-3">Here you can manage your services, track support tickets, and view your account information.</p>
                    <a href="/customer/services/new" class="action-btn me-2">
                        <i class="fas fa-plus me-2"></i>Request New Service
                    </a>
                    <a href="/customer/tickets/create" class="btn btn-outline-primary">
                        <i class="fas fa-headset me-2"></i>Get Support
                    </a>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-user-circle text-primary" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="customer-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= $stats['active_services'] ?></h3>
                            <p class="text-muted mb-0">Active Services</p>
                            <small class="text-success">
                                <i class="fas fa-arrow-up"></i>
                                All running smoothly
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="customer-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #f59e0b);">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= $stats['support_tickets'] ?></h3>
                            <p class="text-muted mb-0">Support Tickets</p>
                            <small class="text-info">
                                <?= $stats['support_tickets'] > 0 ? 'We\'re helping you' : 'All resolved' ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="customer-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, var(--danger-color), #ef4444);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= $stats['pending_requests'] ?></h3>
                            <p class="text-muted mb-0">Pending Requests</p>
                            <small class="text-warning">
                                <?= $stats['pending_requests'] > 0 ? 'Being processed' : 'All clear' ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="customer-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, var(--success-color), #10b981);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= $stats['completed_services'] ?></h3>
                            <p class="text-muted mb-0">Completed Services</p>
                            <small class="text-success">
                                Thank you for trusting us
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services and Support Row -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="customer-card">
                    <h6 class="mb-3">
                        <i class="fas fa-concierge-bell me-2"></i>
                        My Active Services
                    </h6>
                    <?php if(empty($services)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted">No active services at the moment</p>
                            <a href="/customer/services/new" class="action-btn">
                                <i class="fas fa-plus me-2"></i>Request Your First Service
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach(array_slice($services, 0, 4) as $service): ?>
                            <div class="service-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= esc($service['name']) ?></h6>
                                        <p class="text-muted small mb-2"><?= esc($service['description']) ?></p>
                                        <div class="d-flex align-items-center">
                                            <span class="status-badge status-<?= $service['status'] ?>">
                                                <?= ucfirst($service['status']) ?>
                                            </span>
                                            <small class="text-muted ms-2">
                                                Started: <?= date('M j, Y', strtotime($service['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewService(<?= $service['id'] ?>)">
                                            <i class="fas fa-eye me-1"></i>View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(count($services) > 4): ?>
                            <div class="text-center mt-3">
                                <a href="/customer/services" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-2"></i>View All Services (<?= count($services) ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="customer-card">
                    <h6 class="mb-3">
                        <i class="fas fa-headset me-2"></i>
                        Recent Support Tickets
                    </h6>
                    <?php if(empty($support_tickets)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-smile text-success mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted small">No support tickets</p>
                            <p class="text-success small">Everything running smoothly!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach(array_slice($support_tickets, 0, 3) as $ticket): ?>
                            <div class="ticket-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="fw-semibold small"><?= esc($ticket['subject']) ?></div>
                                        <div class="text-muted small">Ticket #<?= $ticket['id'] ?></div>
                                    </div>
                                    <span class="status-badge status-<?= $ticket['status'] ?>">
                                        <?= ucfirst($ticket['status']) ?>
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    Created: <?= date('M j, Y', strtotime($ticket['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="/customer/tickets" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-ticket-alt me-2"></i>View All Tickets
                            </a>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <div class="text-center">
                        <a href="/customer/tickets/create" class="action-btn w-100">
                            <i class="fas fa-plus me-2"></i>Create New Ticket
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="recent-activity">
                    <h6 class="mb-3">
                        <i class="fas fa-history me-2"></i>
                        Recent Activities
                    </h6>
                    <?php if(empty($recent_activities)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clock text-muted mb-3" style="font-size: 2.5rem; opacity: 0.3;"></i>
                            <p class="text-muted">No recent activities</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="badge bg-<?= $activity['type'] === 'info' ? 'primary' : ($activity['type'] === 'warning' ? 'warning' : 'success') ?> rounded-pill">
                                            <i class="fas fa-<?= $activity['type'] === 'info' ? 'info' : ($activity['type'] === 'warning' ? 'exclamation' : 'check') ?>"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?= esc($activity['action']) ?></div>
                                        <div class="text-muted small"><?= date('M j, Y H:i', strtotime($activity['timestamp'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="customer-card">
                    <h6 class="mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Account Information
                    </h6>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Username:</span>
                            <span class="fw-semibold"><?= esc($user['username']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Email:</span>
                            <span class="fw-semibold"><?= esc($user['email']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Member Since:</span>
                            <span class="fw-semibold"><?= date('M Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Status:</span>
                            <span class="status-badge status-active">Active</span>
                        </div>
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <a href="/profile/edit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                        <a href="/customer/billing" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-credit-card me-2"></i>Billing Info
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Footer -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="customer-card">
                    <h6 class="mb-3">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h6>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="/customer/services/new" class="btn btn-outline-primary w-100">
                                <i class="fas fa-plus me-2"></i>New Service
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="/customer/tickets/create" class="btn btn-outline-warning w-100">
                                <i class="fas fa-headset me-2"></i>Get Support
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="/customer/billing" class="btn btn-outline-info w-100">
                                <i class="fas fa-file-invoice me-2"></i>View Bills
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="/customer/help" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-question-circle me-2"></i>Help Center
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
    // Customer Dashboard Functions
    function viewService(serviceId) {
        window.location.href = `/customer/services/view/${serviceId}`;
    }

    function refreshDashboard() {
        // Show loading state
        const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
        const originalContent = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...';
        refreshBtn.disabled = true;

        // Simulate refresh (implement actual AJAX call)
        setTimeout(() => {
            refreshBtn.innerHTML = originalContent;
            refreshBtn.disabled = false;
            showAlert('success', 'Dashboard refreshed successfully!');
        }, 1500);
    }

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
        document.body.appendChild(alert);

        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 5000);
    }

    // Sidebar Toggle for Mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });

    // Welcome message animation
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.customer-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Auto-refresh notifications every 5 minutes
    setInterval(() => {
        // Implement notification refresh
        console.log('Checking for new notifications...');
    }, 300000);

    // Interactive elements
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
</script>
</body>
</html>