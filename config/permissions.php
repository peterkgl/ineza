<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function hasPermission($conn, $userId, $permissionCode) {
    if (!$userId) return false;

    $userId = (int)$userId;
    $roleQuery = "SELECT r.name FROM roles r 
                  JOIN user_roles ur ON r.id = ur.role_id 
                  WHERE ur.user_id = $userId";
    $roleResult = mysqli_query($conn, $roleQuery);
    if ($roleResult) {
        while ($row = mysqli_fetch_assoc($roleResult)) {
            if (strtolower($row['name']) === 'admin') {
                return true;
            }
        }
    }

    $permissionCodeEsc = mysqli_real_escape_string($conn, $permissionCode);
    $permQuery = "SELECT COUNT(*) as count FROM role_permissions rp
                  JOIN permissions p ON rp.permission_id = p.id
                  JOIN user_roles ur ON rp.role_id = ur.role_id
                  WHERE ur.user_id = $userId AND p.permition_code = '$permissionCodeEsc'";
    
    $permResult = mysqli_query($conn, $permQuery);
    if ($permResult) {
        $row = mysqli_fetch_assoc($permResult);
        return ((int)$row['count']) > 0;
    }
    
    return false;
}

function logAudit($conn, $action, $targetTable, $targetName = null, $targetDescription = null, $oldValues = null, $newValues = null, $notes = null) {
    $firstName = $_SESSION['first_name'] ?? '';
    $lastName = $_SESSION['last_name'] ?? '';
    $fullName = trim($firstName . ' ' . $lastName);
    if (empty($fullName)) {
        $fullName = 'Anonymous';
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $sessionId = session_id();

    $actionEsc = mysqli_real_escape_string($conn, $action);
    $targetTableEsc = mysqli_real_escape_string($conn, $targetTable);
    $targetNameEsc = $targetName !== null ? "'" . mysqli_real_escape_string($conn, $targetName) . "'" : "NULL";
    $targetDescEsc = $targetDescription !== null ? "'" . mysqli_real_escape_string($conn, $targetDescription) . "'" : "NULL";

    $oldValuesJson = $oldValues !== null ? "'" . mysqli_real_escape_string($conn, json_encode($oldValues, JSON_UNESCAPED_UNICODE)) . "'" : "NULL";
    $newValuesJson = $newValues !== null ? "'" . mysqli_real_escape_string($conn, json_encode($newValues, JSON_UNESCAPED_UNICODE)) . "'" : "NULL";
    
    $notesEsc = $notes !== null ? "'" . mysqli_real_escape_string($conn, $notes) . "'" : "NULL";
    $fullNameEsc = mysqli_real_escape_string($conn, $fullName);
    $ipEsc = mysqli_real_escape_string($conn, $ipAddress);
    $uaEsc = mysqli_real_escape_string($conn, $userAgent);
    $sessEsc = mysqli_real_escape_string($conn, $sessionId);

    $query = "INSERT INTO audit_log (
                user_full_name, action, target_table, target_name, target_description, 
                old_values, new_values, ip_address, user_agent, session_id, notes
              ) VALUES (
                '$fullNameEsc', '$actionEsc', '$targetTableEsc', $targetNameEsc, $targetDescEsc, 
                $oldValuesJson, $newValuesJson, '$ipEsc', '$uaEsc', '$sessEsc', $notesEsc
              )";

    return mysqli_query($conn, $query);
}
?>
