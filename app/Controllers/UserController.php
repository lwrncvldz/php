<?php
require_once dirname(__FILE__) . '/../Models/User.php';
require_once dirname(__FILE__) . '/../../config/Mailer.php';

class UserController {
    private $db;
    private $userModel;
    private $mailer;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
        $this->mailer = new Mailer();
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

        if (!$result['success']) {
            return $this->response($result, 400);
        }

        $mailResult = $this->mailer->sendVerificationEmail(
            $this->userModel->email,
            $this->userModel->name,
            $result['verification_token']
        );

        $response = [
            'success' => true,
            'message' => 'Account created. Please check your email and activate your account before logging in.',
            'emailSent' => $mailResult['success']
        ];

        if (!$mailResult['success']) {
            $response['message'] = 'Account created, but verification email could not be sent. Please configure mail and request a new activation link.';
            $response['verificationLink'] = $mailResult['activation_link'];
        }

        return $this->response($response, 201);
    }

    // Login
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $this->userModel->email = $data['email'] ?? '';
        $this->userModel->password = $data['password'] ?? '';

        $result = $this->userModel->login();

        if (isset($result['requires_verification']) && $result['requires_verification']) {
            return $this->response($result, 403);
        }
        
        if ($result['success']) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_name'] = $result['user']['name'];
            $_SESSION['user_email'] = $result['user']['email'];
            
            unset($result['user']['password']);
            return $this->response(['success' => true, 'user' => $result['user']]);
        }
        
        return $this->response($result, 401);
    }

    public function verifyEmail() {
        $token = $_GET['token'] ?? '';
        $result = $this->userModel->verifyEmail($token);

        if ($result['success']) {
            return $this->response($result, 200);
        }

        return $this->response($result, 400);
    }

    public function resendVerification() {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            return $this->response(['success' => false, 'message' => 'Email is required'], 400);
        }

        $result = $this->userModel->regenerateVerificationTokenByEmail($email);

        if (!$result['success']) {
            return $this->response($result, 400);
        }

        if (!empty($result['already_verified'])) {
            return $this->response($result, 200);
        }

        if (!empty($result['verification_token'])) {
            $mailResult = $this->mailer->sendVerificationEmail(
                $result['email'],
                $result['name'],
                $result['verification_token']
            );

            if (!$mailResult['success']) {
                return $this->response([
                    'success' => false,
                    'message' => 'Could not resend activation email. Please configure mail and try again.',
                    'verificationLink' => $mailResult['activation_link']
                ], 500);
            }
        }

        return $this->response([
            'success' => true,
            'message' => 'If the account exists and is not yet verified, a new activation link has been sent.'
        ], 200);
    }

    public function adminVerifyUser() {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = trim($data['email'] ?? '');
        $adminKey = trim($data['adminKey'] ?? '');
        $expectedKey = getenv('ADMIN_VERIFY_KEY') ?: '';

        if (empty($expectedKey)) {
            return $this->response([
                'success' => false,
                'message' => 'ADMIN_VERIFY_KEY is not configured on server'
            ], 500);
        }

        if (empty($email) || empty($adminKey)) {
            return $this->response([
                'success' => false,
                'message' => 'Email and adminKey are required'
            ], 400);
        }

        if (!hash_equals($expectedKey, $adminKey)) {
            return $this->response([
                'success' => false,
                'message' => 'Invalid admin key'
            ], 403);
        }

        $result = $this->userModel->markEmailVerifiedByEmail($email);
        return $this->response($result, $result['success'] ? 200 : 404);
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
