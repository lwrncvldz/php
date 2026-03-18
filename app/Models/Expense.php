<?php

class Expense {
    private $db;
    private $table = 'expenses';

    public $id;
    public $user_id;
    public $amount;
    public $category;
    public $description;
    public $date;
    public $created_at;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get all expenses for a user
    public function getAll($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single expense
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add expense
    public function add() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, amount, category, description, date) 
                  VALUES 
                  (:user_id, :amount, :category, :description, :date)";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':date', $this->date);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        }
        return ['success' => false];
    }

    // Update expense
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET amount = :amount, category = :category, 
                      description = :description, date = :date 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':amount', $this->amount);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':date', $this->date);
        
        return $stmt->execute();
    }

    // Delete expense
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Get total by category
    public function getTotalByCategory($user_id) {
        $query = "SELECT category, SUM(amount) as total FROM " . $this->table . 
                 " WHERE user_id = :user_id GROUP BY category";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
