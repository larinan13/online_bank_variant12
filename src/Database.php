<?php

class Database
{
    private static ?PDO $connection = null;
    private static array $config = [];

    private function __construct() {}

    public static function loadConfig(string $configFile): void
    {
        if (!file_exists($configFile)) {
            throw new RuntimeException("Config file not found: $configFile");
        }
        require_once $configFile;
        
        self::$config = [
            'host' => DB_HOST,
            'dbname' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'charset' => DB_CHARSET
        ];
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            if (empty(self::$config)) {
                throw new RuntimeException("Config not loaded. Call Database::loadConfig() first.");
            }
            
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['dbname'],
                self::$config['charset']
            );
            
            try {
                self::$connection = new PDO($dsn, self::$config['user'], self::$config['pass']);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                throw new RuntimeException("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }
}
