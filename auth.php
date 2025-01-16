<?php
session_start();

function isLoggedIn() {
  return isset($_SESSION['user_id']);
}

function requireLogin() {
  if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
  }
}

function hasPermission($permission) {
  global $pdo;
  if (!isLoggedIn()) return false;
  
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM permissions WHERE user_id = ? AND permission_name = ?");
  $stmt->execute([$_SESSION['user_id'], $permission]);
  return $stmt->fetchColumn() > 0;
}

function getUserRole() {
  return $_SESSION['user_role'] ?? null;
}
?>
