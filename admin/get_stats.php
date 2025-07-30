<?php
session_start();
require_once '../core/db_connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Accesso negato']));
}

try {
    // Conta utenti, CV, esperienze e dati giornalieri
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE user_type = 'user'");
    $total_users = $stmt->fetch()['total_users'];

    $stmt = $pdo->query("SELECT COUNT(*) as total_cvs FROM curricula");
    $total_cvs = $stmt->fetch()['total_cvs'];

    $stmt = $pdo->query("SELECT COUNT(*) as total_exp FROM (SELECT id FROM esperienze_lavorative UNION ALL SELECT id FROM esperienze_formative) as exp");
    $total_exp = $stmt->fetch()['total_exp'];

    $stmt = $pdo->query("SELECT COUNT(*) as today_users FROM users WHERE DATE(created_at) = CURDATE()");
    $today_users = $stmt->fetch()['today_users'];

    $stmt = $pdo->query("SELECT COUNT(*) as today_cvs FROM curricula WHERE DATE(uploaded_at) = CURDATE()");
    $today_cvs = $stmt->fetch()['today_cvs'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => $total_users,
            'total_cvs' => $total_cvs,
            'total_experiences' => $total_exp,
            'today_users' => $today_users,
            'today_cvs' => $today_cvs
        ]
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore del server']);
}
?>
