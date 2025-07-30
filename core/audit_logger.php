<?php
require_once 'db_connection.php';

class AuditLogger {
    private $db;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }
    
    /**
     * Registra un'azione utente nella tabella audit_logs
     */
    public function log($action, $user_id = null, $table_name = null, $record_id = null, $details = null, $status = 'success') {
        try {
            $ip_address = $this->getClientIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            // Converte l'array details in JSON se necessario
            $details_json = is_array($details) ? json_encode($details) : $details;
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent, details, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$user_id, $action, $table_name, $record_id, $ip_address, $user_agent, $details_json, $status]);
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un tentativo di login
     */
    public function logLogin($user_id, $username, $success = true, $details = null) {
        $action = $success ? 'login_success' : 'login_failed';
        $status = $success ? 'success' : 'failed';
        $audit_details = [
            'username' => $username,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        if ($details) {
            $audit_details = array_merge($audit_details, $details);
        }
        return $this->log($action, $user_id, 'users', $user_id, $audit_details, $status);
    }
    
    /**
     * Registra un logout
     */
    public function logLogout($user_id, $username) {
        return $this->log('logout', $user_id, 'users', $user_id, [
            'username' => $username,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Registra operazioni sui dati (creazione, aggiornamento, eliminazione)
     */
    public function logDataOperation($action, $user_id, $table_name, $record_id, $details = null) {
        return $this->log($action, $user_id, $table_name, $record_id, $details);
    }
    
    /**
     * Registra operazioni di amministrazione
     */
    public function logAdminOperation($action, $admin_user_id, $target_user_id = null, $details = null) {
        return $this->log($action, $admin_user_id, 'users', $target_user_id, array_merge([
            'admin_operation' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ], $details ?? []));
    }
    
    /**
     * Registra operazioni su documenti
     */
    public function logDocumentOperation($action, $user_id, $document_type, $details = null) {
        return $this->log($action, $user_id, null, null, array_merge([
            'document_type' => $document_type,
            'timestamp' => date('Y-m-d H:i:s')
        ], $details ?? []));
    }
    
    /**
     * Registra eventi di sicurezza
     */
    public function logSecurityEvent($action, $user_id = null, $details = null) {
        return $this->log($action, $user_id, null, null, array_merge([
            'security_event' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ], $details ?? []), 'warning');
    }
    
    /**
     * Ottiene l'indirizzo IP del client
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Gestisce gli IP separati da virgola (header inoltrati)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Valida l'IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Ottiene i log delle audizioni con filtri
     */
    public function getLogs($filters = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($filters['user_id'])) {
                $where_conditions[] = "user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['action'])) {
                $where_conditions[] = "action = ?";
                $params[] = $filters['action'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "DATE(created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "DATE(created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['status'])) {
                $where_conditions[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            $sql = "
                SELECT 
                    al.*, 
                    u.email as username,
                    u.email 
                FROM audit_logs al 
                LEFT JOIN users u ON al.user_id = u.id
            ";
            
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(" AND ", $where_conditions);
            }
            
            $sql .= " ORDER BY al.created_at DESC LIMIT 1000";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Failed to get audit logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ottiene statistiche delle audizioni
     */
    public function getStatistics($days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    status,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM audit_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action, status, DATE(created_at)
                ORDER BY date DESC, count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Failed to get audit statistics: " . $e->getMessage());
            return [];
        }
    }
}
?>
