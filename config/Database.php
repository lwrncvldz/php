<?php

class Database {
    private $host = 'localhost';
    private $dbName = 'expense_tracker';
    private $dbUser = 'root';
    private $dbPassword = '';
    private $connection;

    public function connect() {
        $this->connection = null;

        try {
            $this->connection = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->dbName,
                $this->dbUser,
                $this->dbPassword
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->connection;
    }
}
