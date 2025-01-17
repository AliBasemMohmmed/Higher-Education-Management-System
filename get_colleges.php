<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

if (!isset($_GET['university_id'])) {
    die(json_encode([]));
}

$universityId = $_GET['university_id'];
$userRole = $_SESSION['user_role'];
$userEntityType = $_SESSION['entity_type'] ?? null;

try {
    if ($userRole === 'admin') {
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM colleges 
            WHERE university_id = ?
            ORDER BY name
        ");
        $stmt->execute([$universityId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name 
            FROM colleges c
            INNER JOIN university_divisions ud ON c.university_id = ud.university_id
            INNER JOIN user_entities ue ON ud.id = ue.entity_id
            WHERE c.university_id = ?
            AND ue.user_id = ?
            AND ue.entity_type = 'division'
            AND ue.is_primary = 1
            ORDER BY c.name
        ");
        $stmt->execute([$universityId, $_SESSION['user_id']]);
    }
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([]);
} 