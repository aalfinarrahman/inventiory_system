<?php
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header("Location: modules/dashboard/index.php");
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $error = 'Username or email is required';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } else {
        if ($auth->login($username, $password)) {
            header("Location: modules/dashboard/index.php");
            exit();
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .login-header .logo {
            font-size: 48px;
            color: #007bff;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background: #0056b3;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-cube"></i>
            </div>
            <h1>Inventory System</h1>
            <p>Please sign in to continue</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username or Email
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Enter your username or email"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your password"
                    required
                >
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
    </div>
</body>
</html>