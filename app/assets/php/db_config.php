<?php

class CreamConnection
{
    private static $envLoaded = false;
    private static $host;
    private static $username;
    private static $password;

    // Function to load environment variables from .env file
    private static function loadEnv($file)
    {
        if (!file_exists($file)) {
            die("Error: .env file not found at $file");
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            die("Error: .env file is empty or unreadable.");
        }

        foreach ($lines as $line) {
            // Ignore comments and empty lines
            if (strpos($line, '#') === 0 || empty($line)) {
                continue;
            }
            // Split each line into key=value
            list($key, $value) = explode('=', $line, 2);
            // Trim spaces and store the value in private static variables
            $key = trim($key);
            $value = trim($value);

            if ($key == 'DB_HOST') {
                self::$host = $value;
            } elseif ($key == 'DB_USERNAME') {
                self::$username = $value;
            } elseif ($key == 'DB_PASSWORD') {
                self::$password = $value;
            }
        }

        self::$envLoaded = true; // Flag to track if env variables are loaded
    }

    // Static method to return both database connections
    public static function getConnections()
    {
        // Load environment variables if not already loaded
        if (!self::$envLoaded) {
            self::loadEnv(__DIR__ . '/../data/.env');
        }

        // Validate that the environment variables are properly set
        if (empty(self::$host) || empty(self::$username) || empty(self::$password)) {
            die('Database connection credentials are not properly set.');
        }

        // Connect to the first database (creamdb)
        $creamdb = new mysqli(self::$host, self::$username, self::$password, "cream");
        if ($creamdb->connect_error) {
            die("Connection to creamdb failed: " . $creamdb->connect_error);
        }
        $creamdb->set_charset('utf8mb4');  // Set character set for creamdb

        // Connect to the second database (readerdb)
        $readerdb = new mysqli(self::$host, self::$username, self::$password, "reader");
        if ($readerdb->connect_error) {
            die("Connection to readerdb failed: " . $readerdb->connect_error);
        }
        $readerdb->set_charset('utf8mb4');  

        // Connect to the mailer database.
        // This connection is optional: $mailerdb is not queried anywhere in the
        // application. Previously a missing "mailer" database threw an uncaught
        // mysqli exception on PHP 8, which fataled 12 pages including
        // homePin.php, reader.php and generated_article.php. It is now tolerated.
        $mailerdb = null;
        try {
            $mailerdb = new mysqli(self::$host, self::$username, self::$password, "mailer");
            if ($mailerdb->connect_error) {
                $mailerdb = null;
            } else {
                $mailerdb->set_charset('utf8mb4');
            }
        } catch (\Throwable $e) {
            $mailerdb = null;   // database absent - the app does not need it
        }

        // Return both database connections as an associative array
        return [
            'creamdb' => $creamdb,
            'readerdb' => $readerdb,
            'mailerdb' => $mailerdb
        ];
    }
}

// Get both database connections by calling the static method
$connections = CreamConnection::getConnections();

// Access the creamdb connection
$creamdb = $connections['creamdb'];

// Access the readerdb connection
$readerdb = $connections['readerdb'];

// Access the mailerdb connection
$mailerdb = $connections['mailerdb'];
?>