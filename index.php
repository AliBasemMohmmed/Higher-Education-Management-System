<?php
require_once 'functions.php';

// مثال على استخدام النظام
try {
  // إضافة قسم جديد
  addMinistryDepartment("قسم الدراسات العليا", "قسم مسؤول عن الدراسات العليا");
  
  // إضافة جامعة
  addUniversity("جامعة بغداد", "بغداد", 1);
  
  // إضافة شعبة
  addDivision("شعبة الدراسات", 1);
  
  // إضافة كلية
  $stmt = $pdo->prepare("INSERT INTO colleges (name, university_id) VALUES (?, ?)");
  $stmt->execute(["كلية الهندسة", 1]);
  $collegeId = $pdo->lastInsertId();
  
  // إضافة وحدة
  addUnit("وحدة شؤون الكمرك", $collegeId, 1);
  
  echo "تم إنشاء النظام بنجاح!";
} catch(PDOException $e) {
  echo "حدث خطأ: " . $e->getMessage();
}
?>
