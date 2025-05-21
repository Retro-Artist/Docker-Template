<?php

/**
 * Simple PHP Initialization
 * A minimal Docker environment for PHP development
 */

// Setup database connection and fetch necessary data
function setupDatabaseConnection()
{
    // Config
    $config = [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'dbname' => getenv('DB_DATABASE') ?: 'simple_php',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: 'root_password',
    ];

    $result = [
        'connection' => null,
        'connected' => false,
        'error' => null,
        'notes' => [],
        'tableExists' => false,
        'mysqlVersion' => null
    ];

    try {
        // Try to connect (max 3 attempts)
        for ($i = 0; $i < 3; $i++) {
            try {
                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']}";
                $result['connection'] = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $result['connected'] = true;

                // Check for notes table and fetch data
                if ($result['connection']->query("SHOW TABLES LIKE 'notes'")->rowCount() > 0) {
                    $result['tableExists'] = true;
                    $result['notes'] = $result['connection']->query("SELECT * FROM notes ORDER BY created_at DESC")
                        ->fetchAll(PDO::FETCH_ASSOC);
                }

                // Get MySQL version
                $result['mysqlVersion'] = $result['connection']->query('SELECT version()')->fetchColumn();
                break;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Unknown database') !== false) {
                    $result['error'] = "Database \"{$config['dbname']}\" does not exist yet!";
                    break;
                }

                if ($i === 2) throw $e; // Last attempt failed
                sleep(1); // Wait before retry
            }
        }
    } catch (PDOException $e) {
        $result['error'] = 'Database connection failed: ' . $e->getMessage();
    }

    return $result;
}

// Get database connection and data
$db = setupDatabaseConnection();

// Display basic information about the environment
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple PHP Initialization</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="max-w-5xl mx-auto p-6">
        <header class="pb-4 border-b border-gray-200 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Simple PHP Initialization</h1>
            <p class="text-lg text-gray-600">Your PHP <?= phpversion() ?> environment is ready!</p>
        </header>

        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <!-- Environment Info -->
            <div class="bg-white rounded shadow">
                <div class="bg-blue-600 text-white p-3">Environment Information</div>
                <ul class="divide-y divide-gray-100 p-0">
                    <li class="p-3">PHP Version: <?= phpversion() ?></li>
                    <li class="p-3">Web Server: <?= $_SERVER['SERVER_SOFTWARE'] ?></li>
                    <li class="p-3">Document Root: <?= $_SERVER['DOCUMENT_ROOT'] ?></li>
                    <li class="p-3">Server Protocol: <?= $_SERVER['SERVER_PROTOCOL'] ?></li>
                </ul>
            </div>

            <!-- Database Connection -->
            <div class="bg-white rounded shadow">
                <div class="bg-green-600 text-white p-3">Database Connection</div>
                <div class="p-4">
                    <?php if ($db['connected']): ?>
                        <div class="bg-green-100 border-green-400 text-green-700 p-3 rounded mb-3">
                            Database connection successful!
                        </div>
                        <p>Connected to database: <strong><?= htmlspecialchars($db['dbname'] ?? 'simple_php') ?></strong></p>
                        <?php if ($db['mysqlVersion']): ?>
                            <p>MySQL version: <strong><?= htmlspecialchars($db['mysqlVersion']) ?></strong></p>
                        <?php endif; ?>
                    <?php elseif (isset($db['error']) && strpos($db['error'], 'does not exist yet') !== false): ?>
                        <div class="bg-red-100 text-red-700 p-3 rounded mb-3">
                            <?= htmlspecialchars($db['error']) ?>
                        </div>
                        <div class="bg-yellow-50 text-yellow-700 p-3 rounded">
                            <h4 class="font-bold mb-2">Database Needs Initialization</h4>
                            <p>Run the migration script or import the SQL file manually.</p>

                            <div class="bg-white border rounded mt-3 mb-3">
                                <div class="p-2 bg-gray-50 border-b font-medium">Option 1: Migration Script</div>
                                <div class="p-3">
                                    <div class="bg-gray-800 text-white p-2 text-sm rounded">
                                        docker-compose exec app php database/migrate.php
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white border rounded">
                                <div class="p-2 bg-gray-50 border-b font-medium">Option 2: Manual Import</div>
                                <div class="p-3">
                                    <ol class="list-decimal pl-4 text-sm">
                                        <li>Access phpMyAdmin at <a href="http://localhost:8081" class="text-blue-600 hover:underline">localhost:8081</a></li>
                                        <li>Select the database and import <code class="bg-gray-100 px-1">database/database.sql</code></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-red-100 text-red-700 p-3 rounded">
                            <?= htmlspecialchars($db['error']) ?>
                        </div>
                        <p class="mt-2">Check your database settings in the .env file</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PHP Extensions -->
        <div class="bg-white rounded shadow mb-6 overflow-hidden">
            <div class="bg-blue-400 text-white p-3">Available PHP Extensions</div>
            <div class="p-4">
                <div class="grid md:grid-cols-3 gap-4">
                    <?php
                    $extensions = get_loaded_extensions();
                    sort($extensions);
                    $chunks = array_chunk($extensions, ceil(count($extensions) / 3));

                    foreach ($chunks as $chunk) {
                        echo '<ul class="divide-y text-sm">';
                        foreach ($chunk as $ext) {
                            echo '<li class="py-1.5">' . htmlspecialchars($ext) . '</li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Sample Notes Display -->
        <?php if ($db['tableExists'] && count($db['notes']) > 0): ?>
            <div class="bg-white rounded shadow mb-6">
                <div class="bg-yellow-400 text-gray-800 p-3">Sample Notes from Database</div>
                <div class="p-4 space-y-3">
                    <?php foreach ($db['notes'] as $note): ?>
                        <div class="border rounded">
                            <div class="bg-gray-50 p-2 border-b font-medium">
                                <?= htmlspecialchars($note['title']) ?>
                            </div>
                            <div class="p-3">
                                <p><?= htmlspecialchars($note['content']) ?></p>
                                <div class="text-gray-500 text-xs mt-2">Created: <?= htmlspecialchars($note['created_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif (!$db['tableExists'] && $db['connected']): ?>
            <div class="bg-blue-50 text-blue-700 p-3 mb-6 rounded">
                <h4 class="font-bold mb-1">Database tables missing</h4>
                <p>Initialize your database with the migration script:</p>
                <pre class="bg-gray-800 text-white p-2 mt-1 rounded text-sm">docker-compose exec app php database/migrate.php</pre>
            </div>
        <?php endif; ?>

        <!-- Next Steps Cards -->
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded shadow">
                <div class="bg-gray-800 text-white p-3">Standard Environment Setup</div>
                <div class="p-4">
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Set up database:
                            <ul class="list-disc pl-5 mt-1 text-sm space-y-1">
                                <li>Run <code class="bg-gray-100 px-1">php database/migrate.php</code></li>
                            </ul>
                        </li>
                        <li>Install dependencies: <code class="bg-gray-100 px-1">composer install</code></li>
                        <li>You can delete this page and build your own application in <code class="bg-gray-100 px-1">public/</code></li>
                    </ol>
                </div>
            </div>

            <div class="bg-white rounded shadow">
                <div class="bg-blue-600 text-white p-3">Docker Environment Setup</div>
                <div class="p-4">
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Initialize database: <code class="bg-gray-100 px-1">docker-compose exec app php database/migrate.php</code></li>
                        <li>Access phpMyAdmin: <a href="http://localhost:8081" class="text-blue-600 hover:underline">localhost:8081</a></li>
                        <li>Install dependencies: <code class="bg-gray-100 px-1">docker-compose exec app composer install</code></li>
                        <li>You can delete this page and build your own application in <code class="bg-gray-100 px-1">public/</code></li>
                    </ol>
                </div>
            </div>
        </div>

        <footer class="pt-4 border-t border-gray-200 text-center text-gray-500 text-sm">
            &copy; <?= date('Y') ?> Simple PHP Initialization
        </footer>
    </div>
</body>

</html>