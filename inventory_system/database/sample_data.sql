-- Insert sample users
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@inventory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('manager', 'manager@inventory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('staff', 'staff@inventory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and gadgets'),
('Clothing', 'Apparel and fashion items'),
('Books', 'Books and educational materials'),
('Home & Garden', 'Home improvement and garden supplies');

-- Insert sample products
INSERT INTO products (name, description, category_id, sku, price, cost, min_stock_level) VALUES
('Laptop HP Pavilion', 'HP Pavilion 15-inch laptop', 1, 'HP-PAV-001', 899.99, 650.00, 5),
('T-Shirt Cotton', 'Basic cotton t-shirt', 2, 'TS-COT-001', 19.99, 8.50, 50),
('Programming Book', 'Learn PHP Programming', 3, 'BK-PHP-001', 49.99, 25.00, 10),
('Garden Hose', '50ft garden hose', 4, 'GH-50FT-001', 29.99, 15.00, 20);

-- Insert sample stock
INSERT INTO stock (product_id, quantity, location) VALUES
(1, 15, 'Warehouse A'),
(2, 100, 'Warehouse A'),
(3, 25, 'Warehouse B'),
(4, 30, 'Warehouse A');

-- Insert sample suppliers
INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES
('Tech Supplies Inc', 'John Smith', 'john@techsupplies.com', '+1234567890', '123 Tech Street'),
('Fashion Wholesale', 'Jane Doe', 'jane@fashionwholesale.com', '+1234567891', '456 Fashion Ave'),
('Book Distributors', 'Mike Johnson', 'mike@bookdist.com', '+1234567892', '789 Book Lane');