<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من الصلاحيات
if (!hasPermission('manage_users')) {
    die('غير مصرح لك بإدارة انتماءات المستخدمين');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

if (!$userId) {
    die('معرف المستخدم مطلوب');
}

try {
    switch ($action) {
        case 'add':
            $entityType = $_POST['entity_type'];
            $entityId = $_POST['entity_id'];
            $isPrimary = isset($_POST['is_primary']);
            
            if (assignUserToEntity($userId, $entityType, $entityId, $isPrimary)) {
                $_SESSION['success'] = 'تم إضافة الانتماء بنجاح';
            }
            break;
            
        case 'remove':
            $entityType = $_GET['entity_type'];
            $entityId = $_GET['entity_id'];
            
            $stmt = $pdo->prepare("
                DELETE FROM user_entities 
                WHERE user_id = ? AND entity_type = ? AND entity_id = ?
            ");
            if ($stmt->execute([$userId, $entityType, $entityId])) {
                $_SESSION['success'] = 'تم إزالة الانتماء بنجاح';
            }
            break;
            
        case 'make_primary':
            $entityType = $_GET['entity_type'];
            $entityId = $_GET['entity_id'];
            
            if (assignUserToEntity($userId, $entityType, $entityId, true)) {
                $_SESSION['success'] = 'تم تعيين الجهة الرئيسية بنجاح';
            }
            break;
            
        default:
            die('إجراء غير صالح');
    }
    
    // إعادة تحميل معلومات الجهة في الجلسة إذا كان المستخدم الحالي
    if ($userId == $_SESSION['user_id']) {
        setUserEntityInfo($userId);
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ: ' . $e->getMessage();
}

header("Location: manage_user_entities.php?user_id=$userId");
exit;
?> 