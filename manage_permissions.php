<?php
require_once 'functions.php';
require_once 'auth.php';
require_once 'permissions_list.php';
requireLogin();

// التحقق من أن المستخدم مدير
if (!isAdmin()) {
    logSystemActivity('محاولة وصول غير مصرح لإدارة الصلاحيات', 'security_violation', $_SESSION['user_id']);
    header('Location: index.php');
    exit('غير مصرح لك بالوصول');
}

include 'header.php';

// معالجة تحديث الصلاحيات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $userId = $_POST['user_id'];
        $permissions = $_POST['permissions'] ?? [];
        
        // حذف الصلاحيات الحالية
        $stmt = $pdo->prepare("DELETE FROM permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // إضافة الصلاحيات الجديدة
        if (!empty($permissions)) {
            $stmt = $pdo->prepare("INSERT INTO permissions (user_id, permission_name) VALUES (?, ?)");
            foreach ($permissions as $permission) {
                $stmt->execute([$userId, $permission]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "تم تحديث الصلاحيات بنجاح";
        
        // تسجيل النشاط
        logSystemActivity("تم تحديث صلاحيات المستخدم #$userId", 'permissions', $_SESSION['user_id']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "حدث خطأ أثناء تحديث الصلاحيات";
        error_log($e->getMessage());
    }
}

// جلب قائمة المستخدمين مع صلاحياتهم
$users = $pdo->query("
    SELECT u.*, GROUP_CONCAT(p.permission_name) as current_permissions,
           COALESCE(ue.entity_type, 'none') as entity_type,
           CASE 
               WHEN ue.entity_type = 'division' THEN (
                   SELECT ud.name 
                   FROM university_divisions ud 
                   WHERE ud.id = ue.entity_id
               )
               ELSE ''
           END as entity_name
    FROM users u 
    LEFT JOIN permissions p ON u.id = p.user_id 
    LEFT JOIN user_entities ue ON u.id = ue.user_id AND ue.is_primary = 1
    WHERE u.role != 'admin'
    GROUP BY u.id
")->fetchAll();

// تنظيم الصلاحيات حسب المجموعات
$permissionGroups = [
    'الجامعات' => array_filter($available_permissions, fn($key) => strpos($key, 'university') !== false, ARRAY_FILTER_USE_KEY),
    'الكليات' => array_filter($available_permissions, fn($key) => strpos($key, 'college') !== false, ARRAY_FILTER_USE_KEY),
    'الشعب' => array_filter($available_permissions, fn($key) => strpos($key, 'division') !== false, ARRAY_FILTER_USE_KEY),
    'الوحدات' => array_filter($available_permissions, fn($key) => strpos($key, 'unit') !== false, ARRAY_FILTER_USE_KEY),
    'المستخدمين' => array_filter($available_permissions, fn($key) => strpos($key, 'user') !== false, ARRAY_FILTER_USE_KEY),
    'التقارير' => array_filter($available_permissions, fn($key) => strpos($key, 'report') !== false, ARRAY_FILTER_USE_KEY),
    'النظام' => array_filter($available_permissions, fn($key) => 
        strpos($key, 'log') !== false || 
        strpos($key, 'setting') !== false || 
        strpos($key, 'permission') !== false
    , ARRAY_FILTER_USE_KEY),
];
?>

<div class="container mt-4">
    <h2>إدارة صلاحيات المستخدمين</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>الدور</th>
                            <th>الانتماء الرئيسي</th>
                            <th>الصلاحيات الحالية</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <?php 
                                    if ($user['entity_type'] !== 'none') {
                                        echo htmlspecialchars($user['entity_type'] . ': ' . $user['entity_name']);
                                    } else {
                                        echo 'لا يوجد';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php 
                                        $currentPermissions = explode(',', $user['current_permissions']);
                                        $currentPermissions = array_filter($currentPermissions);
                                        echo implode(', ', array_map(function($p) use ($available_permissions) {
                                            return $available_permissions[$p] ?? $p;
                                        }, $currentPermissions));
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#permissionsModal<?php echo $user['id']; ?>">
                                        تعديل الصلاحيات
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Modal for each user -->
                            <div class="modal fade" id="permissionsModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    تعديل صلاحيات: <?php echo htmlspecialchars($user['full_name']); ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                
                                                <?php foreach ($permissionGroups as $groupName => $permissions): ?>
                                                    <div class="card mb-3">
                                                        <div class="card-header">
                                                            <h6 class="mb-0"><?php echo $groupName; ?></h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <?php foreach ($permissions as $key => $label): ?>
                                                                    <div class="col-md-6 mb-2">
                                                                        <div class="form-check">
                                                                            <input type="checkbox" 
                                                                                   name="permissions[]" 
                                                                                   value="<?php echo $key; ?>" 
                                                                                   class="form-check-input"
                                                                                   <?php echo in_array($key, $currentPermissions) ? 'checked' : ''; ?>>
                                                                            <label class="form-check-label">
                                                                                <?php echo $label; ?>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 