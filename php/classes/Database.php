<?php
class Database
{
    /** @var mysqli|null */
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            self::$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (self::$connection->connect_error) {
                throw new Exception('Connect Error: ' . self::$connection->connect_error);
            }
            // Use utf8mb4 for proper emoji/support
            self::$connection->set_charset('utf8mb4');
        }
        return self::$connection;
    }
}

?>
