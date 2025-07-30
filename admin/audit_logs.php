<?php
session_start();
require_once '../core/audit_logger.php';
require_once '../core/db_connection.php';

// Consenti accesso solo agli admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.html');
    exit();
}

$auditLogger = new AuditLogger();

// Gestione richieste AJAX per log e statistiche
if (isset($_GET['action']) && $_GET['action'] === 'get_logs') {
    $filters = [];
    if (!empty($_GET['user_id'])) {
        $filters['user_id'] = $_GET['user_id'];
    }
    if (!empty($_GET['action_filter'])) {
        $filters['action'] = $_GET['action_filter'];
    }
    if (!empty($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    }
    if (!empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    $logs = $auditLogger->getLogs($filters);
    header('Content-Type: application/json');
    echo json_encode($logs);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'get_stats') {
    $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
    $stats = $auditLogger->getStatistics($days);
    header('Content-Type: application/json');
    echo json_encode($stats);
    exit();
}

// Logga l'accesso dell'admin alla pagina dei log
$auditLogger->logAdminOperation('viewed_audit_logs', $_SESSION['user_id'], null, [
    'section' => 'audit_logs'
]);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log di Audit - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #667eea;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container-fluid {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 20px;
            width: 96%;
            padding: 2%;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: #667eea;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 500;
        }
        
        .btn-warning {
            background: #f093fb;
            border: none;
            border-radius: 25px;
            color: white;
        }
        
        .btn-success {
            background: #4facfe;
            border: none;
            border-radius: 25px;
        }
        
        .table {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .table thead {
            background: #667eea;
            color: white;
        }
        
        .badge-success { background: #28a745; }
        .badge-danger { background: #dc3545; }
        .badge-warning { background: #ffc107; color: #000; }
        
        .status-filter {
            margin-bottom: 20px;
        }
        
        .log-details {
            max-width: 300px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="display-6"><i class="fas fa-shield-alt me-3"></i>Log di Audit</h1>
                    <a href="home.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Torna alla Dashboard
                    </a>
                </div>
            </div>
        </div>


        <div class="row mb-4" id="statsCards">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-sign-in-alt fa-2x text-primary mb-3"></i>
                        <h5>Login Oggi</h5>
                        <h3 class="text-primary" id="loginToday">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <h5>Tentativi Falliti</h5>
                        <h3 class="text-warning" id="failedAttempts">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-success mb-3"></i>
                        <h5>Nuove Registrazioni</h5>
                        <h3 class="text-success" id="newRegistrations">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-database fa-2x text-info mb-3"></i>
                        <h5>Totale Eventi</h5>
                        <h3 class="text-info" id="totalEvents">-</h3>
                    </div>
                </div>
            </div>
        </div>


        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-filter me-2"></i>Filtri</h5>
                <div class="row">
                    <div class="col-md-2">
                        <select class="form-select" id="actionFilter">
                            <option value="">Tutte le Azioni</option>
                            <option value="login_success">Login Riusciti</option>
                            <option value="login_failed">Login Falliti</option>
                            <option value="logout">Logout</option>
                            <option value="user_registered">Registrazioni</option>
                            <option value="user_updated">Aggiornamenti Profilo</option>
                            <option value="document_uploaded">Upload Documenti</option>
                            <option value="viewed_audit_logs">Visualizzazione Log</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="statusFilter">
                            <option value="">Tutti gli Stati</option>
                            <option value="success">Successo</option>
                            <option value="failed">Fallito</option>
                            <option value="warning">Warning</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" id="dateFrom" placeholder="Data Da">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" id="dateTo" placeholder="Data A">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" id="userIdFilter" placeholder="User ID">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="loadLogs()">
                            <i class="fas fa-search me-2"></i>Filtra
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-list me-2"></i>Log di Attività</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data/Ora</th>
                                <th>Utente</th>
                                <th>Azione</th>
                                <th>Stato</th>
                                <th>IP</th>
                                <th>Dettagli</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr>
                                <td colspan="7" class="text-center">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Caricamento log...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Carica le statistiche all'avvio
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadLogs();
        });

        function loadStats() {
            fetch('audit_logs.php?action=get_stats&days=1')
                .then(response => response.json())
                .then(data => {
                    let loginToday = 0;
                    let failedAttempts = 0;
                    let newRegistrations = 0;
                    let totalEvents = 0;

                    data.forEach(stat => {
                        totalEvents += parseInt(stat.count);
                        
                        if (stat.action === 'login_success' && stat.status === 'success') {
                            loginToday += parseInt(stat.count);
                        }
                        if (stat.action === 'login_failed' && stat.status === 'failed') {
                            failedAttempts += parseInt(stat.count);
                        }
                        if (stat.action === 'user_registered' && stat.status === 'success') {
                            newRegistrations += parseInt(stat.count);
                        }
                    });

                    document.getElementById('loginToday').textContent = loginToday;
                    document.getElementById('failedAttempts').textContent = failedAttempts;
                    document.getElementById('newRegistrations').textContent = newRegistrations;
                    document.getElementById('totalEvents').textContent = totalEvents;
                })
                .catch(error => console.error('Error loading stats:', error));
        }

        function loadLogs() {
            const params = new URLSearchParams();
            params.append('action', 'get_logs');
            
            const actionFilter = document.getElementById('actionFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const userIdFilter = document.getElementById('userIdFilter').value;
            
            if (actionFilter) params.append('action_filter', actionFilter);
            if (statusFilter) params.append('status', statusFilter);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
            if (userIdFilter) params.append('user_id', userIdFilter);

            fetch('audit_logs.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('logsTableBody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nessun log trovato</td></tr>';
                        return;
                    }

                    data.forEach(log => {
                        const row = document.createElement('tr');
                        
                        const statusBadge = getStatusBadge(log.status);
                        const actionLabel = getActionLabel(log.action);
                        const userInfo = log.username ? `${log.username} (ID: ${log.user_id})` : 'N/A';
                        const details = log.details ? formatDetails(log.details) : '';
                        
                        row.innerHTML = `
                            <td>${log.id}</td>
                            <td>${formatDateTime(log.created_at)}</td>
                            <td>${userInfo}</td>
                            <td>${actionLabel}</td>
                            <td>${statusBadge}</td>
                            <td>${log.ip_address}</td>
                            <td class="log-details">${details}</td>
                        `;
                        
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading logs:', error);
                    document.getElementById('logsTableBody').innerHTML = 
                        '<tr><td colspan="7" class="text-center text-danger">Errore nel caricamento dei log</td></tr>';
                });
        }

        function getStatusBadge(status) {
            const badges = {
                'success': '<span class="badge badge-success">Successo</span>',
                'failed': '<span class="badge badge-danger">Fallito</span>',
                'warning': '<span class="badge badge-warning">Warning</span>'
            };
            return badges[status] || '<span class="badge badge-secondary">Sconosciuto</span>';
        }

        function getActionLabel(action) {
            const labels = {
                'login_success': 'Login Riuscito',
                'login_failed': 'Login Fallito',
                'logout': 'Logout',
                'user_registered': 'Registrazione',
                'user_updated': 'Aggiornamento Profilo',
                'document_uploaded': 'Upload Documento',
                'viewed_audit_logs': 'Visualizzazione Log',
                'registration_failed': 'Registrazione Fallita'
            };
            return labels[action] || action;
        }

        function formatDetails(details) {
            try {
                const parsed = JSON.parse(details);
                return Object.entries(parsed)
                    .map(([key, value]) => `${key}: ${value}`)
                    .join('<br>');
            } catch (e) {
                return details;
            }
        }

        function formatDateTime(dateTime) {
            return new Date(dateTime).toLocaleString('it-IT');
        }
    </script>
</body>
</html>
