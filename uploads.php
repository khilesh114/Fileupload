<?php
header('Content-Type: application/json');

// --- Configuration ---
$uploadDir = __DIR__ . '/uploads/'; 
$dbPath = __DIR__ . '/database.sqlite'; 

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
    send_json_response(500, 'Upload error. Code: ' . $file_to_upload['error'] . '. Check php.ini settings for large files.');
}

// **SECURITY CHECK: Block dangerous file types like PHP**
$blacklisted_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar', '.htaccess'];
if (in_array($fileExtension, $blacklisted_extensions)) {
    send_json_response(400, 'Error: Executable server scripts are not allowed.');
}
// **NOTE: Size check is removed. The only limit is from your php.ini file.**

// --- Secure File Handling ---
$uniqueId = uniqid('file_') . '-' . bin2hex(random_bytes(8));
$newFileName = $uniqueId . '.' . $fileExtension;
$targetPath = $uploadDir . $newFileName;

// --- Move and Save to Database ---
if (move_uploaded_file($file_to_upload['tmp_name'], $targetPath)) {
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
            id INTEGER PRIMARY KEY AUTOINCREMENT, video_id TEXT NOT NULL UNIQUE,
            original_name TEXT NOT NULL, file_path TEXT NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $pdo->prepare(
            "INSERT INTO videos (video_id, original_name, file_path) VALUES (?, ?, ?)"
        );
        $stmt->execute([$uniqueId, $originalName, 'uploads/' . $newFileName]);
        
        $lastInsertId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
        $stmt->execute([$lastInsertId]);
        $newFileData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        send_json_response(200, 'File uploaded successfully!', $newFileData);

    } catch (PDOException $e) {
        unlink($targetPath); // Clean up file if database fails
        send_json_response(500, 'Database error: ' . $e->getMessage());
    }
} else {
    send_json_response(500, 'Failed to move uploaded file. Check folder permissions.');
}
?>
