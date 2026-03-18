<?php
require_once dirname(__FILE__) . '/../Models/User.php';

class AdminController {
    private $db;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
        $this->checkAdmin();
    }

    private function checkAdmin() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $this->response(['success' => false, 'message' => 'Unauthorized Admin Access'], 403);
            exit;
        }
    }

    public function getDashboardStats() {
        try {
            // Get total users
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM users");
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get total platform expenses
            $stmt = $this->db->query("SELECT SUM(amount) as total FROM expenses");
            $totalExpenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Get total verified users
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND email_verified = 1");
            $verifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            return $this->response([
                'success' => true,
                'stats' => [
                    'totalUsers' => (int)$totalUsers,
                    'totalExpenses' => (float)$totalExpenses,
                    'verifiedUsers' => (int)$verifiedUsers
                ]
            ]);
        } catch (PDOException $e) {
            return $this->response(['success' => false, 'message' => 'Database error'], 500);
        }
    }

    public function getAllUsers() {
        try {
            $query = "
                SELECT u.id, u.name, u.email, u.email_verified, u.created_at, u.role,
                       COUNT(e.id) as expense_count,
                       COALESCE(SUM(e.amount), 0) as total_spent
                FROM users u
                LEFT JOIN expenses e ON u.id = e.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC
            ";
            
            $stmt = $this->db->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(['success' => true, 'users' => $users]);
        } catch (PDOException $e) {
            return $this->response(['success' => false, 'message' => 'Database error'], 500);
        }
    }

    public function deleteUser($id) {
        if ($id == $_SESSION['user_id']) {
            return $this->response(['success' => false, 'message' => 'Cannot delete your own admin account'], 400);
        }

        try {
            // Foreign key CASCADE will handle expenses, budgets, etc.
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return $this->response(['success' => true, 'message' => 'User deleted successfully']);
            }
            return $this->response(['success' => false, 'message' => 'Failed to delete user'], 400);
        } catch (PDOException $e) {
            return $this->response(['success' => false, 'message' => 'Database error'], 500);
        }
    }

    private function response($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
