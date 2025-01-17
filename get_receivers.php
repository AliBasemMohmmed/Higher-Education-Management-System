<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['type'])) {
    die(json_encode(['error' => 'نوع المستلم مطلوب']));
}

$type = $_GET['type'];
$receivers = [];

try {
    global $pdo;
    
    switch ($type) {
        case 'ministry':
            $stmt = $pdo->query("SELECT id, name FROM ministry_departments ORDER BY name");
            $receivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'division':
            $stmt = $pdo->query("SELECT id, name FROM university_divisions ORDER BY name");
            $receivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'unit':
            $stmt = $pdo->query("SELECT id, name FROM units ORDER BY name");
            $receivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default:
            die(json_encode(['error' => 'نوع مستلم غير صالح']));
    }
    
    echo json_encode($receivers);
    
} catch (PDOException $e) {
    die(json_encode(['error' => 'خطأ في قاعدة البيانات']));
}
?> 