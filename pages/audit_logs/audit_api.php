<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

function sendResponse($success, $message, $data = null, $pagination = null, $stats = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'pagination' => $pagination,
        'stats' => $stats
    ]);
    exit();
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_audit_logs')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view audit logs.');
        }

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;

        if ($page < 1) $page = 1;
        if ($limit < 5) $limit = 5;
        if ($limit > 100) $limit = 100;

        $where = "";
        if ($search !== "") {
            $searchEsc = mysqli_real_escape_string($conn, $search);
            $where = " WHERE user_full_name LIKE '%$searchEsc%' 
                        OR action LIKE '%$searchEsc%' 
                        OR target_table LIKE '%$searchEsc%' 
                        OR target_name LIKE '%$searchEsc%' 
                        OR target_description LIKE '%$searchEsc%' 
                        OR ip_address LIKE '%$searchEsc%' 
                        OR notes LIKE '%$searchEsc%'";
        }

        $countQuery = "SELECT COUNT(*) as count FROM audit_log" . $where;
        $countResult = mysqli_query($conn, $countQuery);
        $totalRecords = 0;
        if ($countResult) {
            $totalRecords = (int)mysqli_fetch_assoc($countResult)['count'];
        }

        $totalPages = ceil($totalRecords / $limit);
        if ($totalPages < 1) $totalPages = 1;
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $limit;

        $query = "SELECT * FROM audit_log" . $where . " ORDER BY performed_at DESC, id DESC LIMIT $limit OFFSET $offset";
        $result = mysqli_query($conn, $query);
        $logs = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $logs[] = [
                    'id' => (int)$row['id'],
                    'user_full_name' => $row['user_full_name'],
                    'action' => $row['action'],
                    'target_table' => $row['target_table'],
                    'target_name' => $row['target_name'],
                    'target_description' => $row['target_description'],
                    'old_values' => $row['old_values'] ? json_decode($row['old_values'], true) : null,
                    'new_values' => $row['new_values'] ? json_decode($row['new_values'], true) : null,
                    'ip_address' => $row['ip_address'],
                    'user_agent' => $row['user_agent'],
                    'session_id' => $row['session_id'],
                    'notes' => $row['notes'],
                    'performed_at' => $row['performed_at']
                ];
            }
        }

        $statsQuery = "SELECT 
                        COUNT(*) as total, 
                        SUM(CASE WHEN action = 'CREATE' THEN 1 ELSE 0 END) as creates, 
                        SUM(CASE WHEN action = 'UPDATE' THEN 1 ELSE 0 END) as updates, 
                        SUM(CASE WHEN action = 'DELETE' THEN 1 ELSE 0 END) as deletes 
                       FROM audit_log";
        $statsResult = mysqli_query($conn, $statsQuery);
        $stats = ['total' => 0, 'creates' => 0, 'updates' => 0, 'deletes' => 0];
        if ($statsResult) {
            $row = mysqli_fetch_assoc($statsResult);
            $stats = [
                'total' => (int)$row['total'],
                'creates' => (int)$row['creates'],
                'updates' => (int)$row['updates'],
                'deletes' => (int)$row['deletes']
            ];
        }

        logAudit($conn, 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list');

        sendResponse(true, 'Audit logs retrieved successfully.', $logs, [
            'page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages
        ], $stats);
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
