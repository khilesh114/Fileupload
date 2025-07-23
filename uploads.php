<?php
header('Content-Type: application/json');

// --- Configuration ---
$uploadDir = __DIR__ . '/uploads/'; 

// --- Response Helper ---
function send_json_response($status, $message, $data = []) {
    http_response_code($status);
    echo json_encode(['message' => $message, 'data' => $data]);
    exit;
}

// --- File and Directory Checks ---
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!isset($_FILES['video'])) send_json_response(400, 'No file uploaded.');

$file_to_upload = $_FILES['video'];
$originalName = basename($file_to_upload['name']);
$fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// --- Validation ---
if ($file_to_upload['error'] !== UPLOAD_ERR_OK) {
    send_json_response(500, 'Upload error. Code: ' . $file_to_upload['error'] . '. Check php.ini settings.');
}

// **SECURITY CHECK: Block dangerous file types like PHP**
$blacklisted_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar', '.htaccess'];
if (in_array($fileExtension, $blacklisted_extensions)) {
    send_json_response(400, 'Error: Executable server scripts are not allowed.');
}

// --- Secure File Handling ---
$uniqueId = uniqid('file_') . '-' . bin2hex(random_bytes(8));
$newFileName = $uniqueId . '.' . $fileExtension;
$targetPath = $uploadDir . $newFileName;

// --- Move the File ---
if (move_uploaded_file($file_to_upload['tmp_name'], $targetPath)) {
    // Since there's no database, we manually create the data object
    // to send back to the JavaScript so it can update the list instantly.
    $newFileData = [
        'video_id'      => $uniqueId,
        'original_name' => htmlspecialchars($newFileName), // We use the new filename
        'file_path'     => 'uploads/' . rawurlencode($newFileName),
        'uploaded_at'   => date('Y-m-d H:i:s')
    ];
    
    send_json_response(200, 'File uploaded successfully!', $newFileData);
} else {
    send_json_response(500, 'Failed to move uploaded file. Check folder permissions.');
}
?>
