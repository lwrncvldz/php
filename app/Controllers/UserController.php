<?php
require_once dirname(__FILE__) . '/../Models/User.php';

class UserController {
    private $db;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    // Register
    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->userModel->name = $data['name'] ?? '';
        $this->userModel->email = $data['email'] ?? '';
        $this->userModel->password = $data['password'] ?? '';

        if (!$this->userModel->name || !$this->userModel->email || !$this->userModel->password) {
            return $this->response(['success' => false, 'message' => 'All fields required'], 400);
        }

        $result = $this->userModel->register();
        return $this->response($result);
    }

    // Login
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->userModel->email = $data['email'] ?? '';
        $this->userModel->password = $data['password'] ?? '';

        $result = $this->userModel->login();
        
        if ($result['success']) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_name'] = $result['user']['name'];
            $_SESSION['user_email'] = $result['user']['email'];
            
            unset($result['user']['password']);
            return $this->response(['success' => true, 'user' => $result['user']]);
        }
        
        return $this->response($result, 401);
    }

    // Get current user
    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return $this->response(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $user = $this->userModel->getById($_SESSION['user_id']);
        return $this->response(['success' => true, 'user' => $user]);
    }

    // Logout
    public function logout() {
        session_destroy();
        return $this->response(['success' => true, 'message' => 'Logged out']);
    }

    // Update profile
    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            return $this->response(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->userModel->id = $_SESSION['user_id'];
        $this->userModel->name = $data['name'] ?? '';

        if (!$this->userModel->name) {
            return $this->response(['success' => false, 'message' => 'Name required'], 400);
        }

        if ($this->userModel->updateProfile()) {
            $_SESSION['user_name'] = $this->userModel->name;
            return $this->response(['success' => true, 'message' => 'Profile updated']);
        }
        
        return $this->response(['success' => false, 'message' => 'Update failed'], 400);
    }

    private function response($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
