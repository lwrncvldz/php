<?php

class Budget {
    private $db;
    private $table = 'budgets';

    public $id;
    public $user_id;
    public $category;
    public $limit;
    public $created_at;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get all budgets for user
    public function getAll($user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add budget
    public function add() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, category, limit) 
                  VALUES 
                  (:user_id, :category, :limit)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':limit', $this->limit);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        }
        return ['success' => false];
    }

    // Update budget
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET limit = :limit 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':limit', $this->limit);
        
        return $stmt->execute();
    }

    // Delete budget
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
