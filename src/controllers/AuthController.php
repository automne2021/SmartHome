<?php
class AuthController
{
    private $db;

    public function __construct()
    {
        require_once dirname(__FILE__) . '/../../src/config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function authenticateUser($username, $password)
    {
        // Validate input
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }

        // Get user from database
        $query = "SELECT id, username, password FROM users WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;

            return ['success' => true, 'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ]];
        } else {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
    }

    public function isLoggedIn()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function logout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        session_destroy();

        return true;
    }
}
