<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['university_id']) || !isset($_GET['college_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'معرف الجامعة والكلية مطلوب']);
    exit;
}

$universityId = $_GET['university_id'];
$collegeId = $_GET['college_id'];

try {
    // جلب المستخدمين من نوع unit التابعين للجامعة المحددة
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.full_name 
        FROM users u
        WHERE u.role = 'unit'
        AND u.university_id = :university_id
        ORDER BY u.full_name
    ");
    
    $stmt->execute([
        'university_id' => $universityId
    ]);
    
    error_log("تم تنفيذ الاستعلام - university_id: " . $universityId . ", college_id: " . $collegeId);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("عدد المستخدمين: " . count($users));
    
    if (empty($users)) {
        error_log("لا يوجد مستخدمين");
        echo json_encode([]);
    } else {
        error_log("تم العثور على مستخدمين: " . json_encode($users));
        echo json_encode($users);
    }
} catch (PDOException $e) {
    error_log("خطأ في جلب المستخدمين: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    http_response_code(500);
    echo json_encode([
        'error' => 'حدث خطأ أثناء جلب البيانات',
        'details' => $e->getMessage()
    ]);
} 