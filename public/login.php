<?php
require_once '../src/controllers/AuthController.php';

// Initialize auth controller
$authController = new AuthController();

// Check if user is already logged in
if ($authController->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $authController->authenticateUser($username, $password);

    if ($result['success']) {
        // Redirect to dashboard after successful login
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartHome - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../src/assets/css/style.css">
    <link rel="stylesheet" href="../src/assets/css/login.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Decorative elements -->
            <div class="login-decoration login-decoration-1"></div>
            <div class="login-decoration login-decoration-2"></div>

            <div class="login-header">
                <i class="fas fa-home"></i>
                <h1>SmartHome</h1>
                <p>Login to your dashboard</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" class="login-form">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                        value="<?php echo htmlspecialchars($username); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="password-input-group">
                        <input type="password" id="password" name="password" class="form-control" required>
                        <button type="button" id="toggle-password" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>

</html>