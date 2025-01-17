<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من الصلاحيات
if (!hasPermission('manage_users')) {
    die('غير مصرح لك بإدارة انتماءات المستخدمين');
}

$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    die('معرف المستخدم مطلوب');
}

// جلب معلومات المستخدم
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    die('المستخدم غير موجود');
}

// جلب جميع الجهات المتاحة
$ministries = $pdo->query("SELECT id, name FROM ministry_departments")->fetchAll();
$divisions = $pdo->query("SELECT id, name FROM university_divisions")->fetchAll();
$units = $pdo->query("SELECT id, name FROM units")->fetchAll();

// جلب انتماءات المستخدم الحالية
$userEntities = getUserEntities($userId);

include 'header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3>إدارة انتماءات المستخدم: <?php echo htmlspecialchars($user['full_name']); ?></h3>
                </div>
                <div class="card-body">
                    <!-- عرض الانتماءات الحالية -->
                    <h5 class="mb-3">الانتماءات الحالية</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>نوع الجهة</th>
                                    <th>اسم الجهة</th>
                                    <th>رئيسية</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userEntities as $entity): ?>
                                    <tr>
                                        <td><?php echo getEntityTypeLabel($entity['entity_type']); ?></td>
                                        <td><?php echo htmlspecialchars($entity['entity_name']); ?></td>
                                        <td>
                                            <?php if ($entity['is_primary']): ?>
                                                <span class="badge bg-success">نعم</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">لا</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if (!$entity['is_primary']): ?>
                                                    <a href="process_user_entity.php?action=make_primary&user_id=<?php echo $userId; ?>&entity_type=<?php echo $entity['entity_type']; ?>&entity_id=<?php echo $entity['entity_id']; ?>" 
                                                       class="btn btn-sm btn-success" title="جعل رئيسية">
                                                        <i class="fas fa-star"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="process_user_entity.php?action=remove&user_id=<?php echo $userId; ?>&entity_type=<?php echo $entity['entity_type']; ?>&entity_id=<?php echo $entity['entity_id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('هل أنت متأكد من إزالة هذا الانتماء؟')" 
                                                   title="إزالة">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- نموذج إضافة انتماء جديد -->
                    <h5 class="mb-3">إضافة انتماء جديد</h5>
                    <form action="process_user_entity.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">نوع الجهة</label>
                                <select name="entity_type" class="form-select" required id="entityType">
                                    <option value="">اختر نوع الجهة</option>
                                    <option value="ministry">قسم الوزارة</option>
                                    <option value="division">شعبة</option>
                                    <option value="unit">وحدة</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">الجهة</label>
                                <select name="entity_id" class="form-select" required id="entityId">
                                    <option value="">اختر الجهة</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">رئيسية</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="isPrimary">
                                    <label class="form-check-label" for="isPrimary">
                                        جهة رئيسية
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> إضافة انتماء
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// تحديث قائمة الجهات عند تغيير النوع
const entityData = {
    ministry: <?php echo json_encode($ministries); ?>,
    division: <?php echo json_encode($divisions); ?>,
    unit: <?php echo json_encode($units); ?>
};

document.getElementById('entityType').addEventListener('change', function() {
    const entitySelect = document.getElementById('entityId');
    entitySelect.innerHTML = '<option value="">اختر الجهة</option>';
    
    const entities = entityData[this.value] || [];
    entities.forEach(entity => {
        entitySelect.innerHTML += `<option value="${entity.id}">${entity.name}</option>`;
    });
});
</script>

<?php include 'footer.php'; ?> 