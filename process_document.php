<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من وجود البيانات المطلوبة
    $requiredFields = ['title', 'content'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            die("الحقل $field مطلوب");
        }
    }

    // أخذ معلومات المرسل من الجلسة
    $senderType = $_SESSION['entity_type'] ?? 'unit'; // القيمة الافتراضية 'unit'
    $senderId = $_SESSION['entity_id'] ?? $_SESSION['user_id'];

    $title = $_POST['title'];
    $content = $_POST['content'];
    
    // معالجة الملف المرفق
    $filePath = null;
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['document_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $filePath)) {
            die('فشل في رفع الملف');
        }
    }
    
    try {
        global $pdo;
        $pdo->beginTransaction();

        // إدخال الكتاب كمسودة
        $stmt = $pdo->prepare("
            INSERT INTO documents (
                title, content, sender_type, sender_id,
                file_path, status, created_at, created_by
            ) VALUES (
                ?, ?, ?, ?, 
                ?, 'draft', NOW(), ?
            )
        ");
        
        $stmt->execute([
            $title,
            $content,
            $senderType,
            $senderId,
            $filePath,
            $_SESSION['user_id']
        ]);

        $documentId = $pdo->lastInsertId();

        // إضافة سجل في تاريخ الكتاب
        $historyStmt = $pdo->prepare("
            INSERT INTO document_history (
                document_id, user_id, action, notes
            ) VALUES (?, ?, 'create', 'تم إنشاء الكتاب')
        ");
        $historyStmt->execute([$documentId, $_SESSION['user_id']]);

        $pdo->commit();
        
        // توجيه المستخدم إلى صفحة إرسال الكتاب
        header("Location: send_document.php?id=" . $documentId);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }
        die('حدث خطأ أثناء حفظ الكتاب: ' . $e->getMessage());
    }
}

// إذا لم تكن الطريقة POST
header('Location: documents.php');
exit;
?>
