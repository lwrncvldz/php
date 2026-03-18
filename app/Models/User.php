<?php

class User {
    private $db;
    private $table = 'users';

    public $id;
    public $name;
    public $email;
    public $password;
    public $verificationToken;

    public function __construct($db) {
        $this->db = $db;
    }

    // Register new user
    public function register() {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        if (!$this->hasValidEmailDomain($this->email)) {
            return ['success' => false, 'message' => 'Email domain is not valid'];
        }

        $this->verificationToken = bin2hex(random_bytes(32));
        $verificationTokenHash = hash('sha256', $this->verificationToken);
        $verificationExpiresAt = date('Y-m-d H:i:s', time() + 86400);

        $query = "INSERT INTO " . $this->table . " 
                  (name, email, password, email_verified, verification_token, verification_expires_at) 
                  VALUES 
                  (:name, :email, :password, 0, :verification_token, :verification_expires_at)";
        
        $stmt = $this->db->prepare($query);
        
        $hashedPassword = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':verification_token', $verificationTokenHash);
        $stmt->bindParam(':verification_expires_at', $verificationExpiresAt);
        
        try {
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'id' => $this->db->lastInsertId(),
                    'verification_token' => $this->verificationToken
                ];
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            return ['success' => false, 'message' => 'Registration failed'];
        }

        return ['success' => false, 'message' => 'Registration failed'];
    }

    // Login user
    public function login() {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($this->password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if (isset($user['email_verified']) && (int)$user['email_verified'] !== 1) {
            return [
                'success' => false,
                'requires_verification' => true,
                'message' => 'Please verify your email first. Check your inbox for the activation link.',
                'email' => $user['email']
            ];
        }

        if ($user) {
            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public function verifyEmail($token) {
        if (empty($token)) {
            return ['success' => false, 'message' => 'Invalid verification token'];
        }

        $tokenHash = hash('sha256', $token);

        $query = "SELECT id FROM " . $this->table . "
                  WHERE verification_token = :token
                  AND email_verified = 0
                  AND verification_expires_at > NOW()
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':token', $tokenHash);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired activation link'];
        }

        $updateQuery = "UPDATE " . $this->table . "
                        SET email_verified = 1,
                            verified_at = NOW(),
                            verification_token = NULL,
                            verification_expires_at = NULL
                        WHERE id = :id";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->bindParam(':id', $user['id']);

        if ($updateStmt->execute()) {
            return ['success' => true, 'message' => 'Email verified successfully. You can now log in.'];
        }

        return ['success' => false, 'message' => 'Email verification failed'];
    }

    public function regenerateVerificationTokenByEmail($email) {
        $query = "SELECT id, name, email, email_verified FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => true, 'message' => 'If the account exists, a new activation link has been sent.'];
        }

        if ((int)$user['email_verified'] === 1) {
            return ['success' => true, 'already_verified' => true, 'message' => 'Email is already verified. You can log in now.'];
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);

        $updateQuery = "UPDATE " . $this->table . "
                        SET verification_token = :token,
                            verification_expires_at = :expires_at
                        WHERE id = :id";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->bindParam(':token', $tokenHash);
        $updateStmt->bindParam(':expires_at', $expiresAt);
        $updateStmt->bindParam(':id', $user['id']);

        if (!$updateStmt->execute()) {
            return ['success' => false, 'message' => 'Could not create a new activation link'];
        }

        return [
            'success' => true,
            'name' => $user['name'],
            'email' => $user['email'],
            'verification_token' => $plainToken,
            'message' => 'A new activation link has been sent.'
        ];
    }

    public function markEmailVerifiedByEmail($email) {
        $query = "SELECT id, email_verified FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        if ((int)$user['email_verified'] === 1) {
            return ['success' => true, 'message' => 'User is already verified'];
        }

        $updateQuery = "UPDATE " . $this->table . "
                        SET email_verified = 1,
                            verified_at = NOW(),
                            verification_token = NULL,
                            verification_expires_at = NULL
                        WHERE id = :id";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->bindParam(':id', $user['id']);

        if ($updateStmt->execute()) {
            return ['success' => true, 'message' => 'User manually verified'];
        }

        return ['success' => false, 'message' => 'Failed to verify user'];
    }

    // Get user by ID
    public function getById($id) {
        $query = "SELECT id, name, email, email_verified, created_at FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update user profile
    public function updateProfile() {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name', $this->name);
        
        return $stmt->execute();
    }

    private function hasValidEmailDomain($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        $domain = $parts[1];

        if (!function_exists('checkdnsrr')) {
            return true;
        }

        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }
}
