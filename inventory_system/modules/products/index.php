<?php
/**
 * Products Management Page
 * File: /modules/products/index.php
 * 
 * Displays and manages inventory products with full-screen layout,
 * advanced filtering, search, and export capabilities.
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

// Extract unique categories for filter dropdown
$categories = [];
foreach ($products as $product) {
    if (!empty($product['category_name']) && !in_array($product['category_name'], $categories)) {
        $categories[] = $product['category_name'];
    }
}
sort($categories);

// Helper function to determine stock status
function getStockStatus($quantity, $minLevel) {
    if ($quantity == 0) {
        return 'out-stock';
    } elseif ($quantity <= $minLevel) {
        return 'low-stock';
    }
    return 'normal-stock';
}

// Helper function to get status icon
function getStatusIcon($status) {
    switch ($status) {
        case 'active':
            return 'fa-check-circle';
        case 'inactive':
            return 'fa-pause-circle';
        case 'discontinued':
            return 'fa-times-circle';
        default:
            return 'fa-check-circle';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Inventory System</title>
    
    <!-- External CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Products Specific CSS -->
    <link href="../../assets/css/products.css" rel="stylesheet">
    
    <!-- Meta tags for better SEO and performance -->
    <meta name="description" content="Manage your inventory products with advanced filtering and search capabilities">
    <meta name="keywords" content="inventory, products, management, stock, e-commerce">
    <meta name="author" content="Inventory Management System">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/img/favicon.ico">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="../../assets/css/products.css" as="style">
    <link rel="preload" href="../../assets/js/products.js" as="script">
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
    <div class="products-container">
        
        <!-- Page Header -->
        <header class="products-header">
            <div class="products-title">
                <h1>
                    <div class="products-title-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    Products Management
                </h1>
                <p class="products-subtitle">
                    Manage your inventory products with powerful tools and insights
                </p>
            </div>
            
            <div class="products-actions">
                <div class="products-stats">
                    <div class="products-count"><?php echo count($products); ?></div>
                    <div class="products-count-label">Total Products</div>
                </div>
                
                <?php if ($auth->hasRole('manager')): ?>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 
                    Add New Product
                </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Filters Section -->
        <section class="products-filters">
            <div class="filter-group">
                <label class="filter-label" for="searchInput">
                    <i class="fas fa-search"></i> 
                    Search Products
                </label>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="form-control"
                    placeholder="Search by name, SKU, or description..." 
                    autocomplete="off"
                    spellcheck="false"
                >
            </div>
            
            <div class="filter-group">
                <label class="filter-label" for="categoryFilter">
                    <i class="fas fa-tags"></i> 
                    Category Filter
                </label>
                <select id="categoryFilter" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label" for="stockFilter">
                    <i class="fas fa-cubes"></i> 
                    Stock Status
                </label>
                <select id="stockFilter" class="form-control">
                    <option value="">All Stock Levels</option>
                    <option value="low">Low Stock</option>
                    <option value="normal">Normal Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label" for="statusFilter">
                    <i class="fas fa-toggle-on"></i> 
                    Status Filter
                </label>
                <select id="statusFilter" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="discontinued">Discontinued</option>
                </select>
            </div>
        </section>

        <!-- Products Table -->
        <main class="products-table-container">
            <header class="products-table-header">
                <h3 class="products-table-title">
                    <i class="fas fa-list"></i>
                    Products Inventory
                </h3>
                
                <div class="products-table-actions">
                    <span id="resultCount" class="result-count">
                        Showing all <?php echo count($products); ?> products
                    </span>
                    <button onclick="exportTable()" class="btn btn-sm btn-success" title="Export to CSV">
                        <i class="fas fa-download"></i> 
                        Export
                    </button>
                </div>
            </header>
            
            <div class="products-table-wrapper">
                <?php if (!empty($products)): ?>
                <table class="products-table" id="productsTable">
                    <thead>
                        <tr>
                            <th>
                                <i class="fas fa-barcode"></i> 
                                SKU Code
                            </th>
                            <th>
                                <i class="fas fa-tag"></i> 
                                Product Information
                            </th>
                            <th>
                                <i class="fas fa-layer-group"></i> 
                                Category
                            </th>
                            <th>
                                <i class="fas fa-dollar-sign"></i> 
                                Price
                            </th>
                            <th>
                                <i class="fas fa-cubes"></i> 
                                Stock Level
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
                        $stockStatus = getStockStatus(
                            $product['quantity'] ?? 0, 
                            $product['min_stock_level'] ?? 0
                        );
                        $statusIcon = getStatusIcon($product['status'] ?? 'active');
                        ?>
                        <tr 
                            data-category="<?php echo htmlspecialchars($product['category_name'] ?? ''); ?>" 
                            data-status="<?php echo htmlspecialchars($product['status'] ?? 'active'); ?>"
                            data-stock="<?php echo $stockStatus; ?>"
                            data-product-id="<?php echo $product['id']; ?>"
                        >
                            <!-- SKU Code -->
                            <td>
                                <span class="sku-code">
                                    <?php echo htmlspecialchars($product['sku']); ?>
                                </span>
                            </td>
                            
                            <!-- Product Information -->
                            <td>
                                <div class="product-name">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </div>
                                <?php if (!empty($product['description'])): ?>
                                <div class="product-description">
                                    <?php 
                                    $description = htmlspecialchars($product['description']);
                                    echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;
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
                            
                            <!-- Price -->
                            <td>
                                <span class="price-display">
                                    <?php echo number_format($product['price'], 2); ?>
                                </span>
                            </td>
                            
                            <!-- Stock Level -->
                            <td>
                                <div class="stock-indicator <?php echo $stockStatus; ?>">
                                    <div class="stock-main">
                                        <strong><?php echo number_format($product['quantity'] ?? 0); ?></strong>
                                    </div>
                                    <div class="stock-sub">
                                        Min: <?php echo number_format($product['min_stock_level'] ?? 0); ?>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Status -->
                            <td>
                                <span class="status-badge <?php echo $product['status'] ?? 'active'; ?>">
                                    <i class="fas <?php echo $statusIcon; ?>"></i>
                                    <?php echo ucfirst($product['status'] ?? 'Active'); ?>
                                </span>
                            </td>
                            
                            <!-- Actions -->
                            <td>
                                <div class="action-buttons">
                                    <!-- View Details -->
                                    <a href="view.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Product Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if ($auth->hasRole('manager')): ?>
                                    <!-- Edit Product -->
                                    <a href="edit.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit Product">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Adjust Stock -->
                                    <a href="../stock/adjust.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       title="Adjust Stock Level">
                                        <i class="fas fa-warehouse"></i>
                                    </a>
                                    
                                    <?php if ($auth->hasRole('admin')): ?>
                                    <!-- Delete Product -->
                                    <a href="delete.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete Product"
                                       data-confirm-delete="true">
                                        <i class="fas fa-trash"></i>
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
                    <i class="fas fa-box-open"></i>
                    <h3>No Products Found</h3>
                    <p>You haven't added any products yet. Start building your inventory by adding your first product.</p>
                    
                    <?php if ($auth->hasRole('manager')): ?>
                    <div style="margin-top: 2rem;">
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Your First Product
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination (if needed for large datasets) -->
            <?php if (count($products) > 50): ?>
            <footer class="products-pagination">
                <button class="btn btn-sm" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="btn btn-sm active">1</button>
                <button class="btn btn-sm">2</button>
                <button class="btn btn-sm">3</button>
                <button class="btn btn-sm">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </footer>
            <?php endif; ?>
        </main>
        
    </div>

    <!-- Loading Script Data for JavaScript -->
    <script>
        // Pass PHP data to JavaScript
        window.productsData = {
            totalProducts: <?php echo count($products); ?>,
            userRole: '<?php echo $_SESSION['role'] ?? 'user'; ?>',
            currentPage: '<?php echo $current_page; ?>',
            currentDir: '<?php echo $current_dir; ?>',
            categories: <?php echo json_encode($categories); ?>,
            features: {
                canAdd: <?php echo $auth->hasRole('manager') ? 'true' : 'false'; ?>,
                canEdit: <?php echo $auth->hasRole('manager') ? 'true' : 'false'; ?>,
                canDelete: <?php echo $auth->hasRole('admin') ? 'true' : 'false'; ?>
            }
        };
    </script>

    <!-- Products JavaScript -->
    <script src="../../assets/js/products.js"></script>
    
    <!-- Additional Scripts for Enhanced Functionality -->
    <script>
        // Enhanced page initialization
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Products page loaded successfully');
            console.log('Total products:', window.productsData.totalProducts);
            
            // Add keyboard shortcut hints
            if (window.productsData.userRole === 'admin' || window.productsData.userRole === 'manager') {
                console.log('Keyboard shortcuts available:');
                console.log('- Ctrl+F: Focus search');
                console.log('- Escape: Clear filters');
                console.log('- Ctrl+E: Export data');
                console.log('- Ctrl+R: Refresh page');
            }
        });

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Products page error:', e.error);
            
            // Show user-friendly error message
            if (typeof productsManager !== 'undefined' && productsManager.showNotification) {
                productsManager.showNotification('An error occurred. Please refresh the page.', 'error');
            }
        });

        // Performance monitoring
        window.addEventListener('load', function() {
            if (performance.navigation.type === 1) {
                console.log('Page was refreshed');
            }
            
            // Log page load time
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log(`Page loaded in ${loadTime}ms`);
        });
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