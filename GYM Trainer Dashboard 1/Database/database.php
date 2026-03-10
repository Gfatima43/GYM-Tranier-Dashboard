<?php

class Conn
{
    public static function connect()
    {
        try {
            // Create the PDO connection instance
            $pdo = new PDO(
                "mysql:host=127.0.0.1;dbname=gym_trainer;charset=utf8mb4",
                'root',
                ''
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            // Catch and display any connection errors
            die("Connection failed: " . $e->getMessage());
        }
    }
}
