<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$inventory = new InventoryFunctions();

// Tentukan halaman aktif untuk navbar
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Ambil data untuk laporan
$products = $inventory->getAllProducts();
$stats = $inventory->getDashboardStats();

// Ambil data pergerakan stok
$query = "SELECT sm.*, p.name as product_name, p.sku, u.username 
         FROM stock_movements sm 
         JOIN products p ON sm.product_id = p.id 
         JOIN users u ON sm.user_id = u.id 
         ORDER BY sm.created_at DESC 
         LIMIT 100";
$stmt = $inventory->getConnection()->prepare($query);
$stmt->execute();
$stock_movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter berdasarkan tanggal jika ada
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

if (!empty($start_date) && !empty($end_date)) {
    $query = "SELECT sm.*, p.name as product_name, p.sku, u.username 
             FROM stock_movements sm 
             JOIN products p ON sm.product_id = p.id 
             JOIN users u ON sm.user_id = u.id 
             WHERE DATE(sm.created_at) BETWEEN ? AND ? 
             ORDER BY sm.created_at DESC";
    $stmt = $inventory->getConnection()->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $stock_movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hitung nilai inventaris per kategori
$query = "SELECT c.name as category, SUM(p.cost * s.quantity) as value, 
         COUNT(p.id) as product_count, SUM(s.quantity) as total_quantity 
         FROM products p 
         JOIN stock s ON p.id = s.product_id 
         LEFT JOIN categories c ON p.category_id = c.id 
         GROUP BY c.name 
         ORDER BY value DESC";
$stmt = $inventory->getConnection()->prepare($query);
$stmt->execute();
$inventory_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Inventory System</title>
    <!-- Dashboard CSS for better card styling -->
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="../../assets/css/products.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Additional styles for reports page */
        .report-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .report-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .report-header h2 {
            font-size: 1.25rem;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .report-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .report-filters .form-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .report-filters label {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .report-filters input, .report-filters select {
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .report-filters button {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .chart-container {
            height: 300px;
            margin-bottom: 1.5rem;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .data-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .data-card h3 {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0 0 0.5rem 0;
        }
        
        .data-card p {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .movement-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .movement-in {
            background: #dcfce7;
            color: #166534;
        }
        
        .movement-out {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .movement-adjustment {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        @media (min-width: 768px) {
            .report-container {
                grid-template-columns: repeat(2, 1fr);
            }
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

    <div class="container">
        <!-- Main Container -->
        <div class="products-container">
            
            <!-- Page Header -->
            <header class="products-header">
                <div class="products-title">
                    <h1>
                        <div class="products-title-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        Reports & Analytics
                    </h1>
                    <p class="products-subtitle">
                        Comprehensive insights and analytics for your inventory management system
                    </p>
                </div>
                
                <div class="products-actions">
                    <div class="products-stats">
                        <div class="products-count"><?php echo count($stock_movements); ?></div>
                        <div class="products-count-label">Total Reports</div>
                    </div>
                    
                    <?php if ($auth->hasRole('manager')): ?>
                    <button onclick="exportAllReports()" class="btn btn-primary">
                        <i class="fas fa-download"></i> 
                        Export All Reports
                    </button>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- Report Filters -->
            <div class="report-filters">
                <form method="get" action="" class="filter-form" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div class="form-group">
                        <label for="start_date"><i class="fas fa-calendar"></i> Start Date:</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date"><i class="fas fa-calendar"></i> End Date:</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Summary Cards -->
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
                    <div class="stat-icon bg-purple">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Inventory Value</h3>
                        <p class="stat-number">$<?php echo number_format($stats['total_stock_value'], 2); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Low Stock Items</h3>
                        <p class="stat-number"><?php echo number_format($stats['low_stock_count']); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Stock Movements</h3>
                        <p class="stat-number"><?php echo count($stock_movements); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Reports Grid -->
            <div class="report-container">
            <!-- Inventory Value by Category -->
            <div class="report-card">
                <div class="report-header">
                    <h2><i class="fas fa-chart-pie" style="color: #3b82f6;"></i> Inventory Value by Category</h2>
                    <button onclick="exportTableToCSV('inventory_by_category.csv', 'categoryTable')" class="btn btn-sm">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
                
                <table class="table" id="categoryTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Products</th>
                            <th>Quantity</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory_by_category as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['category'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo number_format($category['product_count']); ?></td>
                            <td><?php echo number_format($category['total_quantity']); ?></td>
                            <td>$<?php echo number_format($category['value'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Stock Movement History -->
            <div class="report-card">
                <div class="report-header">
                    <h2><i class="fas fa-history" style="color: #10b981;"></i> Stock Movement History</h2>
                    <button onclick="exportTableToCSV('stock_movements.csv', 'movementsTable')" class="btn btn-sm">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                
                <div class="chart-container">
                    <canvas id="movementsChart"></canvas>
                </div>
                
                <table class="table" id="movementsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>User</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_movements as $movement): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($movement['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($movement['product_name']); ?> (<?php echo htmlspecialchars($movement['sku']); ?>)</td>
                            <td>
                                <span class="movement-type movement-<?php echo $movement['movement_type']; ?>">
                                    <?php echo ucfirst($movement['movement_type']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($movement['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($movement['username']); ?></td>
                            <td><?php echo htmlspecialchars($movement['reason']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    // Prepare data for charts
    document.addEventListener('DOMContentLoaded', function() {
        // Category Chart
        const categoryData = {
            labels: [<?php echo implode(', ', array_map(function($item) { 
                return '"' . ($item['category'] ?? 'Uncategorized') . '"'; 
            }, $inventory_by_category)); ?>],
            datasets: [{
                label: 'Inventory Value ($)',
                data: [<?php echo implode(', ', array_map(function($item) { 
                    return $item['value']; 
                }, $inventory_by_category)); ?>],
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                    '#ec4899', '#06b6d4', '#14b8a6', '#f97316', '#6366f1'
                ],
                borderWidth: 1
            }]
        };

        const categoryChart = new Chart(
            document.getElementById('categoryChart'),
            {
                type: 'pie',
                data: categoryData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += new Intl.NumberFormat('en-US', { 
                                            style: 'currency', 
                                            currency: 'USD' 
                                        }).format(context.parsed);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            }
        );
        
        // Process movement data for chart
        const movementDates = {};
        const inData = {};
        const outData = {};
        
        <?php 
        $movement_dates = [];
        foreach ($stock_movements as $movement) {
            $date = date('Y-m-d', strtotime($movement['created_at']));
            if (!in_array($date, $movement_dates)) {
                $movement_dates[] = $date;
            }
        }
        sort($movement_dates);
        ?>
        
        // Initialize data structure
        <?php foreach ($movement_dates as $date): ?>
        movementDates['<?php echo $date; ?>'] = '<?php echo $date; ?>';
        inData['<?php echo $date; ?>'] = 0;
        outData['<?php echo $date; ?>'] = 0;
        <?php endforeach; ?>
        
        // Populate with actual data
        <?php foreach ($stock_movements as $movement): ?>
        <?php $date = date('Y-m-d', strtotime($movement['created_at'])); ?>
        <?php if ($movement['movement_type'] == 'in'): ?>
        inData['<?php echo $date; ?>'] += <?php echo $movement['quantity']; ?>;
        <?php elseif ($movement['movement_type'] == 'out'): ?>
        outData['<?php echo $date; ?>'] += <?php echo $movement['quantity']; ?>;
        <?php endif; ?>
        <?php endforeach; ?>
        
        // Create arrays for chart
        const dates = Object.values(movementDates);
        const inValues = Object.values(inData);
        const outValues = Object.values(outData);
        
        // Movements Chart
        const movementsChart = new Chart(
            document.getElementById('movementsChart'),
            {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Stock In',
                            data: inValues,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Stock Out',
                            data: outValues,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            }
        );
    });
    
    // Export table to CSV function
    function exportTableToCSV(filename, tableId) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tr');
        const csv = [];
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                // Get the text content and clean it
                let data = cols[j].textContent.replace(/\s+/g, ' ').trim();
                // Escape double quotes and wrap in quotes
                data = '"' + data.replace(/"/g, '""') + '"';
                row.push(data);
            }
            
            csv.push(row.join(','));
        }
        
        // Download CSV file
        downloadCSV(csv.join('\n'), filename);
    }
    
    function downloadCSV(csv, filename) {
        const csvFile = new Blob([csv], {type: 'text/csv'});
        const downloadLink = document.createElement('a');
        
        // Create a download link
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        
        // Add to DOM, trigger click, and remove
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
    </script>
</body>
</html>