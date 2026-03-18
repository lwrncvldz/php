<?php

class RecurringExpense {
    private $db;
    private $table = 'recurring_expenses';

    public $id;
    public $user_id;
    public $amount;
    public $category;
    public $description;
    public $frequency; // daily, weekly, monthly, yearly
    public $start_date;
    public $end_date;
    public $active;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get all recurring expenses for user
    public function getAll($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY start_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add recurring expense
    public function add() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, amount, category, description, frequency, start_date, end_date, active) 
                  VALUES 
                  (:user_id, :amount, :category, :description, :frequency, :start_date, :end_date, :active)";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':frequency', $this->frequency);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':end_date', $this->end_date);
        $stmt->bindParam(':active', $this->active);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        }
        return ['success' => false];
    }

    // Process recurring expenses (create actual expense entries)
    public function processRecurring() {
        $today = date('Y-m-d');
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE active = 1 
                  AND start_date <= :today 
                  AND (end_date IS NULL OR end_date >= :today)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        
        $recurring = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;

        foreach ($recurring as $rec) {
            if ($this->shouldCreateExpense($rec)) {
                $this->createExpenseFromRecurring($rec);
                $count++;
            }
        }

        return $count;
    }

    private function shouldCreateExpense($recurring) {
        $lastExpenseQuery = "SELECT MAX(date) as last_date FROM expenses 
                            WHERE user_id = :user_id 
                            AND category = :category 
                            AND amount = :amount";
        
        $stmt = $this->db->prepare($lastExpenseQuery);
        $stmt->bindParam(':user_id', $recurring['user_id']);
        $stmt->bindParam(':category', $recurring['category']);
        $stmt->bindParam(':amount', $recurring['amount']);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastDate = $result['last_date'] ? new DateTime($result['last_date']) : null;
        $today = new DateTime();

        if (!$lastDate) {
            return true;
        }

        $interval = $this->getIntervalDays($recurring['frequency']);
        $lastDate->modify("+{$interval} days");
        
        return $lastDate <= $today;
    }

    private function createExpenseFromRecurring($recurring) {
        $query = "INSERT INTO expenses (user_id, amount, category, description, date) 
                  VALUES (:user_id, :amount, :category, :description, :date)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $recurring['user_id']);
        $stmt->bindParam(':amount', $recurring['amount']);
        $stmt->bindParam(':category', $recurring['category']);
        $stmt->bindParam(':description', $recurring['description']);
        
        $date = date('Y-m-d');
        $stmt->bindParam(':date', $date);
        
        return $stmt->execute();
    }

    private function getIntervalDays($frequency) {
        $intervals = [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'yearly' => 365
        ];
        return $intervals[$frequency] ?? 30;
    }

    // Update recurring expense
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET amount = :amount, category = :category, 
                      description = :description, frequency = :frequency,
                      end_date = :end_date, active = :active
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':frequency', $this->frequency);
        $stmt->bindParam(':end_date', $this->end_date);
        $stmt->bindParam(':active', $this->active);
        
        return $stmt->execute();
    }

    // Delete recurring expense
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
