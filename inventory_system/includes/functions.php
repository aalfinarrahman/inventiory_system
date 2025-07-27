<?php
require_once __DIR__ . '/../config/database.php';

class InventoryFunctions {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Tambahkan metode getter untuk koneksi
    public function getConnection() {
        return $this->conn;
    }
    
    // Product functions
    public function getAllProducts() {
        $query = "SELECT p.*, c.name as category_name, s.quantity 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 LEFT JOIN stock s ON p.id = s.product_id 
                 ORDER BY p.name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductById($id) {
        $query = "SELECT p.*, c.name as category_name, s.quantity 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 LEFT JOIN stock s ON p.id = s.product_id 
                 WHERE p.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function addProduct($data) {
        $query = "INSERT INTO products (name, description, category_id, sku, price, cost, min_stock_level) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            $data['name'], $data['description'], $data['category_id'],
            $data['sku'], $data['price'], $data['cost'], $data['min_stock_level']
        ]);
        
        if ($result) {
            $product_id = $this->conn->lastInsertId();
            // Initialize stock entry
            $stock_query = "INSERT INTO stock (product_id, quantity) VALUES (?, 0)";
            $stock_stmt = $this->conn->prepare($stock_query);
            $stock_stmt->execute([$product_id]);
        }
        
        return $result;
    }
    
    public function updateProduct($id, $data) {
        $query = "UPDATE products SET name=?, description=?, category_id=?, price=?, cost=?, min_stock_level=? WHERE id=?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'], $data['description'], $data['category_id'],
            $data['price'], $data['cost'], $data['min_stock_level'], $id
        ]);
    }
    
    public function deleteProduct($id) {
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    // Stock functions
    public function updateStock($product_id, $quantity, $movement_type, $reason, $user_id) {
        try {
            $this->conn->beginTransaction();
            
            // Update stock quantity
            if ($movement_type == 'in') {
                $query = "UPDATE stock SET quantity = quantity + ? WHERE product_id = ?";
            } elseif ($movement_type == 'out') {
                $query = "UPDATE stock SET quantity = quantity - ? WHERE product_id = ?";
            } else { // adjustment
                $query = "UPDATE stock SET quantity = ? WHERE product_id = ?";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$quantity, $product_id]);
            
            // Record movement
            $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reason, user_id) 
                             VALUES (?, ?, ?, ?, ?)";
            $movement_stmt = $this->conn->prepare($movement_query);
            $movement_stmt->execute([$product_id, $movement_type, $quantity, $reason, $user_id]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    public function getLowStockProducts() {
        $query = "SELECT p.*, s.quantity, p.min_stock_level 
                 FROM products p 
                 JOIN stock s ON p.id = s.product_id 
                 WHERE s.quantity <= p.min_stock_level 
                 ORDER BY s.quantity ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Category functions
    public function getAllCategories() {
        $query = "SELECT * FROM categories ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Dashboard statistics
    public function getDashboardStats() {
        $stats = [];
        
        // Total products
        $query = "SELECT COUNT(*) as total FROM products";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total stock value
        $query = "SELECT SUM(p.cost * s.quantity) as total_value 
                 FROM products p JOIN stock s ON p.id = s.product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_stock_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
        
        // Low stock count
        $query = "SELECT COUNT(*) as low_stock 
                 FROM products p JOIN stock s ON p.id = s.product_id 
                 WHERE s.quantity <= p.min_stock_level";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['low_stock_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'];
        
        // Recent movements
        $query = "SELECT sm.*, p.name as product_name, u.username 
                 FROM stock_movements sm 
                 JOIN products p ON sm.product_id = p.id 
                 JOIN users u ON sm.user_id = u.id 
                 ORDER BY sm.created_at DESC LIMIT 5";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['recent_movements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
?>