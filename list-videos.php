<?php
header('Content-Type: application/json');

$uploadDir = __DIR__ . '/uploads/';
$filesData = [];

// Check if the uploads directory exists
if (is_dir($uploadDir)) {
    // Scan the directory for files
    $files = scandir($uploadDir);
    
    foreach ($files as $file) {
        // Ignore the '.' and '..' system files and any sub-directories
        if ($file === '.' || $file === '..' || !is_file($uploadDir . $file)) {
            continue;
        }

        // Get file modification time as a timestamp (for sorting)
        $uploadTimestamp = filemtime($uploadDir . $file);
        
        // Add file information to the array
        $filesData[] = [
            'video_id'      => pathinfo($file, PATHINFO_FILENAME), // e.g., 'file_635ac...'
            'original_name' => htmlspecialchars($file), // Shows the unique filename
            'file_path'     => 'uploads/' . rawurlencode($file),
            'uploaded_at'   => date('Y-m-d H:i:s', $uploadTimestamp) // Format timestamp for display
        ];
    }

    // Sort the files by upload date in descending order (newest first)
    usort($filesData, function($a, $b) {
        // We compare the original timestamps for accuracy
        return filemtime(__DIR__ . '/' . $b['file_path']) <=> filemtime(__DIR__ . '/' . $a['file_path']);
    });
}

// Output the list of files as JSON
echo json_encode($filesData);
?>
