<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$inventory = new InventoryFunctions();
$stats = $inventory->getDashboardStats();
$lowStockProducts = $inventory->getLowStockProducts();

// Tentukan halaman aktif untuk navbar
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Default values jika data tidak ada
if (!isset($stats['total_products'])) $stats['total_products'] = 0;
if (!isset($stats['total_stock'])) $stats['total_stock'] = 0;
if (!isset($stats['low_stock_count'])) $stats['low_stock_count'] = 0;
if (!isset($stats['inventory_value'])) $stats['inventory_value'] = 0;
if (!isset($lowStockProducts)) $lowStockProducts = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory System</title>
    <!-- Full Screen Dashboard CSS -->
    <link href="../../assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Additional inline styles for immediate full screen effect */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow-x: hidden;
        }
        
        .container {
            width: 100vw;
            max-width: none;
            margin: 0;
            padding: 2rem 3rem;
        }
        
        /* Enhanced visual effects */
        .stat-card:hover {
            transform: translateY(-6px) scale(1.02);
        }
        
        .chart-card:hover {
            transform: translateY(-4px);
        }
        
        /* Smooth transitions for better UX */
        * {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">
            <h2>
                <i class="fas fa-cube"></i> 
                Inventory Management System
            </h2>
        </div>
        
        <div class="nav-links">
            <a href="../dashboard/index.php" class="<?php echo ($current_dir == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> 
                <span>Dashboard</span>
            </a>
            <a href="../products/index.php" class="<?php echo ($current_dir == 'products') ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> 
                <span>Products</span>
            </a>
            <a href="../stock/index.php" class="<?php echo ($current_dir == 'stock') ? 'active' : ''; ?>">
                <i class="fas fa-warehouse"></i> 
                <span>Stock Management</span>
            </a>
            <a href="../reports/index.php" class="<?php echo ($current_dir == 'reports') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> 
                <span>Reports & Analytics</span>
            </a>
            <a href="../../includes/auth.php?action=logout">
                <i class="fas fa-sign-out-alt"></i> 
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <!-- Full Screen Main Container -->
    <div class="container">
        <div class="dashboard-container">
            <!-- Enhanced Dashboard Header -->
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
                <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>! Here's what's happening with your inventory today.</p>
            </div>
            
            <!-- Full Width Dashboard Content -->
            <div class="dashboard-content">
                <!-- Full Width Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Products</h3>
                            <p class="stat-number"><?php echo number_format($stats['total_products']); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-green">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Stock Units</h3>
                            <p class="stat-number"><?php echo number_format($stats['total_stock']); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-orange">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Low Stock Alerts</h3>
                            <p class="stat-number"><?php echo number_format($stats['low_stock_count']); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-purple">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Inventory Value</h3>
                            <p class="stat-number">$<?php echo number_format($stats['inventory_value'], 2); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Full Width Charts and Tables -->
                <div class="charts-container">
                    <!-- Low Stock Products Table - Enhanced -->
                    <div class="chart-card">
                        <div class="card-header">
                            <h2><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Stock Alerts & Warnings</h2>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($lowStockProducts)): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-tag"></i> Product Name</th>
                                        <th><i class="fas fa-barcode"></i> SKU</th>
                                        <th><i class="fas fa-cubes"></i> Current Stock</th>
                                        <th><i class="fas fa-level-down-alt"></i> Minimum Level</th>
                                        <th><i class="fas fa-traffic-light"></i> Status</th>
                                        <th><i class="fas fa-tools"></i> Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        </td>
                                        <td>
                                            <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                                                <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?>
                                            </code>
                                        </td>
                                        <td class="text-danger">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <strong><?php echo number_format($product['quantity']); ?></strong>
                                        </td>
                                        <td><?php echo number_format($product['min_stock_level'] ?? 0); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo ($product['quantity'] == 0) ? 'bg-red' : 'bg-orange'; ?>">
                                                <i class="fas <?php echo ($product['quantity'] == 0) ? 'fa-times-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                                <?php echo ($product['quantity'] == 0) ? 'Out of Stock' : 'Low Stock'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../stock/adjust.php?id=<?php echo $product['id']; ?>" class="btn btn-sm" style="background: #10b981; padding: 0.5rem 1rem; color: white; text-decoration: none; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.5rem;">
                                                <i class="fas fa-plus"></i>
                                                Restock Now
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div style="padding: 3rem; text-align: center;">
                                <div style="background: linear-gradient(135deg, #10b981, #059669); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);">
                                    <i class="fas fa-check-circle" style="font-size: 2.5rem; color: white;"></i>
                                </div>
                                <h3 style="color: #1e293b; margin-bottom: 0.5rem; font-size: 1.5rem;">Excellent Stock Levels!</h3>
                                <p style="color: #64748b; font-size: 1.1rem;">All products are adequately stocked. No immediate attention required.</p>
                                <div style="margin-top: 1.5rem;">
                                    <a href="../products/index.php" style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-eye"></i>
                                        View All Products
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Activity - Enhanced -->
                    <div class="chart-card">
                        <div class="card-header">
                            <h2><i class="fas fa-clock" style="color: #3b82f6;"></i> Recent Activity Feed</h2>
                        </div>
                        <div class="card-body">
                            <div class="activity-list">
                                <?php 
                                // Check if recent_movements exists in stats
                                if (isset($stats['recent_movements']) && !empty($stats['recent_movements'])): 
                                    foreach (array_slice($stats['recent_movements'], 0, 6) as $movement): // Show only last 6 activities
                                ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?php 
                                        echo ($movement['movement_type'] == 'in') ? 'bg-green' : 
                                             (($movement['movement_type'] == 'out') ? 'bg-red' : 'bg-blue'); 
                                    ?>">
                                        <i class="fas <?php 
                                            echo ($movement['movement_type'] == 'in') ? 'fa-arrow-up' : 
                                                 (($movement['movement_type'] == 'out') ? 'fa-arrow-down' : 'fa-sync-alt'); 
                                        ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">
                                            <strong><?php echo ucfirst($movement['movement_type']); ?></strong> - 
                                            <strong><?php echo htmlspecialchars($movement['product_name']); ?></strong>
                                            <?php 
                                            $action = ($movement['movement_type'] == 'in') ? 'restocked with' : 
                                                     (($movement['movement_type'] == 'out') ? 'sold/distributed' : 'adjusted by');
                                            echo $action . ' ' . number_format($movement['quantity']) . ' units';
                                            ?>
                                        </p>
                                        <p class="activity-time">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($movement['username']); ?> • 
                                            <i class="fas fa-calendar"></i> <?php echo date('M d, Y • H:i', strtotime($movement['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php 
                                    endforeach; 
                                else: 
                                ?>
                                <!-- Enhanced Sample activities -->
                                <div class="activity-item">
                                    <div class="activity-icon bg-blue">
                                        <i class="fas fa-rocket"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">
                                            <strong>System Initialized</strong> - Welcome to your new <strong>Inventory Management System</strong>! 
                                            Start by adding your first products to see live activity updates here.
                                        </p>
                                        <p class="activity-time">
                                            <i class="fas fa-user"></i> System • <i class="fas fa-calendar"></i> Today, <?php echo date('H:i'); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="activity-item">
                                    <div class="activity-icon bg-green">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">
                                            <strong>Security Active</strong> - All systems are running smoothly. 
                                            Your inventory data is secure and <strong>ready for management</strong>.
                                        </p>
                                        <p class="activity-time">
                                            <i class="fas fa-user"></i> Security System • <i class="fas fa-calendar"></i> Today, <?php echo date('H:i'); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="activity-item">
                                    <div class="activity-icon bg-purple">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">
                                            <strong>Analytics Ready</strong> - Real-time reporting and analytics are now active. 
                                            <strong>Track your inventory performance</strong> effortlessly.
                                        </p>
                                        <p class="activity-time">
                                            <i class="fas fa-user"></i> Analytics Engine • <i class="fas fa-calendar"></i> Today, <?php echo date('H:i'); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- View More Activities Button -->
                                <div style="text-align: center; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; margin-top: 1.5rem;">
                                    <a href="../reports/index.php" style="background: #f8fafc; color: #3b82f6; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                                        <i class="fas fa-history"></i>
                                        View Complete Activity Log
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimal Footer -->
    <footer class="footer">
        <p>
            &copy; <?php echo date('Y'); ?> Inventory Management System. All rights reserved. | 
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | 
            Last updated: <span id="lastUpdated"><?php echo date('M d, Y H:i:s'); ?></span>
        </p>
    </footer>

    <!-- Enhanced JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced fade in animation for stat cards
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });

            // Chart cards animation
            const chartCards = document.querySelectorAll('.chart-card');
            chartCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, 500 + (index * 200));
            });

            // Real-time clock update
            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleString();
                const lastUpdated = document.getElementById('lastUpdated');
                if (lastUpdated) {
                    lastUpdated.textContent = timeString;
                }
            }

            // Update clock every second
            setInterval(updateClock, 1000);
            updateClock();

            // Enhanced hover effects
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                    this.style.boxShadow = '0 12px 30px rgba(0, 0, 0, 0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.05)';
                });
            });

            // Auto-refresh data every 5 minutes with notification
            let refreshTimer = setTimeout(function() {
                // Show notification before refresh
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #3b82f6;
                    color: white;
                    padding: 1rem 1.5rem;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                    z-index: 1000;
                    animation: slideInRight 0.3s ease;
                `;
                notification.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing dashboard data...';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }, 300000); // 5 minutes

            // Clear refresh timer if user navigates away
            window.addEventListener('beforeunload', function() {
                clearTimeout(refreshTimer);
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + R: Manual refresh
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    location.reload();
                }
                
                // Ctrl/Cmd + D: Go to dashboard (already here)
                if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                    e.preventDefault();
                    // Already on dashboard, maybe focus on search if exists
                }
            });
        });

        // Enhanced logout confirmation
        const logoutLink = document.querySelector('.logout-link');
        if (logoutLink) {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to logout from the Inventory System?')) {
                    // Add loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Logging out...</span>';
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 1000);
                }
            });
        }

        // Progressive loading for better performance
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        });

        // Observe all cards for progressive loading
        document.querySelectorAll('.stat-card, .chart-card').forEach(card => {
            observer.observe(card);
        });

        // Add CSS for progressive loading
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            .chart-card, .stat-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .visible {
                animation: fadeInScale 0.6s ease-out;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>