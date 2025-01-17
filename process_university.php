<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من الصلاحيات
if (!hasPermission('add_university') && !hasPermission('edit_university')) {
    die('غير مصرح لك بإدارة الجامعات');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // التحقق من البيانات المطلوبة
        if (empty($_POST['name'])) {
            throw new Exception('اسم الجامعة مطلوب');
        }

        $name = trim($_POST['name']);
        $location = trim($_POST['location'] ?? '');
        $ministryDepartmentId = $_POST['ministry_department_id'] ?? null;

        // طباعة البيانات للتصحيح
        error_log("بيانات الجامعة: " . print_r($_POST, true));

        // إضافة جامعة جديدة
        if (!isset($_POST['id'])) {
            $stmt = $pdo->prepare("
                INSERT INTO universities (
                    name, 
                    location, 
                    ministry_department_id, 
                    created_at, 
                    created_by
                ) VALUES (
                    :name,
                    :location,
                    :ministry_department_id,
                    NOW(),
                    :created_by
                )
            ");
            
            $result = $stmt->execute([
                ':name' => $name,
                ':location' => $location,
                ':ministry_department_id' => $ministryDepartmentId,
                ':created_by' => $_SESSION['user_id']
            ]);

            if (!$result) {
                throw new Exception("فشل في إضافة الجامعة: " . implode(", ", $stmt->errorInfo()));
            }

            $universityId = $pdo->lastInsertId();

            // تسجيل النشاط
            logSystemActivity(
                "تم إضافة جامعة جديدة: $name (ID: $universityId)", 
                'university', 
                $_SESSION['user_id']
            );

            $_SESSION['success'] = 'تم إضافة الجامعة بنجاح';
        }
        // تعديل جامعة موجودة
        else {
            $stmt = $pdo->prepare("
                UPDATE universities 
                SET name = :name,
                    location = :location,
                    ministry_department_id = :ministry_department_id,
                    updated_at = NOW(),
                    updated_by = :updated_by
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                ':name' => $name,
                ':location' => $location,
                ':ministry_department_id' => $ministryDepartmentId,
                ':updated_by' => $_SESSION['user_id'],
                ':id' => $_POST['id']
            ]);

            if (!$result) {
                throw new Exception("فشل في تعديل الجامعة: " . implode(", ", $stmt->errorInfo()));
            }

            // تسجيل النشاط
            logSystemActivity(
                "تم تعديل الجامعة: $name (ID: {$_POST['id']})", 
                'university', 
                $_SESSION['user_id']
            );

            $_SESSION['success'] = 'تم تعديل الجامعة بنجاح';
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("خطأ في معالجة الجامعة: " . $e->getMessage());
        $_SESSION['error'] = 'حدث خطأ: ' . $e->getMessage();
    }
}

// العودة إلى صفحة الجامعات
header('Location: universities.php');
exit; 