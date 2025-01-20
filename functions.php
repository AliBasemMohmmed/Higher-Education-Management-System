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

// دالة تنسيق التاريخ
function formatDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}

// دالة تنسيق حجم الملف
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

// دالة لون حالة الكتاب
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'received' => 'info',
        'processed' => 'success',
        'rejected' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

// دالة نص حالة الكتاب
function getStatusText($status) {
    $texts = [
        'pending' => 'قيد الانتظار',
        'received' => 'تم الاستلام',
        'processed' => 'تمت المعالجة',
        'rejected' => 'مرفوض'
    ];
    return $texts[$status] ?? $status;
}

function sendNotification($userId, $title, $message, $type = 'info') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $title, $message, $type]);
    } catch (PDOException $e) {
        error_log("خطأ في إرسال الإشعار: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة لتنسيق حالة الكتاب مع الألوان
 */
function getStatusClass($status) {
    $classes = [
        'draft' => 'bg-secondary',
        'pending' => 'bg-warning',
        'sent' => 'bg-info',
        'received' => 'bg-primary',
        'processed' => 'bg-success',
        'archived' => 'bg-dark'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

/**
 * دالة لتحويل حالة الكتاب إلى نص عربي
 */
function getStatusLabel($status) {
    $labels = [
        'draft' => 'مسودة',
        'pending' => 'قيد الإرسال',
        'sent' => 'تم الإرسال',
        'received' => 'تم الاستلام',
        'processed' => 'تمت المعالجة',
        'archived' => 'مؤرشف'
    ];
    return $labels[$status] ?? $status;
}

/**
 * دالة لتحويل التاريخ إلى صيغة "منذ..."
 */
function timeAgo($datetime) {
    if (!$datetime) return '';
    
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return "منذ " . $diff->y . " سنة";
    } elseif ($diff->m > 0) {
        return "منذ " . $diff->m . " شهر";
    } elseif ($diff->d > 0) {
        return "منذ " . $diff->d . " يوم";
    } elseif ($diff->h > 0) {
        return "منذ " . $diff->h . " ساعة";
    } elseif ($diff->i > 0) {
        return "منذ " . $diff->i . " دقيقة";
    } else {
        return "منذ لحظات";
    }
}

/**
 * دالة للتحقق من نوع الملف
 */
function getAllowedFileTypes() {
    return [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
}

/**
 * دالة للتحقق من حجم الملف
 */
function getMaxFileSize() {
    return 10 * 1024 * 1024; // 10 ميجابايت
}

/**
 * دالة لتنظيف اسم الملف
 */
function sanitizeFileName($fileName) {
    // إزالة الأحرف غير المسموح بها
    $fileName = preg_replace("/[^a-zA-Z0-9.-]/", "_", $fileName);
    // تجنب تكرار النقاط
    $fileName = preg_replace("/\.+/", ".", $fileName);
    // تقصير اسم الملف إذا كان طويلاً جداً
    if (strlen($fileName) > 255) {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileName = substr($fileName, 0, 250 - strlen($ext)) . '.' . $ext;
    }
    return $fileName;
}

/**
 * دالة لإنشاء رقم مرجعي فريد للكتاب
 */
function generateDocumentReference() {
    return date('Y') . '/' . date('m') . '/' . uniqid();
}

/**
 * دالة للحصول على رابط الإشعار حسب نوعه
 */
function getNotificationLink($notification) {
    // إذا لم يكن هناك نوع مرتبط، نعيد رابط الإشعارات الافتراضي
    if (!isset($notification['type'])) {
        return '#';
    }

    switch ($notification['type']) {
        case 'document':
            return 'view_document.php?id=' . ($notification['document_id'] ?? '');
        case 'report':
            return 'view_report.php?id=' . ($notification['report_id'] ?? '');
        default:
            return '#';
    }
}

/**
 * دالة لربط المستخدم بجهة معينة
 */
function assignUserToEntity($userId, $entityType, $entityId, $isPrimary = true) {
    global $pdo;
    
    try {
        // إذا كانت الجهة رئيسية، نجعل باقي الجهات ثانوية
        if ($isPrimary) {
            $updateStmt = $pdo->prepare("
                UPDATE user_entities 
                SET is_primary = FALSE 
                WHERE user_id = ?
            ");
            $updateStmt->execute([$userId]);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO user_entities (user_id, entity_type, entity_id, is_primary)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)
        ");
        
        return $stmt->execute([$userId, $entityType, $entityId, $isPrimary]);
    } catch (PDOException $e) {
        error_log("خطأ في ربط المستخدم بالجهة: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة لجلب جميع جهات المستخدم
 */
function getUserEntities($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ue.*, 
                CASE 
                    WHEN ue.entity_type = 'ministry' THEN (SELECT name FROM ministry_departments WHERE id = ue.entity_id)
                    WHEN ue.entity_type = 'division' THEN (SELECT name FROM university_divisions WHERE id = ue.entity_id)
                    WHEN ue.entity_type = 'unit' THEN (SELECT name FROM units WHERE id = ue.entity_id)
                END as entity_name
            FROM user_entities ue
            WHERE ue.user_id = ?
            ORDER BY ue.is_primary DESC, ue.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("خطأ في جلب جهات المستخدم: " . $e->getMessage());
        return [];
    }
}

/**
 * دالة لجلب الجهة الرئيسية للمستخدم
 */
function getUserPrimaryEntity($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM user_entities 
            WHERE user_id = ? AND is_primary = TRUE 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("خطأ في جلب الجهة الرئيسية للمستخدم: " . $e->getMessage());
        return null;
    }
}

/**
 * دالة للتحقق من انتماء المستخدم لجهة معينة
 */
function isUserInEntity($userId, $entityType, $entityId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM user_entities 
            WHERE user_id = ? AND entity_type = ? AND entity_id = ?
        ");
        $stmt->execute([$userId, $entityType, $entityId]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("خطأ في التحقق من انتماء المستخدم للجهة: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة لتحويل نوع الجهة إلى نص عربي
 */
function getEntityTypeLabel($type) {
    $labels = [
        'ministry' => 'قسم الوزارة',
        'division' => 'شعبة',
        'unit' => 'وحدة'
    ];
    return $labels[$type] ?? $type;
}

function addNotification($userId, $title, $message, $type = 'info') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $title, $message, $type]);
    } catch (PDOException $e) {
        error_log("خطأ في إضافة الإشعار: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة للحصول على معلومات المستخدم الكاملة
 */
function getUserFullInfo($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*,
                   un.name as university_name,
                   c.name as college_name,
                   CASE 
                     WHEN u.role = 'ministry' THEN (SELECT name FROM ministry_departments WHERE id = u.entity_id)
                     WHEN u.role = 'division' THEN (SELECT name FROM university_divisions WHERE id = u.entity_id)
                     WHEN u.role = 'unit' THEN (SELECT name FROM units WHERE id = u.entity_id)
                   END as entity_name
            FROM users u
            LEFT JOIN universities un ON u.university_id = un.id
            LEFT JOIN colleges c ON u.college_id = c.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $userInfo = $stmt->fetch();
        
        if ($userInfo) {
            // إضافة المعرفات حسب نوع المستخدم
            switch($userInfo['role']) {
                case 'ministry':
                    $userInfo['department_id'] = $userInfo['entity_id'];
                    $userInfo['department_name'] = $userInfo['entity_name'];
                    break;
                case 'division':
                    $userInfo['division_id'] = $userInfo['entity_id'];
                    $userInfo['division_name'] = $userInfo['entity_name'];
                    break;
                case 'unit':
                    $userInfo['unit_id'] = $userInfo['entity_id'];
                    $userInfo['unit_name'] = $userInfo['entity_name'];
                    break;
            }
        }
        
        return $userInfo;
    } catch (PDOException $e) {
        error_log("خطأ في جلب معلومات المستخدم: " . $e->getMessage());
        return null;
    }
}

/**
 * دالة للحصول على نص الترحيب حسب نوع المستخدم
 */
function getWelcomeMessage($userInfo) {
    if (!$userInfo) return "مرحباً بك في النظام";
    
    $welcome = "مرحباً بك ";
    $welcome .= $userInfo['full_name'];
    
    switch ($userInfo['role']) {
        case 'admin':
            $welcome .= " - مدير النظام";
            break;
        case 'unit':
            $welcome .= " - رئيس وحدة " . ($userInfo['unit_name'] ?? '');
            if (!empty($userInfo['college_name'])) {
                $welcome .= " في " . $userInfo['college_name'];
            }
            if (!empty($userInfo['university_name'])) {
                $welcome .= " - " . $userInfo['university_name'];
            }
            break;
        case 'division':
            $welcome .= " - رئيس شعبة " . ($userInfo['division_name'] ?? '');
            if (!empty($userInfo['university_name'])) {
                $welcome .= " في " . $userInfo['university_name'];
            }
            break;
        case 'ministry':
            $welcome .= " - " . ($userInfo['department_name'] ?? 'قسم الوزارة');
            break;
    }
    
    return $welcome;
}

function getDefaultPermissions($role) {
    switch($role) {
        case 'division':
            return [
                'view_colleges',     // إضافة صلاحية عرض الكليات
                'manage_colleges',   // إضافة صلاحية إدارة الكليات
                'view_units',
                'manage_units',
                'add_unit',
                'edit_unit',
                'delete_unit',
                'view_reports',
                'manage_reports',
                'add_report',
                'edit_report',
                'delete_report'
            ];
        // ... rest of the code ...
    }
}

/**
 * دالة للحصول على الصلاحيات الثابتة حسب نوع المستخدم
 */
function getRolePermissions($role) {
    global $pdo;
    
    try {
        // محاولة جلب الصلاحيات المخصصة من قاعدة البيانات
        $stmt = $pdo->prepare("
            SELECT permission_name 
            FROM role_default_permissions 
            WHERE role = ?
        ");
        $stmt->execute([$role]);
        $customPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // إذا وجدت صلاحيات مخصصة، نستخدمها
        if (!empty($customPermissions)) {
            return $customPermissions;
        }
    } catch (PDOException $e) {
        error_log("خطأ في جلب الصلاحيات المخصصة: " . $e->getMessage());
    }
    
    // الصلاحيات الافتراضية إذا لم توجد صلاحيات مخصصة
    $defaultPermissions = [
        'admin' => [
            // صلاحيات إدارة المستخدمين
            'manage_users', 'view_users', 'add_user', 'edit_user', 'delete_user',
            // صلاحيات إدارة الجامعات
            'manage_universities', 'view_universities', 'add_university', 'edit_university', 'delete_university',
            // صلاحيات إدارة الكليات
            'manage_colleges', 'view_colleges', 'add_college', 'edit_college', 'delete_college',
            // صلاحيات إدارة الشعب
            'manage_divisions', 'view_divisions', 'add_division', 'edit_division', 'delete_division',
            // صلاحيات إدارة الوحدات
            'manage_units', 'view_units', 'add_unit', 'edit_unit', 'delete_unit',
            // صلاحيات إدارة التقارير
            'manage_reports', 'view_reports', 'add_report', 'edit_report', 'delete_report',
            // صلاحيات النظام
            'manage_settings', 'view_logs', 'manage_permissions'
        ],
        
        'ministry' => [
            // صلاحيات الجامعات
            'view_universities', 'manage_universities',
            // صلاحيات الكليات
            'view_colleges',
            // صلاحيات الشعب
            'view_divisions',
            // صلاحيات التقارير
            'view_reports',
            // صلاحيات الكتب
            'manage_documents', 'view_documents', 'add_document', 'edit_document'
        ],
        
        'division' => [
            // صلاحيات الكليات
            'view_colleges',
            // صلاحيات الوحدات
            'manage_units', 'view_units', 'add_unit', 'edit_unit',
            // صلاحيات التقارير
            'manage_reports', 'view_reports',
            // صلاحيات الكتب
            'manage_documents', 'view_documents', 'add_document', 'edit_document'
        ],
        
        'unit' => [
            // صلاحيات التقارير
            'manage_unit_reports', 'view_unit_reports', 'add_unit_report', 'edit_unit_report',
            // صلاحيات الكتب
            'manage_unit_documents', 'view_unit_documents', 'add_unit_document', 'edit_unit_document'
        ]
    ];
    
    return $defaultPermissions[$role] ?? [];
}

/**
 * دالة للتحقق من صلاحيات المستخدم
 */
function hasPermission($permission) {
    // إذا كان المستخدم مدير النظام
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        return true;
    }
    
    // الحصول على صلاحيات دور المستخدم
    $rolePermissions = getRolePermissions($_SESSION['user_role'] ?? '');
    
    return in_array($permission, $rolePermissions);
}

/**
 * دالة لتطبيق الصلاحيات عند إنشاء مستخدم جديد
 */
function applyRolePermissions($userId, $role) {
    global $pdo;
    
    try {
        // حذف الصلاحيات القديمة
        $stmt = $pdo->prepare("DELETE FROM permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // إضافة الصلاحيات الجديدة
        $permissions = getRolePermissions($role);
        if (!empty($permissions)) {
            $stmt = $pdo->prepare("INSERT INTO permissions (user_id, permission_name) VALUES (?, ?)");
            foreach ($permissions as $permission) {
                $stmt->execute([$userId, $permission]);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("خطأ في تطبيق صلاحيات الدور: " . $e->getMessage());
        return false;
    }
}
?>
