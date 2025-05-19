<?php

/**
 * Simple PHP Initialization
 * A minimal Docker environment for PHP 8.3 development
 */

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
                        <?php
                        try {
                            $host = getenv('DB_HOST') ?: 'mysql';
                            $dbname = getenv('DB_DATABASE') ?: 'simple_php';
                            $username = getenv('DB_USERNAME') ?: 'root';
                            $password = getenv('DB_PASSWORD') ?: 'root_password';

                            $connected = false;
                            $attempts = 0;
                            $maxAttempts = 3;

                            while (!$connected && $attempts < $maxAttempts) {
                                try {
                                    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    $connected = true;
                                    echo '<div class="alert alert-success">Database connection successful!</div>';
                                    echo '<p>Connected to database: <strong>' . $dbname . '</strong></p>';
                                    echo '<p>MySQL version: <strong>' . $pdo->query('select version()')->fetchColumn() . '</strong></p>';
                                } catch (PDOException $e) {
                                    $attempts++;
                                    // Check for "unknown database" error specifically
                                    if (strpos($e->getMessage(), 'Unknown database') !== false) {
                                        echo '<div class="alert alert-warning">Database "' . $dbname . '" does not exist yet!</div>';
                                        echo '<div class="alert alert-info">';
                                        echo '<h4 class="alert-heading">Database Needs Initialization</h4>';
                                        echo '<p>Run the database migration script to create the database and tables:</p>';
                                        echo '<pre>docker-compose exec app php database/migrate.php</pre>';
                                        echo '</div>';
                                        return; // Exit the loop immediately
                                    }

                                    if ($attempts >= $maxAttempts) {
                                        throw $e;
                                    }
                                    sleep(1); // Wait 1 second before retrying
                                }
                            }
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-danger">Database connection failed: ' . $e->getMessage() . '</div>';
                            echo '<p>Check your database connection settings in the .env file or ensure the MySQL service is running.</p>';
                        }

                        ?>
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
                            echo '<li class="list-group-item">' . $ext . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php
        // Check if the notes table exists and display notes if it does
        try {
            $host = getenv('DB_HOST') ?: 'mysql';
            $dbname = getenv('DB_DATABASE') ?: 'simple_php';
            $username = getenv('DB_USERNAME') ?: 'root';
            $password = getenv('DB_PASSWORD') ?: 'root_password';

            $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if the notes table exists
            $tableExists = false;
            $stmt = $pdo->query("SHOW TABLES LIKE 'notes'");
            if ($stmt->rowCount() > 0) {
                $tableExists = true;
            }

            if ($tableExists) {
                // Fetch notes
                $stmt = $pdo->query("SELECT * FROM notes ORDER BY created_at DESC");
                $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($notes) > 0) {
                    echo '<div class="card mb-4">';
                    echo '<div class="card-header bg-warning text-dark">';
                    echo 'Sample Notes from Database';
                    echo '</div>';
                    echo '<div class="card-body">';

                    foreach ($notes as $note) {
                        echo "<div class='card mb-3'>";
                        echo "<div class='card-header'>" . htmlspecialchars($note['title']) . "</div>";
                        echo "<div class='card-body'>";
                        echo "<p class='card-text'>" . htmlspecialchars($note['content']) . "</p>";
                        echo "<div class='text-muted'>Created: " . $note['created_at'] . "</div>";
                        echo "</div></div>";
                    }

                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="alert alert-info">';
                echo '<h4 class="alert-heading">Database Initialization</h4>';
                echo '<p>Run the database migration script to create sample tables:</p>';
                echo '<pre>docker-compose exec app php database/migrate.php</pre>';
                echo '</div>';
            }
        } catch (Exception $e) {
            // Silently ignore errors - maybe the database isn't ready yet
        }
        ?>

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        Next Steps
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Start building your PHP application in the <code>public/</code> directory</li>
                            <li>Run the database migration script: <code>docker-compose exec app php database/migrate.php</code></li>
                            <li>Access phpMyAdmin at <a href="http://localhost:8081" target="_blank">http://localhost:8081</a></li>
                            <li>Install packages with Composer: <code>docker-compose exec app composer require package-name</code></li>
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