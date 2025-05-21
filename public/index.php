<?php

/**
 * Simple PHP Initialization
 * A minimal Docker environment for PHP 8.3 development
 */

// Centralized database connection
$dbConnection = null;
$dbConnected = false;
$dbError = null;
$notes = [];
$tableExists = false;

try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_DATABASE') ?: 'simple_php';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: 'root_password';

    $connected = false;
    $attempts = 0;
    $maxAttempts = 3;

    while (!$connected && $attempts < $maxAttempts) {
        try {
            $dbConnection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connected = true;
            $dbConnected = true;

            // Check if the notes table exists
            $stmt = $dbConnection->query("SHOW TABLES LIKE 'notes'");
            if ($stmt->rowCount() > 0) {
                $tableExists = true;

                // Fetch notes
                $stmt = $dbConnection->query("SELECT * FROM notes ORDER BY created_at DESC");
                $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $attempts++;
            // Check for "unknown database" error specifically
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $dbError = 'Database "' . $dbname . '" does not exist yet!';
                break; // Exit the loop immediately
            }

            if ($attempts >= $maxAttempts) {
                throw $e;
            }
            sleep(1); // Wait 1 second before retrying
        }
    }
} catch (PDOException $e) {
    $dbError = 'Database connection failed: ' . $e->getMessage();
}

// Get MySQL version if connected
$mysqlVersion = null;
if ($dbConnected) {
    try {
        $mysqlVersion = $dbConnection->query('select version()')->fetchColumn();
    } catch (Exception $e) {
        // Ignore error
    }
}

// Display basic information about the environment
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple PHP Initialization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .header {
            padding-bottom: 1rem;
            border-bottom: .05rem solid #e5e5e5;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <h1>Simple PHP Initialization</h1>
            <p class="lead">Your PHP <?= phpversion() ?> environment is ready!</p>
        </header>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        Environment Information
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">PHP Version: <?= phpversion() ?></li>
                            <li class="list-group-item">Web Server: <?= $_SERVER['SERVER_SOFTWARE'] ?></li>
                            <li class="list-group-item">Document Root: <?= $_SERVER['DOCUMENT_ROOT'] ?></li>
                            <li class="list-group-item">Server Protocol: <?= $_SERVER['SERVER_PROTOCOL'] ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        Database Connection
                    </div>
                    <div class="card-body">
                        <?php if ($dbConnected): ?>
                            <div class="alert alert-success">Database connection successful!</div>
                            <p>Connected to database: <strong><?= htmlspecialchars($dbname) ?></strong></p>
                            <?php if ($mysqlVersion): ?>
                                <p>MySQL version: <strong><?= htmlspecialchars($mysqlVersion) ?></strong></p>
                            <?php endif; ?>
                        <?php elseif (isset($dbError) && strpos($dbError, 'does not exist yet') !== false): ?>
                            <div class="alert alert-warning"><?= htmlspecialchars($dbError) ?></div>
                            <div class="alert alert-info">
                                <h4 class="alert-heading">Database Needs Initialization</h4>
                                <p>Your database exists but needs to be initialized with tables and sample data. You have two options:</p>

                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Option 1: Run the Migration Script</strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2">This will automatically create tables and insert sample data.</p>

                                        <div class="mb-2">
                                            <span class="badge bg-secondary">For Non-Docker users:</span>
                                            <div class="bg-dark text-light p-2 mt-1 rounded">
                                                <code>php database/migrate.php</code>
                                            </div>
                                        </div>

                                        <div>
                                            <span class="badge bg-secondary">For Docker users:</span>
                                            <div class="bg-dark text-light p-2 mt-1 rounded">
                                                <code>docker-compose exec app php database/migrate.php</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Option 2: Manual Import</strong>
                                    </div>
                                    <div class="card-body">
                                        <p>Alternatively, you can manually import the SQL file:</p>
                                        <ol class="mb-0">
                                            <li>Go to phpMyAdmin at <a href="http://localhost:8081" target="_blank">http://localhost:8081</a> (for Docker users)</li>
                                            <li>Select the <code><?= htmlspecialchars($dbname) ?></code> database</li>
                                            <li>Click on the "Import" tab at the top</li>
                                            <li>Choose the file <code>database/database.sql</code></li>
                                            <li>Click "Go" to import the database structure and sample data</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($dbError) ?></div>
                            <p>Check your database connection settings in the .env file or ensure the MySQL service is running.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                Available PHP Extensions
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $extensions = get_loaded_extensions();
                    sort($extensions);
                    $chunks = array_chunk($extensions, ceil(count($extensions) / 3));

                    foreach ($chunks as $chunk) {
                        echo '<div class="col-md-4">';
                        echo '<ul class="list-group list-group-flush">';
                        foreach ($chunk as $ext) {
                            echo '<li class="list-group-item">' . htmlspecialchars($ext) . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php if ($tableExists && count($notes) > 0): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    Sample Data from Database
                </div>
                <div class="card-body">
                    <?php foreach ($notes as $note): ?>
                        <div class='card mb-3'>
                            <div class='card-header'><?= htmlspecialchars($note['title']) ?></div>
                            <div class='card-body'>
                                <p class='card-text'><?= htmlspecialchars($note['content']) ?></p>
                                <div class='text-muted'>Created: <?= htmlspecialchars($note['created_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif (!$tableExists && $dbConnected): ?>
            <div class="alert alert-info">
                <h4 class="alert-heading">Database Initialization</h4>
                <p>Run the database migration script to create sample tables:</p>
                <pre>docker-compose exec app php database/migrate.php</pre>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <i class="bi bi-laptop"></i> Next Steps (Standard Environment)
                    </div>
                    <div class="card-body">
                        <ol class="ps-4">
                            <li class="mb-2">Set up your database:
                                <ul class="mt-1">
                                    <li>Import <code>database/database.sql</code> manually via phpMyAdmin, or</li>
                                    <li>Run <code>php database/migrate.php</code></li>
                                </ul>
                            </li>
                            <li class="mb-2">Install dependencies: <code>composer install</code></li>
                            <li class="mb-2">Start building your PHP application in the <code>public/</code> directory</li>
                            <li>Structure your application any way you like</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-docker"></i> Next Steps (Docker Environment)
                    </div>
                    <div class="card-body">
                        <ol class="ps-4">
                            <li class="mb-2">Initialize your database: <code>docker-compose exec app php database/migrate.php</code></li>
                            <li class="mb-2">Access database admin at <a href="http://localhost:8081" target="_blank" class="text-decoration-none">http://localhost:8081</a>
                                <small class="text-muted">(username: root, password: root_password)</small>
                            </li>
                            <li class="mb-2">Install dependencies: <code>docker-compose exec app composer install</code></li>
                            <li class="mb-2">Start building your PHP application in the <code>public/</code> directory</li>
                            <li>Structure your application any way you like</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <footer class="pt-4 my-md-5 pt-md-5 border-top">
            <div class="row">
                <div class="col-12 col-md">
                    <small class="d-block mb-3 text-muted">&copy; <?= date('Y') ?> Simple PHP Initialization</small>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>