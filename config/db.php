<?php
/**
 * Clase Database que implementa el patrón Singleton para manejar la conexión a la base de datos
 */
class Database {
    private static $instance = null; // Almacena la única instancia de la clase
    private $connection; // Almacena la conexión PDO

    // Constructor privado para prevenir instanciación directa
    private function __construct() {
        // Configuración de la conexión a la base de datos
        $host = 'ls-24ab11e36d4d24df477ba5a5a8f5753b1eb7c14a.cylsiewx0zgx.us-east-1.rds.amazonaws.com';
        $db   = 'totem_logs';
        $user = 'dbmasteruser';
        $pass = '_b?Q#0HE_xBcs?27e>)%};FsxUMA~]J}';
        $charset = 'utf8mb4';

        // Cadena de conexión DSN (Data Source Name)
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        
        // Opciones de configuración para PDO
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve arrays asociativos
            PDO::ATTR_EMULATE_PREPARES   => false, // Usa prepared statements nativos
        ];

        try {
            // Intenta crear la conexión PDO
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            // Lanza excepción si falla la conexión
            throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    /**
     * Método para obtener la instancia única de la conexión (Singleton)
     * @return PDO La conexión a la base de datos
     */
    public static function getInstance() {
        // Si no existe una instancia, crea una nueva
        if (!self::$instance) {
            self::$instance = new Database();
        }
        // Devuelve la conexión PDO
        return self::$instance->connection;
    }
}
?>


