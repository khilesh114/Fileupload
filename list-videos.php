<?php
header('Content-Type: application/json');

$dbPath = __DIR__ . '/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);

$pdo->exec("CREATE TABLE IF NOT EXISTS videos (
    id INTEGER PRIMARY KEY AUTOINCREMENT, video_id TEXT NOT NULL UNIQUE,
    original_name TEXT NOT NULL, file_path TEXT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $pdo->query("SELECT * FROM videos ORDER BY uploaded_at DESC");
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($videos);
?>
