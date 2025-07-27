<?php
/**
 * Stock Management Page
 * File: /modules/stock/index.php
 * 
 * Displays and manages inventory stock levels with full-screen layout,
 * advanced filtering, search, and stock adjustment capabilities.
 */

require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Authentication check
$auth = new Auth();
$auth->requireLogin();

// Initialize inventory functions
$inventory = new InventoryFunctions();
$products = $inventory->getAllProducts();

// Determine active page for navbar
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Extract unique locations for filter dropdown
$locations = [];
foreach ($products as $product) {
    $location = $product['location'] ?? 'Warehouse A';
    if (!in_array($location, $locations)) {
        $locations[] = $location;
    }
}
sort($locations);

// Calculate stock statistics
$stockStats = [
    'total' => count($products),
    'normalStock' => 0,
    'lowStock' => 0,
    'outOfStock' => 0,
    'totalValue' => 0
];

foreach ($products as $product) {
    $quantity = $product['quantity'] ?? 0;
    $minLevel = $product['min_stock_level'] ?? 0;
    $price = $product['price'] ?? 0;
    
    $stockStats['totalValue'] += ($quantity * $price);
    
    if ($quantity == 0) {
        $stockStats['outOfStock']++;
    } elseif ($quantity <= $minLevel) {
        $stockStats['lowStock']++;
    } else {
        $stockStats['normalStock']++;
    }
}

// Helper function to determine stock status
function getStockStatus($quantity, $minLevel) {
    if ($quantity == 0) {
        return 'out';
    } elseif ($quantity <= $minLevel) {
        return 'low';
    }
    return 'normal';
}

// Helper function to get stock status badge
function getStatusBadgeClass($quantity, $minLevel) {
    if ($quantity == 0) {
        return 'bg-red';
    } elseif ($quantity <= $minLevel) {
        return 'bg-orange';
    }
    return 'bg-green';
}

// Helper function to get stock status text
function getStatusText($quantity, $minLevel) {
    if ($quantity == 0) {
        return 'Out of Stock';
    } elseif ($quantity <= $minLevel) {
        return 'Low Stock';
    }
    return 'In Stock';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - Inventory System</title>
    
    <!-- External CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Stock Specific CSS -->
    <link href="../../assets/css/stocks.css" rel="stylesheet">
    
    <!-- Meta tags for better SEO and performance -->
    <meta name="description" content="Manage your inventory stock levels with real-time monitoring and advanced filtering">
    <meta name="keywords" content="inventory, stock, management, warehouse, levels, tracking">
    <meta name="author" content="Inventory Management System">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/img/favicon.ico">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="../../assets/css/stocks.css" as="style">
    <link rel="preload" href="../../assets/js/stocks.js" as="script">
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

    <!-- Main Container -->
    <div class="stocks-container">
        
        <!-- Page Header -->
        <header class="stocks-header">
            <div class="stocks-title">
                <h1>
                    <div class="stocks-title-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    Stock Management
                </h1>
                <p class="stocks-subtitle">
                    Monitor and manage inventory stock levels across all locations
                </p>
            </div>
            
            <div class="stocks-actions">
                <div class="stocks-stats">
                    <div class="stock-stat-card">
                        <div class="stock-stat-number"><?php echo $stockStats['normalStock']; ?></div>
                        <div class="stock-stat-label">In Stock</div>
                    </div>
                    
                    <div class="stock-stat-card warning">
                        <div class="stock-stat-number"><?php echo $stockStats['lowStock']; ?></div>
                        <div class="stock-stat-label">Low Stock</div>
                    </div>
                    
                    <div class="stock-stat-card danger">
                        <div class="stock-stat-number"><?php echo $stockStats['outOfStock']; ?></div>
                        <div class="stock-stat-label">Out of Stock</div>
                    </div>
                </div>
                
                <?php if ($auth->hasRole('manager')): ?>
                <a href="bulk-adjust.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> 
                    Bulk Adjustment
                </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Filters Section -->
        <section class="stocks-filters">
            <div class="filter-group">
                <label class="filter-label" for="searchInput">
                    <i class="fas fa-search"></i> 
                    Search Stock Items
                </label>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="form-control"
                    placeholder="Search by product name, SKU..." 
                    autocomplete="off"
                    spellcheck="false"
                >
            </div>
            
            <div class="filter-group">
                <label class="filter-label" for="locationFilter">
                    <i class="fas fa-map-marker-alt"></i> 
                    Location Filter
                </label>
                <select id="locationFilter" class="form-control">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                    <option value="<?php echo htmlspecialchars($location); ?>">
                        <?php echo htmlspecialchars($location); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label" for="stockLevelFilter">
                    <i class="fas fa-layer-group"></i> 
                    Stock Level
                </label>
                <select id="stockLevelFilter" class="form-control">
                    <option value="">All Stock Levels</option>
                    <option value="low">Low Stock</option>
                    <option value="normal">Normal Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label" for="sortOrder">
                    <i class="fas fa-sort"></i> 
                    Sort By
                </label>
                <select id="sortOrder" class="form-control">
                    <option value="name">Product Name</option>
                    <option value="quantity">Stock Quantity</option>
                    <option value="status">Stock Status</option>
                    <option value="location">Location</option>
                </select>
            </div>
        </section>

        <!-- Quick Actions Panel -->
        <section class="quick-actions">
            <div class="quick-actions-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </div>
            <div class="quick-actions-buttons">
                <button onclick="stockManager.selectAllVisible()" class="btn btn-sm btn-info">
                    <i class="fas fa-check-square"></i> 
                    Select All
                </button>
                <button onclick="stockManager.clearSelection()" class="btn btn-sm" style="background: #6b7280; color: white;">
                    <i class="fas fa-square"></i> 
                    Clear Selection
                </button>
                <button onclick="exportStockData()" class="btn btn-sm btn-success">
                    <i class="fas fa-download"></i> 
                    Export Data
                </button>
            </div>
        </section>

        <!-- Stock Table -->
        <main class="stocks-table-container">
            <header class="stocks-table-header">
                <h3 class="stocks-table-title">
                    <i class="fas fa-table"></i>
                    Stock Inventory Overview
                </h3>
                
                <div class="stocks-table-actions">
                    <span id="resultCount" class="result-count">
                        Showing all <?php echo count($products); ?> items
                    </span>
                    <button onclick="exportStockData()" class="btn btn-sm btn-success" title="Export to CSV">
                        <i class="fas fa-download"></i> 
                        Export
                    </button>
                </div>
            </header>
            
            <div class="stocks-table-wrapper">
                <?php if (!empty($products)): ?>
                <table class="stocks-table" id="stockTable">
                    <thead>
                        <tr>
                            <th>
                                <i class="fas fa-barcode"></i> 
                                SKU Code
                            </th>
                            <th>
                                <i class="fas fa-tag"></i> 
                                Product Name
                            </th>
                            <th>
                                <i class="fas fa-layer-group"></i> 
                                Category
                            </th>
                            <th>
                                <i class="fas fa-cubes"></i> 
                                Current Stock
                            </th>
                            <th>
                                <i class="fas fa-level-down-alt"></i> 
                                Min. Level
                            </th>
                            <th>
                                <i class="fas fa-map-marker-alt"></i> 
                                Location
                            </th>
                            <th>
                                <i class="fas fa-traffic-light"></i> 
                                Status
                            </th>
                            <th>
                                <i class="fas fa-cogs"></i> 
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <?php
                        $quantity = $product['quantity'] ?? 0;
                        $minLevel = $product['min_stock_level'] ?? 0;
                        $stockStatus = getStockStatus($quantity, $minLevel);
                        $badgeClass = getStatusBadgeClass($quantity, $minLevel);
                        $statusText = getStatusText($quantity, $minLevel);
                        $location = $product['location'] ?? 'Warehouse A';
                        ?>
                        <tr 
                            data-stock-status="<?php echo $stockStatus; ?>"
                            data-quantity="<?php echo $quantity; ?>"
                            data-min-level="<?php echo $minLevel; ?>"
                            data-location="<?php echo htmlspecialchars($location); ?>"
                            data-product-id="<?php echo $product['id']; ?>"
                        >
                            <!-- SKU Code -->
                            <td>
                                <span class="sku-code">
                                    <?php echo htmlspecialchars($product['sku']); ?>
                                </span>
                            </td>
                            
                            <!-- Product Name -->
                            <td>
                                <div class="product-name">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </div>
                                <?php if (!empty($product['description'])): ?>
                                <div style="color: #6b7280; font-size: 0.85rem; margin-top: 0.25rem; font-style: italic;">
                                    <?php 
                                    $description = htmlspecialchars($product['description']);
                                    echo strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
                                    ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Category -->
                            <td>
                                <?php if (!empty($product['category_name'])): ?>
                                <span class="category-tag">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">Uncategorized</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Current Stock -->
                            <td>
                                <div class="stock-quantity <?php echo $stockStatus; ?>">
                                    <div class="stock-main">
                                        <strong><?php echo number_format($quantity); ?></strong>
                                        <span>units</span>
                                    </div>
                                    <?php if ($stockStatus === 'low' || $stockStatus === 'out'): ?>
                                    <div class="stock-trend">
                                        <?php echo $stockStatus === 'out' ? 'Critical!' : 'Need restock'; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Minimum Level -->
                            <td>
                                <span class="min-level">
                                    <?php echo number_format($minLevel); ?>
                                </span>
                            </td>
                            
                            <!-- Location -->
                            <td>
                                <span class="location-tag">
                                    <?php echo htmlspecialchars($location); ?>
                                </span>
                            </td>
                            
                            <!-- Status -->
                            <td>
                                <span class="status-badge <?php echo $badgeClass; ?>">
                                    <i class="fas <?php 
                                        echo $stockStatus === 'out' ? 'fa-times-circle' : 
                                             ($stockStatus === 'low' ? 'fa-exclamation-triangle' : 'fa-check-circle'); 
                                    ?>"></i>
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            
                            <!-- Actions -->
                            <td>
                                <div class="action-buttons">
                                    <!-- View Stock History -->
                                    <a href="history.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Stock History">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    
                                    <?php if ($auth->hasRole('manager')): ?>
                                    <!-- Adjust Stock -->
                                    <a href="adjust.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Adjust Stock Level">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Quick Add Stock -->
                                    <a href="quick-add.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       title="Quick Add Stock">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                    
                                    <?php if ($stockStatus === 'low' || $stockStatus === 'out'): ?>
                                    <!-- Reorder -->
                                    <a href="reorder.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Create Reorder">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-warehouse"></i>
                    <h3>No Stock Data Found</h3>
                    <p>There are no products in your inventory yet. Add some products first to manage their stock levels.</p>
                    
                    <div style="margin-top: 2rem;">
                        <a href="../products/add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Your First Product
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
        
    </div>

    <!-- Loading Script Data for JavaScript -->
    <script>
        // Pass PHP data to JavaScript
        window.stockData = {
            totalItems: <?php echo count($products); ?>,
            stockStats: <?php echo json_encode($stockStats); ?>,
            userRole: '<?php echo $_SESSION['role'] ?? 'user'; ?>',
            currentPage: '<?php echo $current_page; ?>',
            currentDir: '<?php echo $current_dir; ?>',
            locations: <?php echo json_encode($locations); ?>,
            features: {
                canAdjust: <?php echo $auth->hasRole('manager') ? 'true' : 'false'; ?>,
                canBulkEdit: <?php echo $auth->hasRole('manager') ? 'true' : 'false'; ?>,
                canReorder: <?php echo $auth->hasRole('manager') ? 'true' : 'false'; ?>
            },
            alerts: {
                lowStockCount: <?php echo $stockStats['lowStock']; ?>,
                outOfStockCount: <?php echo $stockStats['outOfStock']; ?>
            }
        };
    </script>

    <!-- Stock JavaScript -->
    <script src="../../assets/js/stocks.js"></script>
    
    <!-- Additional Scripts for Enhanced Functionality -->
    <script>
        // Enhanced page initialization
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Stock Management page loaded successfully');
            console.log('Stock statistics:', window.stockData.stockStats);
            
            // Add keyboard shortcut hints
            if (window.stockData.userRole === 'admin' || window.stockData.userRole === 'manager') {
                console.log('Stock Manager shortcuts available:');
                console.log('- Ctrl+F: Focus search');
                console.log('- Escape: Clear filters');
                console.log('- Ctrl+E: Export data');
                console.log('- Ctrl+A: Select all visible');
            }
            
            // Show critical alerts
            const alerts = window.stockData.alerts;
            if (alerts.outOfStockCount > 0) {
                console.warn(`⚠️ ${alerts.outOfStockCount} items are out of stock!`);
            }
            if (alerts.lowStockCount > 0) {
                console.warn(`📉 ${alerts.lowStockCount} items have low stock levels`);
            }
        });

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Stock page error:', e.error);
            
            // Show user-friendly error message
            if (typeof stockManager !== 'undefined' && stockManager.showNotification) {
                stockManager.showNotification('An error occurred. Please refresh the page.', 'error');
            }
        });

        // Performance monitoring
        window.addEventListener('load', function() {
            if (performance.navigation.type === 1) {
                console.log('Stock page was refreshed');
            }
            
            // Log page load time
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log(`Stock page loaded in ${loadTime}ms`);
            
            // Track critical stock items
            const criticalItems = document.querySelectorAll('[data-stock-status="out"]').length;
            const lowItems = document.querySelectorAll('[data-stock-status="low"]').length;
            
            console.log(`Stock monitoring: ${criticalItems} critical, ${lowItems} low stock items`);
        });

        // Auto-refresh for real-time stock monitoring (every 5 minutes)
        setInterval(function() {
            // Check if user is still active (mouse movement, keyboard activity)
            if (document.hasFocus()) {
                console.log('Auto-refreshing stock data...');
                
                // You can implement AJAX refresh here instead of full page reload
                // For now, we'll just show a notification
                if (typeof stockManager !== 'undefined' && stockManager.showNotification) {
                    stockManager.showNotification('Stock data auto-refreshed', 'info');
                }
            }
        }, 300000); // 5 minutes
    </script>

    <!-- Service Worker Registration (Optional for PWA features) -->
    <script>
        // Register service worker for offline capabilities (if available)
        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            navigator.serviceWorker.register('../../sw.js')
                .then(registration => console.log('SW registered:', registration))
                .catch(error => console.log('SW registration failed:', error));
        }
    </script>

</body>
</html>