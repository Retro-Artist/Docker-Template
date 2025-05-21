<?php
/**
 * Simple PHP Initialization - Database Migration Script
 * 
 * This script creates a basic database structure and adds sample data.
 * Run it with: docker-compose exec app php database/migrate.php
 */

// Connect to the database using environment variables or default values
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_DATABASE') ?: 'simple_php';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root_password';

try {
    // Create database connection
    echo "Connecting to MySQL...\n";
    
    // Connect without database first (in case the database doesn't exist yet)
    $pdo = new PDO("mysql:host={$host}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Create database if it doesn't exist
    echo "Creating database '{$dbname}' if it doesn't exist...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");
    
    echo "Database connection established successfully!\n";
    
    // Create sample tables
    echo "Creating sample tables...\n";
    
    // Example table: Simple notes table
    $pdo->exec("DROP TABLE IF EXISTS notes");
    $pdo->exec("
        CREATE TABLE notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "- Created 'notes' table\n";
    
    // Insert sample data
    echo "Inserting sample data...\n";
    $pdo->exec("
        INSERT INTO notes (title, content) VALUES
        ('Welcome to Simple PHP Initialization', 'This is a sample note created by the database migration script.'),
        ('Getting Started', 'Edit the public/index.php file to begin building your PHP application.'),
        ('Database Connections', 'Use PDO to connect to MySQL from your PHP scripts.')
    ");
    echo "- Inserted sample data into 'notes' table\n";
    
    echo "\nDatabase migration completed successfully!\n";
    echo "If you're running docker on your local machine you access phpMyAdmin at: http://localhost:8081\n";
    echo "Your PHPMyAdmin Access credentials on Docker are: Username: root, Password: root_password\n\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}