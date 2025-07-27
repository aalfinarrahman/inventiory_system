<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$inventory = new InventoryFunctions();

// Tentukan halaman aktif untuk navbar
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$error = '';
$success = '';
$product = null;

// Cek apakah ada ID produk
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    
    // Ambil data produk menggunakan metode yang sudah dibuat
    $product = $inventory->getProductById($product_id);
    
    if (!$product) {
        $error = 'Product not found';
    }
}

// Proses form adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adjust_stock'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $movement_type = $_POST['movement_type'];
    $reason = $_POST['reason'];
    $user_id = $_SESSION['user_id'];
    
    if ($movement_type == 'out' && $quantity > $product['quantity']) {
        $error = 'Cannot remove more stock than available';
    } else {
        if ($inventory->updateStock($product_id, $quantity, $movement_type, $reason, $user_id)) {
            $success = 'Stock updated successfully';
            
            // Refresh product data
            $product = $inventory->getProductById($product_id);
        } else {
            $error = 'Failed to update stock';
        }
    }
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
    <title>Adjust Stock - Inventory System</title>
    
    <!-- External CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Stock Specific CSS -->
    <link href="../../assets/css/stocks.css" rel="stylesheet">
    
    <!-- Meta tags for better SEO and performance -->
    <meta name="description" content="Adjust inventory stock levels with real-time monitoring">
    <meta name="keywords" content="inventory, stock, adjustment, management, warehouse">
    <meta name="author" content="Inventory Management System">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/img/favicon.ico">
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
        <div class="stocks-header">
            <div class="stocks-title">
                <h1>
                    <div class="stocks-title-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    Adjust Stock
                </h1>
                <p class="stocks-subtitle">Modify inventory levels with detailed tracking</p>
            </div>
            
            <div class="stocks-actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Stock List
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($product): ?>
        <!-- Product Information Card -->
        <div class="stocks-table-container">
            <div class="stocks-table-header">
                <h3 class="stocks-table-title">
                    <i class="fas fa-info-circle"></i>
                    Product Information
                </h3>
            </div>
            
            <div style="padding: 2rem 2.5rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                    <div class="product-info-card">
                        <h2 style="color: #1e293b; font-size: 1.5rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-box" style="color: #10b981;"></i>
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h2>
                        
                        <div style="display: grid; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-weight: 600; color: #64748b;">SKU:</span>
                                <span class="sku-code"><?php echo htmlspecialchars($product['sku']); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-weight: 600; color: #64748b;">Category:</span>
                                <span><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-weight: 600; color: #64748b;">Location:</span>
                                <span><?php echo htmlspecialchars($product['location'] ?? 'Warehouse A'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stock-info-card">
                        <h3 style="color: #1e293b; font-size: 1.25rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-warehouse" style="color: #10b981;"></i>
                            Stock Information
                        </h3>
                        
                        <div style="display: grid; gap: 1rem;">
                            <div class="stock-stat-display">
                                <span style="font-size: 0.9rem; color: #64748b; font-weight: 600;">Current Stock</span>
                                <div style="font-size: 2rem; font-weight: 800; color: <?php echo ($product['quantity'] <= $product['min_stock_level']) ? '#ef4444' : '#10b981'; ?>;">
                                    <?php echo number_format($product['quantity'] ?? 0); ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-weight: 600; color: #64748b;">Minimum Level:</span>
                                <span style="font-weight: 700;"><?php echo number_format($product['min_stock_level']); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                                <span style="font-weight: 600; color: #64748b;">Status:</span>
                                <?php 
                                $statusClass = getStatusBadgeClass($product['quantity'], $product['min_stock_level']);
                                $statusText = getStatusText($product['quantity'], $product['min_stock_level']);
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment Form -->
        <div class="stocks-table-container">
            <div class="stocks-table-header">
                <h3 class="stocks-table-title">
                    <i class="fas fa-edit"></i>
                    Stock Adjustment Form
                </h3>
            </div>
            
            <div style="padding: 2rem 2.5rem;">
                <form method="POST" style="max-width: 600px;">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <div class="filter-group" style="margin-bottom: 1.5rem;">
                        <label class="filter-label" for="movement_type">
                            <i class="fas fa-exchange-alt"></i>
                            Movement Type
                        </label>
                        <select id="movement_type" name="movement_type" class="form-control" required>
                            <option value="in">Stock In (Add)</option>
                            <option value="out">Stock Out (Remove)</option>
                            <option value="adjustment">Adjustment (Set to specific value)</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="margin-bottom: 1.5rem;">
                        <label class="filter-label" for="quantity">
                            <i class="fas fa-sort-numeric-up"></i>
                            Quantity
                        </label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
                        <small style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem; display: block;" id="quantity_help">
                            Enter the quantity to add or remove
                        </small>
                    </div>
                    
                    <div class="filter-group" style="margin-bottom: 2rem;">
                        <label class="filter-label" for="reason">
                            <i class="fas fa-clipboard-list"></i>
                            Reason
                        </label>
                        <select id="reason" name="reason" class="form-control" required>
                            <option value="Purchase">Purchase</option>
                            <option value="Sale">Sale</option>
                            <option value="Return">Return</option>
                            <option value="Damage">Damage/Loss</option>
                            <option value="Inventory Count">Inventory Count</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="adjust_stock" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1rem;">
                        <i class="fas fa-save"></i> Update Stock
                    </button>
                </form>
            </div>
        </div>
        
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Product not found. <a href="index.php" style="color: #d97706; text-decoration: underline;">Return to stock list</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const movementType = document.getElementById('movement_type');
            const quantityHelp = document.getElementById('quantity_help');
            
            movementType.addEventListener('change', function() {
                if (this.value === 'in') {
                    quantityHelp.textContent = 'Enter the quantity to add to stock';
                } else if (this.value === 'out') {
                    quantityHelp.textContent = 'Enter the quantity to remove from stock';
                } else { // adjustment
                    quantityHelp.textContent = 'Enter the new total quantity for this product';
                }
            });
        });
    </script>
</body>
</html>