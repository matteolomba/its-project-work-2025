<?php
session_start();
require_once '../../core/audit_logger.php';

$auditLogger = new AuditLogger();

if (isset($_SESSION['user_id'])) {
    // Log logout
    $auditLogger->logLogout($_SESSION['user_id'], $_SESSION['user_email'] ?? 'unknown');
    
    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page
header('Location: ../../pages/auth/login.html');
exit();
?>
