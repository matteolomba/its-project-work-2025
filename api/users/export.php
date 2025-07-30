<?php
session_start();
require_once '../../core/db_connection.php';

// Verifica autenticazione utente
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Recupera tutti i dati dell'utente
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    // Recupera curricula
    $stmt = $pdo->prepare("SELECT * FROM curricula WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $curricula = $stmt->fetchAll();
    
    // Recupera esperienze lavorative
    $stmt = $pdo->prepare("SELECT * FROM esperienze_lavorative WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $exp_lavorative = $stmt->fetchAll();
    
    // Recupera esperienze formative
    $stmt = $pdo->prepare("SELECT * FROM esperienze_formative WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $exp_formative = $stmt->fetchAll();
    
    // Prepara struttura dati per export GDPR
    $export_data = [
        'data_esportazione' => date('Y-m-d H:i:s'),
        'informativa' => 'Dati esportati secondo Art. 20 GDPR - Diritto alla portabilità dei dati',
        'titolare_trattamento' => 'CV Manager System',
        'contatto_privacy' => 'privacy@cvmanager.local',
        'dati_personali' => [
            'id' => $user_data['id'],
            'nome' => $user_data['nome'],
            'cognome' => $user_data['cognome'],
            'email' => $user_data['email'],
            'telefono' => $user_data['telefono'],
            'indirizzo' => $user_data['indirizzo'],
            'citta' => $user_data['citta'],
            'sommario' => $user_data['sommario'],
            'data_registrazione' => $user_data['created_at'],
            'tipo_utente' => $user_data['user_type']
        ],
        'curricula' => array_map(function($cv) {
            return [
                'id' => $cv['id'],
                'nome_file' => $cv['nome_originale'],
                'tipo' => $cv['tipo'] ?? 'caricato',
                'data_caricamento' => $cv['uploaded_at']
            ];
        }, $curricula),
        'esperienze_lavorative' => $exp_lavorative,
        'esperienze_formative' => $exp_formative,
        'note_legali' => [
            'gdpr_compliance' => true,
            'data_retention_policy' => 'I dati vengono conservati fino alla cancellazione dell\'account',
            'data_processing_basis' => 'Art. 6(1)(b) GDPR - Esecuzione del contratto',
            'diritti_interessato' => [
                'accesso' => 'Art. 15 GDPR',
                'rettifica' => 'Art. 16 GDPR',
                'cancellazione' => 'Art. 17 GDPR',
                'portabilità' => 'Art. 20 GDPR',
                'opposizione' => 'Art. 21 GDPR'
            ]
        ]
    ];
    
    // Invia come download JSON
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="dati_personali_' . date('Y-m-d') . '.json"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Errore export dati GDPR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore nell\'esportazione dei dati']);
}
?>
