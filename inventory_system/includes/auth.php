<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
        
        // Tentukan path yang benar ke login.php
        $login_path = $_SERVER['REQUEST_URI'];
        if (strpos($login_path, '/modules/') !== false) {
            // Jika dipanggil dari dalam folder modules
            header("Location: ../../login.php");
        } else {
            // Jika dipanggil dari root directory
            header("Location: login.php");
        }
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
    
    public function hasRole($required_role) {
        $roles = ['staff' => 1, 'manager' => 2, 'admin' => 3];
        $user_level = $roles[$_SESSION['role']] ?? 0;
        $required_level = $roles[$required_role] ?? 0;
        return $user_level >= $required_level;
    }
    
    public function requireRole($required_role) {
        $this->requireLogin();
        if (!$this->hasRole($required_role)) {
            header("Location: dashboard.php?error=insufficient_permissions");
            exit();
        }
    }
}
?>