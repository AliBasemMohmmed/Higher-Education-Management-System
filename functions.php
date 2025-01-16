<?php
require_once 'config.php';

// دالة إضافة قسم جديد
function addMinistryDepartment($name, $description) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO ministry_departments (name, description) VALUES (?, ?)");
  return $stmt->execute([$name, $description]);
}

// دالة إضافة جامعة جديدة
function addUniversity($name, $location, $departmentId) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO universities (name, location, ministry_department_id) VALUES (?, ?, ?)");
  return $stmt->execute([$name, $location, $departmentId]);
}

// دالة إضافة شعبة جديدة
function addDivision($name, $universityId) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO university_divisions (name, university_id) VALUES (?, ?)");
  return $stmt->execute([$name, $universityId]);
}

// دالة إضافة وحدة جديدة
function addUnit($name, $collegeId, $divisionId) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO units (name, college_id, division_id) VALUES (?, ?, ?)");
  return $stmt->execute([$name, $collegeId, $divisionId]);
}

// دالة إضافة كتاب جديد
function addDocument($title, $content, $filePath, $senderType, $senderId, $receiverType, $receiverId) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO documents (title, content, file_path, sender_type, sender_id, receiver_type, receiver_id) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)");
  return $stmt->execute([$title, $content, $filePath, $senderType, $senderId, $receiverType, $receiverId]);
}

// دالة إضافة تقرير جديد
function addReport($title, $content, $filePath, $unitId, $documentId = null) {
  global $pdo;
  $stmt = $pdo->prepare("INSERT INTO reports (title, content, file_path, unit_id, document_id) VALUES (?, ?, ?, ?, ?)");
  return $stmt->execute([$title, $content, $filePath, $unitId, $documentId]);
}

// دالة جلب الكتب الخاصة بوحدة معينة
function getUnitDocuments($unitId) {
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM documents WHERE (receiver_type = 'unit' AND receiver_id = ?) 
                         OR (sender_type = 'unit' AND sender_id = ?)");
  $stmt->execute([$unitId, $unitId]);
  return $stmt->fetchAll();
}

// دالة جلب تقارير وحدة معينة
function getUnitReports($unitId) {
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM reports WHERE unit_id = ?");
  $stmt->execute([$unitId]);
  return $stmt->fetchAll();
}
?>
