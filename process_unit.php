<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hasPermission('add_unit')) {
    die('غير مصرح لك بإضافة وحدة');
  }
  
  $name = $_POST['name'];
  $collegeId = $_POST['college_id'];
  $divisionId = $_POST['division_id'];
  
  try {
    $stmt = $pdo->prepare("INSERT INTO units (name, college_id, division_id) VALUES (?, ?, ?)");
    $stmt->execute([$name, $collegeId, $divisionId]);
    header('Location: units.php?success=1');
    exit;
  } catch(PDOException $e) {
    die('خطأ في إضافة الوحدة: ' . $e->getMessage());
  }
}
?>
