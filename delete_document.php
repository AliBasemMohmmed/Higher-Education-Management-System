<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // قراءة البيانات من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'معرف الوثيقة مطلوب']);
        exit;
    }

    try {
        global $pdo;
        $pdo->beginTransaction();

        // جلب معلومات الملف قبل الحذف
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            throw new Exception('الوثيقة غير موجودة');
        }

        // حذف الوثيقة
        $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->execute([$id]);

        // حذف الملف المرفق إذا وجد
        if ($document['file_path'] && file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'تم حذف الوثيقة بنجاح']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// إذا لم تكن الطريقة POST
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
exit; 