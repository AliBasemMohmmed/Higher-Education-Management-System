<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من الصلاحيات
if (!hasPermission('edit_unit')) {
    die('غير مصرح لك بتعديل الوحدات');
}

// التحقق من وجود معرف الوحدة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'معرف الوحدة غير صحيح';
    header('Location: units.php');
    exit;
}

$unitId = (int)$_GET['id'];

try {
    // جلب بيانات الوحدة مع كل العلاقات في استعلام واحد
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            un.name as university_name,
            c.name as college_name,
            d.name as division_name,
            creator.full_name as created_by_name,
            updater.full_name as updated_by_name
        FROM units u
        LEFT JOIN universities un ON u.university_id = un.id
        LEFT JOIN colleges c ON u.college_id = c.id
        LEFT JOIN university_divisions d ON u.division_id = d.id
        LEFT JOIN users creator ON u.created_by = creator.id
        LEFT JOIN users updater ON u.updated_by = updater.id
        WHERE u.id = ?
    ");
    
    $stmt->execute([$unitId]);
    $unit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$unit) {
        $_SESSION['error'] = 'الوحدة غير موجودة';
        header('Location: units.php');
        exit;
    }

    // تعيين القيم الافتراضية إذا كانت غير موجودة
    $unit = array_merge([
        'name' => '',
        'university_id' => '',
        'university_name' => 'غير محدد',
        'college_id' => '',
        'college_name' => 'غير محدد',
        'division_id' => '',
        'division_name' => 'غير محدد',
        'description' => '',
        'created_by_name' => 'غير معروف',
        'updated_by_name' => 'غير معروف',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => null,
        'is_active' => 1
    ], $unit);

    // التحقق من صلاحية المستخدم لتعديل هذه الوحدة
    if ($_SESSION['user_role'] !== 'admin') {
        $stmt = $pdo->prepare("
            SELECT 1 
            FROM user_entities ue
            INNER JOIN university_divisions ud ON ue.entity_id = ud.id
            WHERE ue.user_id = ? 
            AND ue.entity_type = 'division'
            AND ue.is_primary = 1
            AND ud.university_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $unit['university_id']]);
        
        if (!$stmt->fetch()) {
            $_SESSION['error'] = 'غير مصرح لك بتعديل هذه الوحدة';
            header('Location: units.php');
            exit;
        }
    }

} catch (PDOException $e) {
    error_log("خطأ في جلب بيانات الوحدة: " . $e->getMessage());
    $_SESSION['error'] = 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage();
    header('Location: units.php');
    exit;
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>تعديل الوحدة
                    </h5>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <form method="POST" action="process_unit.php" class="needs-validation" novalidate>
                        <input type="hidden" name="id" value="<?php echo $unit['id']; ?>">
                        <input type="hidden" name="action" value="edit">
                        
                        <div class="mb-3">
                            <label class="form-label">اسم الوحدة</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($unit['name']); ?>" required>
                            <div class="invalid-feedback">يرجى إدخال اسم الوحدة</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الجامعة</label>
                            <select name="university_id" id="university_select" class="form-select" required>
                                <option value="">اختر الجامعة</option>
                                <?php
                                $universities = $pdo->query("SELECT * FROM universities ORDER BY name")->fetchAll();
                                foreach ($universities as $univ) {
                                    $selected = $univ['id'] == $unit['university_id'] ? 'selected' : '';
                                    echo "<option value='{$univ['id']}' {$selected}>{$univ['name']}</option>";
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">يرجى اختيار الجامعة</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الكلية</label>
                            <select name="college_id" id="college_select" class="form-select" required>
                                <option value="<?php echo $unit['college_id']; ?>"><?php echo $unit['college_name']; ?></option>
                            </select>
                            <div class="invalid-feedback">يرجى اختيار الكلية</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الشعبة</label>
                            <select name="division_id" id="division_select" class="form-select" required>
                                <option value="<?php echo $unit['division_id']; ?>"><?php echo $unit['division_name']; ?></option>
                            </select>
                            <div class="invalid-feedback">يرجى اختيار الشعبة</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($unit['description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">حالة الوحدة</label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?php echo $unit['is_active'] == 1 ? 'selected' : ''; ?>>نشط</option>
                                <option value="0" <?php echo $unit['is_active'] == 0 ? 'selected' : ''; ?>>غير نشط</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ التعديلات
                            </button>
                            <a href="units.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>إلغاء
                            </a>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-muted">
                    <small>
                        <i class="fas fa-user me-1"></i>تم الإنشاء بواسطة: <?php echo htmlspecialchars($unit['created_by_name']); ?> | 
                        <i class="fas fa-calendar me-1"></i>تاريخ الإنشاء: <?php echo date('Y-m-d H:i', strtotime($unit['created_at'])); ?>
                        <?php if ($unit['updated_at']): ?>
                            <br>
                            <i class="fas fa-user-edit me-1"></i>آخر تحديث بواسطة: <?php echo htmlspecialchars($unit['updated_by_name']); ?> | 
                            <i class="fas fa-calendar-check me-1"></i>تاريخ التحديث: <?php echo date('Y-m-d H:i', strtotime($unit['updated_at'])); ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// تفعيل التحقق من صحة النموذج
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// نفس الكود السابق للتعامل مع تغيير الجامعة والكلية
document.getElementById('university_select').addEventListener('change', function() {
    const universityId = this.value;
    const collegeSelect = document.getElementById('college_select');
    const divisionSelect = document.getElementById('division_select');
    const managerSelect = document.getElementById('unit_manager_select');
    
    // تفريغ القوائم
    collegeSelect.innerHTML = '<option value="">اختر الكلية</option>';
    divisionSelect.innerHTML = '<option value="">اختر الشعبة</option>';
    managerSelect.innerHTML = '<option value="">اختر مدير الوحدة</option>';
    
    if (universityId) {
        // جلب الكليات المتاحة
        fetch(`get_available_colleges.php?university_id=${universityId}`)
            .then(response => response.json())
            .then(colleges => {
                collegeSelect.innerHTML = '<option value="">اختر الكلية</option>';
                colleges.forEach(college => {
                    const option = document.createElement('option');
                    option.value = college.id;
                    option.textContent = college.name;
                    collegeSelect.appendChild(option);
                });
            });

        // جلب الشعب المتاحة
        fetch(`get_divisions.php?university_id=${universityId}`)
            .then(response => response.json())
            .then(divisions => {
                divisionSelect.innerHTML = '<option value="">اختر الشعبة</option>';
                divisions.forEach(division => {
                    const option = document.createElement('option');
                    option.value = division.id;
                    option.textContent = division.name;
                    divisionSelect.appendChild(option);
                });
            });
    }
});

document.getElementById('college_select').addEventListener('change', function() {
    const universityId = document.getElementById('university_select').value;
    const collegeId = this.value;
    const managerSelect = document.getElementById('unit_manager_select');
    
    if (collegeId) {
        // جلب المستخدمين المتاحين
        fetch(`get_unit_users.php?university_id=${universityId}&college_id=${collegeId}`)
            .then(response => response.json())
            .then(users => {
                managerSelect.innerHTML = '<option value="">اختر مدير الوحدة</option>';
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.full_name;
                    managerSelect.appendChild(option);
                });
            });
    }
});

// تحديد القيم الافتراضية عند تحميل الصفحة
window.addEventListener('load', function() {
    const universitySelect = document.getElementById('university_select');
    if (universitySelect.value) {
        universitySelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include 'footer.php'; ?> 