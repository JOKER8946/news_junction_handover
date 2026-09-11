<?php

if (!class_exists('CreamConnection')) {
class CreamConnection
{
    private static $envLoaded = false;
    private static $host;
    private static $port;
    private static $username;
    private static $password;

    // Load environment variables from .env file
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
            if (strpos($line, '#') === 0) continue; // Skip comments

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            switch ($key) {
                case 'DB_HOST':
                    self::$host = $value;
                    break;
                case 'DB_PORT':
                    self::$port = (int)$value;
                    break;
                case 'DB_USERNAME':
                    self::$username = $value;
                    break;
                case 'DB_PASSWORD':
                    self::$password = $value;
                    break;
            }
        }

        self::$envLoaded = true;
    }

    // Return all database connections
    public static function getConnections()
    {
        if (!self::$envLoaded) {
            self::loadEnv(__DIR__ . '/../data/.env');
        }

        // Validate required ENV values
        if (
            empty(self::$host) ||
            empty(self::$username) ||
            empty(self::$password) ||
            empty(self::$port)
        ) {
            die('Database credentials (host, port, username, password) are not properly set.');
        }

        // Connect to cream database
        $creamdb = new mysqli(self::$host, self::$username, self::$password, "nj_cream", self::$port);
        if ($creamdb->connect_error) {
            die("Connection to creamdb failed: " . $creamdb->connect_error);
        }
        $creamdb->set_charset('utf8mb4');

        // Connect to reader database
        $readerdb = new mysqli(self::$host, self::$username, self::$password, "nj_reader", self::$port);
        if ($readerdb->connect_error) {
            die("Connection to readerdb failed: " . $readerdb->connect_error);
        }
        $readerdb->set_charset('utf8mb4');

        // Connect to mailer database
        $mailerdb = new mysqli(self::$host, self::$username, self::$password, "nj_mailer", self::$port);
        if ($mailerdb->connect_error) {
            die("Connection to mailerdb failed: " . $mailerdb->connect_error);
        }

        // Return array of all DB connections
        return [
            'creamdb' => $creamdb,
            'readerdb' => $readerdb,
            'mailerdb' => $mailerdb
        ];
    }
}
} // end if (!class_exists('CreamConnection'))

// Get all database connections (only once per request)
if (!isset($creamdb) || !($creamdb instanceof mysqli)) {
    $connections = CreamConnection::getConnections();
    $creamdb  = $connections['creamdb'];
    $readerdb = $connections['readerdb'];
    $mailerdb = $connections['mailerdb'];
}

?>
