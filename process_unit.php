<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

// التحقق من الصلاحيات
if (!hasPermission('add_unit') && !hasPermission('edit_unit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بإضافة أو تعديل الوحدات']);
    exit;
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
    exit;
}

// التحقق من البيانات المطلوبة
$requiredFields = ['name', 'university_id', 'college_id', 'division_id'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'جميع الحقول المطلوبة يجب تعبئتها']);
        exit;
    }
}

$action = $_POST['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim($_POST['name']);
$universityId = (int)$_POST['university_id'];
$collegeId = (int)$_POST['college_id'];
$divisionId = (int)$_POST['division_id'];
$description = trim($_POST['description'] ?? '');
$isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

try {
    // التحقق من صلاحية المستخدم للتعامل مع الجامعة المحددة
    if ($_SESSION['user_role'] !== 'admin') {
        $stmt = $pdo->prepare("
            SELECT 1 
            FROM user_entities ue 
            INNER JOIN university_divisions ud ON ue.entity_id = ud.id 
            WHERE ue.user_id = ? 
            AND ue.entity_type = 'division' 
            AND ue.is_primary = 1 
            AND ud.university_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $universityId]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالتعامل مع هذه الجامعة']);
            exit;
        }
    }

    // التحقق من وجود الوحدة في حالة التعديل
    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM units WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'الوحدة غير موجودة']);
            exit;
        }
    }

    // بدء المعاملة
    $pdo->beginTransaction();

    if ($action === 'edit' && $id > 0) {
        // تحديث الوحدة
        $stmt = $pdo->prepare("
            UPDATE units 
            SET name = ?, 
                university_id = ?, 
                college_id = ?, 
                division_id = ?, 
                description = ?, 
                is_active = ?,
                updated_by = ?,
                user_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $universityId,
            $collegeId,
            $divisionId,
            $description,
            $isActive,
            $_SESSION['user_id'],
            $_POST['user_id'],
            $id
        ]);
        $message = 'تم تحديث الوحدة بنجاح';
    } else {
        // إضافة وحدة جديدة
        $stmt = $pdo->prepare("
            INSERT INTO units (
                name, university_id, college_id, division_id, 
                description, is_active, created_by, user_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $name,
            $universityId,
            $collegeId,
            $divisionId,
            $description,
            $isActive,
            $_SESSION['user_id'],
            $_POST['user_id']
        ]);
        $message = 'تمت إضافة الوحدة بنجاح';
    }

    // تأكيد المعاملة
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    // التراجع عن المعاملة في حالة حدوث خطأ
    $pdo->rollBack();
    error_log("خطأ في معالجة الوحدة: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء معالجة البيانات']);
}
?>
