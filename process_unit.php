<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من الصلاحيات
$userRole = $_SESSION['user_role'];
$userEntityType = $_SESSION['entity_type'] ?? null;
$userEntityId = $_SESSION['entity_id'] ?? null;

if ($userRole !== 'admin' && $userEntityType !== 'division') {
    die('غير مصرح لك بإدارة الوحدات');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        if (empty($_POST['name']) || empty($_POST['university_id']) || 
            empty($_POST['college_id']) || empty($_POST['unit_manager_id'])) {
            throw new Exception('جميع الحقول مطلوبة');
        }

        $name = trim($_POST['name']);
        $universityId = $_POST['university_id'];
        $collegeId = $_POST['college_id'];
        $unitManagerId = $_POST['unit_manager_id'];
        $description = trim($_POST['description'] ?? '');

        // التحقق من صلاحية الوصول للجامعة والكلية
        if ($userRole !== 'admin') {
            $stmt = $pdo->prepare("
                SELECT 1 
                FROM colleges c
                INNER JOIN university_divisions ud ON c.university_id = ud.university_id
                INNER JOIN user_entities ue ON ud.id = ue.entity_id
                WHERE c.id = ? 
                AND c.university_id = ?
                AND ue.user_id = ?
                AND ue.entity_type = 'division'
                AND ue.is_primary = 1
            ");
            $stmt->execute([$collegeId, $universityId, $_SESSION['user_id']]);
            
            if (!$stmt->fetch()) {
                throw new Exception('غير مصرح لك بإضافة وحدات لهذه الكلية');
            }
        }

        // إضافة وحدة جديدة
        $stmt = $pdo->prepare("
            INSERT INTO units (
                name,
                university_id,
                college_id,
                description,
                created_at,
                created_by
            ) VALUES (
                :name,
                :university_id,
                :college_id,
                :description,
                NOW(),
                :created_by
            )
        ");
        
        $stmt->execute([
            ':name' => $name,
            ':university_id' => $universityId,
            ':college_id' => $collegeId,
            ':description' => $description,
            ':created_by' => $_SESSION['user_id']
        ]);

        $unitId = $pdo->lastInsertId();

        // تحديث جدول المستخدمين وإضافة مدير الوحدة
        $stmt = $pdo->prepare("
            UPDATE users 
            SET university_id = :university_id,
                role = 'unit',
                updated_at = NOW()
            WHERE id = :user_id
        ");
        
        $stmt->execute([
            ':university_id' => $universityId,
            ':user_id' => $unitManagerId
        ]);

        // إضافة الانتماء للوحدة
        $stmt = $pdo->prepare("
            INSERT INTO user_entities (
                user_id,
                entity_id,
                entity_type,
                is_primary
            ) VALUES (
                :user_id,
                :entity_id,
                'unit',
                1
            )
        ");
        
        $stmt->execute([
            ':user_id' => $unitManagerId,
            ':entity_id' => $unitId
        ]);

        $pdo->commit();
        $_SESSION['success'] = 'تم إضافة الوحدة وتعيين مديرها بنجاح';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("خطأ في معالجة الوحدة: " . $e->getMessage());
        $_SESSION['error'] = 'حدث خطأ: ' . $e->getMessage();
    }
}

header('Location: units.php');
exit;
?>
