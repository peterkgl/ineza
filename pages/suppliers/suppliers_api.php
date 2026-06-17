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

if (empty($_SESSION['suppliers_token'])) {
    $_SESSION['suppliers_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['suppliers_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['suppliers_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_suppliers')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view suppliers.');
        }

        $query = "SELECT * FROM suppliers ORDER BY name ASC";
        $result = mysqli_query($conn, $query);
        $suppliers = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $suppliers[] = [
                    'id' => (int)$row['id'],
                    'supplier_type' => $row['supplier_type'],
                    'name' => $row['name'],
                    'nif' => $row['nif'],
                    'vat_reg_no' => $row['vat_reg_no'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'region' => $row['region'],
                    'is_active' => (int)$row['is_active'],
                    'notes' => $row['notes'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list');
        sendResponse(true, 'Suppliers retrieved successfully.', $suppliers);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_supplier')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create suppliers.');
        }

        $supplier_type = isset($_POST['supplier_type']) ? trim($_POST['supplier_type']) : 'individual';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $nif = isset($_POST['nif']) ? trim($_POST['nif']) : '';
        $vat_reg_no = isset($_POST['vat_reg_no']) ? trim($_POST['vat_reg_no']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $region = isset($_POST['region']) ? trim($_POST['region']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if (empty($name)) {
            sendResponse(false, 'Supplier name is required.');
        }

        if (!in_array($supplier_type, ['individual', 'cooperative', 'company'])) {
            sendResponse(false, 'Invalid supplier type.');
        }

        $nameEsc = mysqli_real_escape_string($conn, $name);
        $chkName = mysqli_query($conn, "SELECT id FROM suppliers WHERE name = '$nameEsc' LIMIT 1");
        if ($chkName && mysqli_num_rows($chkName) > 0) {
            sendResponse(false, 'A supplier with this name already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nifEsc = mysqli_real_escape_string($conn, $nif);
            $vatEsc = mysqli_real_escape_string($conn, $vat_reg_no);
            $phoneEsc = mysqli_real_escape_string($conn, $phone);
            $emailEsc = mysqli_real_escape_string($conn, $email);
            $addressEsc = mysqli_real_escape_string($conn, $address);
            $regionEsc = mysqli_real_escape_string($conn, $region);
            $notesEsc = mysqli_real_escape_string($conn, $notes);

            $insertQuery = "INSERT INTO suppliers (supplier_type, name, nif, vat_reg_no, phone, email, address, region, notes, is_active, created_by) 
                            VALUES ('$supplier_type', '$nameEsc', '$nifEsc', '$vatEsc', '$phoneEsc', '$emailEsc', '$addressEsc', '$regionEsc', '$notesEsc', $is_active, $userId)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'supplier_type' => $supplier_type,
                    'name' => $name,
                    'nif' => $nif,
                    'vat_reg_no' => $vat_reg_no,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'region' => $region,
                    'is_active' => $is_active,
                    'notes' => $notes
                ];

                logAudit($conn, 'CREATE', 'suppliers', $name, "Created supplier: $name ($supplier_type)", null, $newValues);
                $_SESSION['suppliers_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Supplier created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create supplier: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_supplier')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit suppliers.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $supplier_type = isset($_POST['supplier_type']) ? trim($_POST['supplier_type']) : 'individual';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $nif = isset($_POST['nif']) ? trim($_POST['nif']) : '';
        $vat_reg_no = isset($_POST['vat_reg_no']) ? trim($_POST['vat_reg_no']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $region = isset($_POST['region']) ? trim($_POST['region']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($id <= 0 || empty($name)) {
            sendResponse(false, 'Valid ID and supplier name are required.');
        }

        if (!in_array($supplier_type, ['individual', 'cooperative', 'company'])) {
            sendResponse(false, 'Invalid supplier type.');
        }

        $fetchQuery = "SELECT * FROM suppliers WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Supplier not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $nameEsc = mysqli_real_escape_string($conn, $name);
        $chkName = mysqli_query($conn, "SELECT id FROM suppliers WHERE name = '$nameEsc' AND id != $id LIMIT 1");
        if ($chkName && mysqli_num_rows($chkName) > 0) {
            sendResponse(false, 'A supplier with this name already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nifEsc = mysqli_real_escape_string($conn, $nif);
            $vatEsc = mysqli_real_escape_string($conn, $vat_reg_no);
            $phoneEsc = mysqli_real_escape_string($conn, $phone);
            $emailEsc = mysqli_real_escape_string($conn, $email);
            $addressEsc = mysqli_real_escape_string($conn, $address);
            $regionEsc = mysqli_real_escape_string($conn, $region);
            $notesEsc = mysqli_real_escape_string($conn, $notes);

            $updateQuery = "UPDATE suppliers SET 
                                supplier_type = '$supplier_type', 
                                name = '$nameEsc', 
                                nif = '$nifEsc', 
                                vat_reg_no = '$vatEsc', 
                                phone = '$phoneEsc', 
                                email = '$emailEsc', 
                                address = '$addressEsc', 
                                region = '$regionEsc', 
                                notes = '$notesEsc', 
                                is_active = $is_active, 
                                updated_by = $userId, 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $newValues = [
                    'id' => $id,
                    'supplier_type' => $supplier_type,
                    'name' => $name,
                    'nif' => $nif,
                    'vat_reg_no' => $vat_reg_no,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'region' => $region,
                    'is_active' => $is_active,
                    'notes' => $notes
                ];

                logAudit($conn, 'UPDATE', 'suppliers', $name, "Updated supplier: $name ($supplier_type)", $oldValues, $newValues);
                $_SESSION['suppliers_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Supplier updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update supplier: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_supplier')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete suppliers.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid supplier ID.');
        }

        $fetchQuery = "SELECT * FROM suppliers WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Supplier not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $name = $oldValues['name'];

        $chkAdvances = mysqli_query($conn, "SELECT COUNT(*) as count FROM supplier_advances WHERE supplier_id = $id");
        $advancesCount = 0;
        if ($chkAdvances) {
            $advancesCount = (int)mysqli_fetch_assoc($chkAdvances)['count'];
        }

        if ($advancesCount > 0) {
            sendResponse(false, 'This supplier cannot be deleted because they currently have ' . $advancesCount . ' supplier advance record(s) configured.');
        }

        mysqli_begin_transaction($conn);

        try {
            $deleteQuery = "DELETE FROM suppliers WHERE id = $id";
            if (mysqli_query($conn, $deleteQuery)) {
                logAudit($conn, 'DELETE', 'suppliers', $name, "Deleted supplier: $name", $oldValues);
                $_SESSION['suppliers_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Supplier deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete supplier: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
