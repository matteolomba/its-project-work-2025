<?php
session_start();
require_once '../core/db_connection.php';

// Consenti accesso solo agli admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.html');
    exit();
}

// Carica i dati dell'admin corrente
$stmt_admin = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_admin->execute([$_SESSION['user_id']]);
$admin_user = $stmt_admin->fetch();

try {
    // Carica tutti gli utenti (non admin) e conta i loro CV
    $sql = "SELECT u.id, u.nome, u.cognome, u.email, u.created_at, COUNT(c.id) as cv_count 
            FROM users u 
            LEFT JOIN curricula c ON u.id = c.user_id 
            WHERE u.user_type = 'user'
            GROUP BY u.id 
            ORDER BY u.cognome, u.nome";
    $stmt = $pdo->query($sql);
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Errore fatale: Impossibile caricare i dati degli utenti.");
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Amministratore - Gestione CV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-primary { background-color: #0d6efd !important; border-color: #0d6efd !important; color: white !important; }
        .btn-success { background-color: #198754 !important; border-color: #198754 !important; color: white !important; }
        .btn-warning { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #000 !important; }
        .btn-danger { background-color: #dc3545 !important; border-color: #dc3545 !important; color: white !important; }
        .btn-info { background-color: #0dcaf0 !important; border-color: #0dcaf0 !important; color: #000 !important; }
        .btn-secondary { background-color: #6c757d !important; border-color: #6c757d !important; color: white !important; }
        
        .card-header { 
            background: #667eea; 
            color: white; 
            border-bottom: none; 
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        .admin-card { 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1); 
            border-radius: 15px;
            overflow: hidden;
        }
        
        .accordion-item {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px !important;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }
        
        .accordion-button {
            background: #f8f9fa;
            border: none;
            font-weight: 500;
            padding: 1rem 1.5rem;
        }
        
        .accordion-button:not(.collapsed) {
            background: #667eea;
            color: white;
            box-shadow: none;
        }
        
        .navbar { 
            background: #667eea !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15); 
            padding: 1rem 0;
        }
        
        .user-stats-badge {
            background: #28a745;
            color: white;
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .modern-card {
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .modern-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-check me-2"></i>Pannello Admin</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item me-2">
                    <a href="audit_logs.php" class="btn btn-secondary">
                        <i class="bi bi-shield-alt me-1"></i>Log di Audit
                    </a>
                </li>
                <li class="nav-item me-2">
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#adminProfileModal">
                        <i class="bi bi-person-gear me-1"></i>Il Mio Profilo
                    </button>
                </li>
                <li class="nav-item">
                    <a href="../api/auth/logout.php" class="btn btn-warning">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <div id="message-container"></div>
        <div class="card admin-card">
            <div class="card-header">
                <div class="row align-items-center g-2">
                    <div class="col-md-4">
                        <h3 class="mb-0"><i class="bi bi-people-fill me-2"></i>Gestione Utenti</h3>
                    </div>
                    <div class="col-md-8 d-flex justify-content-end">
                        <div class="input-group me-3" style="max-width: 400px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="admin-search-input" class="form-control" placeholder="Cerca per nome, cognome, email...">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="checkbox" id="admin-filter-has-cv">
                                <label class="form-check-label ms-2" for="admin-filter-has-cv">Solo con CV</label>
                            </div>
                        </div>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-plus-circle me-1"></i>Aggiungi Utente
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="accordion" id="adminUserAccordion">
                    <?php if (empty($all_users)): ?>
                        <p class="text-center text-muted">Nessun utente studente trovato.</p>
                    <?php else: ?>
                        <?php foreach ($all_users as $user): ?>
                            <div class="accordion-item user-item" 
                                 data-name="<?= strtolower(htmlspecialchars($user['nome'] . ' ' . $user['cognome'])) ?>" 
                                 data-email="<?= strtolower(htmlspecialchars($user['email'])) ?>"
                                 data-has-cv="<?= $user['cv_count'] > 0 ? 'true' : 'false' ?>"
                                 data-user-id="<?= $user['id'] ?>">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $user['id'] ?>">
                                        <div class="d-flex align-items-center w-100">
                                            <div class="me-3">
                                                <i class="bi bi-person-circle fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <strong><?= htmlspecialchars($user['cognome'] . ' ' . $user['nome']) ?></strong>
                                                <div class="small text-muted"><?= htmlspecialchars($user['email']) ?></div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if($user['cv_count'] > 0): ?>
                                                    <span class="user-stats-badge"><?= $user['cv_count'] ?> CV</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Nessun CV</span>
                                                <?php endif; ?>
                                                <small class="text-muted">Dal <?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-<?= $user['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#adminUserAccordion">
                                    <div class="accordion-body">
                                        <div class="d-flex justify-content-center align-items-center" style="min-height: 100px;">
                                            <div class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Caricamento...</span>
                                                </div>
                                                <p class="mt-2 text-muted">Caricamento dettagli utente...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modern-card">
                <div class="modal-header" style="background: #198754; color: white;">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Aggiungi Nuovo Utente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="modal-error-container"></div>
                    <form id="addUserForm" action="../api/users/create.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome *</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Cognome *</label>
                                <input type="text" name="cognome" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-1"></i>Crea Utente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="adminEditUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modern-card">
                <div class="modal-header" style="background: #0d6efd; color: white;">
                    <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Modifica Profilo Utente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="modal-error-container"></div>
                    <form id="adminEditUserForm" action="update_user.php" method="POST">
                        <input type="hidden" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome *</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Cognome *</label>
                                <input type="text" name="cognome" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Telefono</label>
                                <input type="tel" name="telefono" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Indirizzo</label>
                                <input type="text" name="indirizzo" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Città</label>
                                <input type="text" name="citta" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sommario Professionale</label>
                            <textarea name="sommario" class="form-control" rows="3"></textarea>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-1"></i>Salva Modifiche
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="adminProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modern-card">
                <div class="modal-header" style="background: #6f42c1; color: white;">
                    <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Gestione Profilo Amministratore</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-4 bg-light p-4">
                            <div class="text-center">
                                <i class="bi bi-person-circle text-primary" style="font-size: 4rem;"></i>
                                <h6 class="mt-2"><?= htmlspecialchars($admin_user['nome'] . ' ' . $admin_user['cognome']) ?></h6>
                                <small class="text-muted">Amministratore</small>
                                <div class="small text-muted mt-1">Dal <?= date('d/m/Y', strtotime($admin_user['created_at'])) ?></div>
                            </div>
                        </div>
                        <div class="col-md-8 p-4">
                            <div class="modal-error-container"></div>
                            <ul class="nav nav-tabs" id="adminProfileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="admin-info-tab" data-bs-toggle="tab" data-bs-target="#admin-info" type="button">
                                        <i class="bi bi-person me-1"></i>Informazioni
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="admin-password-tab" data-bs-toggle="tab" data-bs-target="#admin-password" type="button">
                                        <i class="bi bi-key me-1"></i>Password
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="admin-settings-tab" data-bs-toggle="tab" data-bs-target="#admin-settings" type="button">
                                        <i class="bi bi-gear me-1"></i>Statistiche
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content mt-3" id="adminProfileTabsContent">
                                <div class="tab-pane fade show active" id="admin-info" role="tabpanel">
                                    <form id="adminSelfProfileForm" action="update_admin_profile.php" method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Nome *</label>
                                                <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($admin_user['nome']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Cognome *</label>
                                                <input type="text" name="cognome" class="form-control" value="<?= htmlspecialchars($admin_user['cognome']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Email *</label>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin_user['email']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Telefono</label>
                                            <input type="tel" name="telefono" class="form-control" value="<?= htmlspecialchars($admin_user['telefono']) ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-1"></i>Salva Modifiche
                                        </button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="admin-password" role="tabpanel">
                                    <form id="adminSelfPasswordForm" action="update_admin_password.php" method="POST">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Password Attuale *</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nuova Password *</label>
                                            <input type="password" name="new_password" class="form-control" required minlength="6">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Conferma Nuova Password *</label>
                                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                        </div>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-key me-1"></i>Cambia Password
                                        </button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="admin-settings" role="tabpanel">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Statistiche Sistema</strong>
                                    </div>
                                    <table class="table table-borderless">
                                        <tr><td class="fw-semibold">Totale utenti:</td><td id="admin-stats-users">Caricamento...</td></tr>
                                        <tr><td class="fw-semibold">CV totali:</td><td id="admin-stats-cvs">Caricamento...</td></tr>
                                        <tr><td class="fw-semibold">Ultimo login:</td><td><?= date('d/m/Y H:i') ?></td></tr>
                                    </table>
                                    <hr>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="adminChangePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modern-card">
                <div class="modal-header" style="background: #dc3545; color: white;">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i>Modifica Password Utente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="modal-error-container"></div>
                    <form id="adminChangePasswordForm" action="change_user_password.php" method="POST">
                        <input type="hidden" name="user_id">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Attenzione:</strong> Stai modificando la password di un altro utente.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nuova Password *</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                            <div class="form-text">Minimo 6 caratteri</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Conferma Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-key me-1"></i>Cambia Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module">
        import { handleUrlMessages } from '../assets/js/common.js';
        import { initAdminArea } from '../assets/js/admin.js';

        document.addEventListener('DOMContentLoaded', function () {
            handleUrlMessages();
            initAdminArea();
        });
    </script>
</body>
</html>
