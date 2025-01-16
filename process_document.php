<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = $_POST['title'];
  $content = $_POST['content'];
  $senderType = $_POST['sender_type'];
  $senderId = $_POST['sender_id'];
  $receiverType = $_POST['receiver_type'];
  $receiverId = $_POST['receiver_id'];
  
  // معالجة الملف المرفق
  $filePath = null;
  if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($_FILES['document_file']['name']);
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['document_file']['tmp_name'], $filePath)) {
      // تم رفع الملف بنجاح
    } else {
      die('فشل في رفع الملف');
    }
  }
  
  try {
    addDocument($title, $content, $filePath, $senderType, $senderId, $receiverType, $receiverId);
    header('Location: documents.php?success=1');
    exit;
  } catch(PDOException $e) {
    die('خطأ في إضافة الكتاب: ' . $e->getMessage());
  }
}
?>
