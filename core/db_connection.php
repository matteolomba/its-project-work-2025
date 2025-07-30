<?php
// Carica variabili ambiente dal file .env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $_ENV[$name] = $value;
    }
}

// Configurazione delle credenziali del database
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

// Stringa di connessione (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// Opzioni di PDO per una connessione robusta
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Solleva eccezioni per errori SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Restituisce risultati come array associativi
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa prepared statements nativi
];

try {
    // Crea l'oggetto PDO per la connessione al database
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    // In caso di errore di connessione, mostra un messaggio generico
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
