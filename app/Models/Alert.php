<?php

class Alert {
    private $db;
    private $table = 'alerts';

    public $id;
    public $user_id;
    public $type; // budget_exceeded, approaching_budget
    public $category;
    public $amount;
    public $budget_limit;
    public $message;
    public $read;
    public $created_at;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get all alerts for user
    public function getAll($user_id, $unreadOnly = false) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
        
        if ($unreadOnly) {
            $query .= " AND read = 0";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Create alert
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, type, category, amount, budget_limit, message, read) 
                  VALUES 
                  (:user_id, :type, :category, :amount, :budget_limit, :message, :read)";
        
        $stmt = $this->db->prepare($query);
        
        $read = 0;
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':budget_limit', $this->budget_limit);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':read', $read);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        }
        return ['success' => false];
    }

    // Mark as read
    public function markAsRead($id) {
        $query = "UPDATE " . $this->table . " SET read = 1 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Mark all as read
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table . " SET read = 1 WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    // Check budgets and create alerts
    public function checkBudgets($user_id) {
        $budgetQuery = "SELECT * FROM budgets WHERE user_id = :user_id";
        $stmt = $this->db->prepare($budgetQuery);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $month = date('Y-m');
        $alertsCreated = 0;

        foreach ($budgets as $budget) {
            $expenseQuery = "SELECT SUM(amount) as total FROM expenses 
                           WHERE user_id = :user_id 
                           AND category = :category 
                           AND DATE_FORMAT(date, '%Y-%m') = :month";
            
            $expenseStmt = $this->db->prepare($expenseQuery);
            $expenseStmt->bindParam(':user_id', $user_id);
            $expenseStmt->bindParam(':category', $budget['category']);
            $expenseStmt->bindParam(':month', $month);
            $expenseStmt->execute();
            
            $result = $expenseStmt->fetch(PDO::FETCH_ASSOC);
            $spent = $result['total'] ?? 0;

            // Check if budget exceeded
            if ($spent > $budget['limit']) {
                $this->createBudgetAlert($user_id, $budget, $spent, 'budget_exceeded');
                $alertsCreated++;
            }
            // Check if approaching budget (80%)
            elseif ($spent >= ($budget['limit'] * 0.8)) {
                $this->createBudgetAlert($user_id, $budget, $spent, 'approaching_budget');
                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    private function createBudgetAlert($user_id, $budget, $spent, $type) {
        // Check if alert already exists for this budget this month
        $month = date('Y-m');
        $checkQuery = "SELECT id FROM " . $this->table . " 
                      WHERE user_id = :user_id 
                      AND type = :type 
                      AND category = :category 
                      AND DATE_FORMAT(created_at, '%Y-%m') = :month";
        
        $stmt = $this->db->prepare($checkQuery);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':category', $budget['category']);
        $stmt->bindParam(':month', $month);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return; // Alert already exists
        }

        // Create new alert
        $this->user_id = $user_id;
        $this->type = $type;
        $this->category = $budget['category'];
        $this->amount = $spent;
        $this->budget_limit = $budget['limit'];
        
        if ($type === 'budget_exceeded') {
            $this->message = "Budget exceeded for {$budget['category']}! Spent \${$spent} of \${$budget['limit']}";
        } else {
            $this->message = "Approaching budget for {$budget['category']}: \${$spent} of \${$budget['limit']}";
        }
        
        $this->create();
    }

    // Delete alert
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
