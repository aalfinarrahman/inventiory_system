<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

// Hanya manager yang bisa menambah produk
if (!$auth->hasRole('manager')) {
    header('Location: index.php');
    exit;
}

$inventory = new InventoryFunctions();
$categories = $inventory->getAllCategories();

// Tentukan halaman aktif untuk navbar
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$error = '';
$success = '';

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    // Validasi input
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']);
    $price = (float)$_POST['price'];
    $cost = (float)$_POST['cost'];
    $min_stock_level = (int)$_POST['min_stock_level'];
    
    if (empty($name)) {
        $error = 'Product name is required';
    } elseif (empty($sku)) {
        $error = 'SKU is required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than zero';
    } elseif ($cost <= 0) {
        $error = 'Cost must be greater than zero';
    } else {
        // Siapkan data produk
        $product_data = [
            'name' => $name,
            'description' => $_POST['description'],
            'category_id' => $_POST['category_id'] ?: null,
            'sku' => $sku,
            'price' => $price,
            'cost' => $cost,
            'min_stock_level' => $min_stock_level
        ];
        
        // Tambahkan produk
        if ($inventory->addProduct($product_data)) {
            $success = 'Product added successfully';
            // Reset form setelah berhasil
            $_POST = [];
        } else {
            $error = 'Failed to add product';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Inventory System</title>
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="../../assets/css/products.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .form-header h2 {
            font-size: 1.5rem;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #334155;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: white;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-brand">
            <h2><i class="fas fa-cube"></i> Inventory System</h2>
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
                <span>Stock</span>
            </a>
            <a href="../reports/index.php" class="<?php echo ($current_dir == 'reports') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> 
                <span>Reports</span>
            </a>
            <a href="../../logout.php">
                <i class="fas fa-sign-out-alt"></i> 
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Add New Product</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-box" style="color: #3b82f6;"></i> Product Information</h2>
            </div>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sku">SKU (Stock Keeping Unit) *</label>
                        <input type="text" id="sku" name="sku" class="form-control" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_stock_level">Minimum Stock Level *</label>
                        <input type="number" id="min_stock_level" name="min_stock_level" class="form-control" value="<?php echo htmlspecialchars($_POST['min_stock_level'] ?? '10'); ?>" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Selling Price ($) *</label>
                        <input type="number" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($_POST['price'] ?? '0.00'); ?>" min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cost">Cost Price ($) *</label>
                        <input type="number" id="cost" name="cost" class="form-control" value="<?php echo htmlspecialchars($_POST['cost'] ?? '0.00'); ?>" min="0" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate SKU based on product name
        const nameInput = document.getElementById('name');
        const skuInput = document.getElementById('sku');
        
        if (nameInput && skuInput) {
            nameInput.addEventListener('blur', function() {
                // Only auto-generate if SKU is empty
                if (skuInput.value === '') {
                    // Create SKU from name: uppercase, replace spaces with dashes, add random number
                    const namePart = this.value.substring(0, 3).toUpperCase().replace(/\s+/g, '-');
                    const randomPart = Math.floor(1000 + Math.random() * 9000); // 4-digit random number
                    skuInput.value = `${namePart}-${randomPart}`;
                }
            });
        }
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const price = parseFloat(document.getElementById('price').value);
            const cost = parseFloat(document.getElementById('cost').value);
            
            if (price <= 0) {
                alert('Price must be greater than zero');
                event.preventDefault();
                return false;
            }
            
            if (cost <= 0) {
                alert('Cost must be greater than zero');
                event.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>