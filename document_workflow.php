
<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();
include 'header.php';

// التحقق من معرف الكتاب
$documentId = $_GET['id'] ?? null;
if (!$documentId) {
  die('معرف الكت