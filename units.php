<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من الصلاحيات
if (!hasPermission('view_units')) {
    die('غير مصرح لك بعرض الوحدات');
}

// التحقق من الانتماء الرئيسي للمستخدم
$userRole = $_SESSION['user_role'];
$userEntityType = $_SESSION['entity_type'] ?? null;
$userDivisionId = null;

if ($userEntityType === 'division') {
    $stmt = $pdo->prepare("
        SELECT ue.entity_id, ud.university_id
        FROM user_entities ue 
        INNER JOIN university_divisions ud ON ue.entity_id = ud.id
        WHERE ue.user_id = ? 
        AND ue.entity_type = 'division' 
        AND ue.is_primary = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $userDivisionId = $result ? $result['entity_id'] : null;
    $userUniversityId = $result ? $result['university_id'] : null;
}

include 'header.php';
?>

<div class="container mt-4">
    <h2>إدارة الوحدات</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($userRole === 'admin' || ($userEntityType === 'division' && hasPermission('add_unit'))): ?>
    <div class="card mb-4">
        <div class="card-header">
            إضافة وحدة جديدة
        </div>
        <div class="card-body">
            <form method="POST" action="process_unit.php">
                <div class="mb-3">
                    <label class="form-label">اسم الوحدة</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">الجامعة</label>
                    <select name="university_id" id="university_select" class="form-control" required>
                        <option value="">اختر الجامعة</option>
                        <?php
                        $universities = $pdo->query("SELECT * FROM universities ORDER BY name")->fetchAll();
                        foreach ($universities as $univ) {
                            echo "<option value='{$univ['id']}'>{$univ['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">الكلية</label>
                    <select name="college_id" id="college_select" class="form-control" required>
                        <option value="">اختر الكلية</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">مدير الوحدة</label>
                    <select name="unit_manager_id" id="unit_manager_select" class="form-control" required>
                        <option value="">اختر مدير الوحدة</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">إضافة الوحدة</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            الوحدات الحالية
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم الوحدة</th>
                            <th>الجامعة</th>
                            <th>الوصف</th>
                            <th>تاريخ الإضافة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // إذا كان المستخدم أدمن، اعرض كل الوحدات
                            if ($userRole === 'admin') {
                                $stmt = $pdo->query("
                                    SELECT u.*, un.name as university_name,
                                           COALESCE(creator.full_name, 'غير معروف') as created_by_name,
                                           COALESCE(updater.full_name, 'غير معروف') as updated_by_name
                                    FROM units u 
                                    LEFT JOIN universities un ON u.university_id = un.id 
                                    LEFT JOIN users creator ON u.created_by = creator.id
                                    LEFT JOIN users updater ON u.updated_by = updater.id
                                    ORDER BY u.id DESC
                                ");
                            } 
                            // إذا كان مدير شعبة، اعرض فقط الوحدات التابعة لجامعته
                            else {
                                $stmt = $pdo->prepare("
                                    SELECT u.*, un.name as university_name,
                                           COALESCE(creator.full_name, 'غير معروف') as created_by_name,
                                           COALESCE(updater.full_name, 'غير معروف') as updated_by_name
                                    FROM units u 
                                    INNER JOIN universities un ON u.university_id = un.id 
                                    INNER JOIN university_divisions ud ON un.id = ud.university_id
                                    INNER JOIN user_entities ue ON ud.id = ue.entity_id
                                    LEFT JOIN users creator ON u.created_by = creator.id
                                    LEFT JOIN users updater ON u.updated_by = updater.id
                                    WHERE ue.user_id = ? 
                                    AND ue.entity_type = 'division'
                                    AND ue.is_primary = 1
                                    ORDER BY u.id DESC
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                            }

                            $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (empty($units)) {
                                echo "<tr><td colspan='6' class='text-center'>لا توجد وحدات لعرضها</td></tr>";
                            } else {
                                foreach ($units as $row) {
                                    echo "<tr>
                                            <td>{$row['id']}</td>
                                            <td>{$row['name']}</td>
                                            <td>{$row['university_name']}</td>
                                            <td>{$row['description']}</td>
                                            <td>" . date('Y-m-d H:i', strtotime($row['created_at'])) . "</td>
                                            <td class='text-nowrap'>";
                                    
                                    if ($userRole === 'admin' || 
                                        ($userEntityType === 'division' && hasPermission('edit_unit'))) {
                                        echo "<a href='edit_unit.php?id={$row['id']}' class='btn btn-sm btn-primary me-1'>
                                                <i class='fas fa-edit'></i> تعديل
                                              </a>";
                                    }
                                    
                                    if ($userRole === 'admin' || 
                                        ($userEntityType === 'division' && hasPermission('delete_unit'))) {
                                        echo "<a href='delete_unit.php?id={$row['id']}' 
                                              class='btn btn-sm btn-danger'
                                              onclick='return confirm(\"هل أنت متأكد من حذف هذه الوحدة؟\")'>
                                                <i class='fas fa-trash'></i> حذف
                                              </a>";
                                    }
                                    echo "</td></tr>";
                                }
                            }
                        } catch (PDOException $e) {
                            error_log("خطأ في عرض الوحدات: " . $e->getMessage());
                            echo "<tr><td colspan='6' class='text-danger'>حدث خطأ في عرض البيانات. الرجاء المحاولة مرة أخرى لاحقاً.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- إضافة JavaScript لتحديث قائمة الكليات -->
<script>
document.getElementById('university_select').addEventListener('change', function() {
    const universityId = this.value;
    const collegeSelect = document.getElementById('college_select');
    const managerSelect = document.getElementById('unit_manager_select');
    
    // تفريغ القوائم
    collegeSelect.innerHTML = '<option value="">اختر الكلية</option>';
    managerSelect.innerHTML = '<option value="">اختر مدير الوحدة</option>';
    
    if (universityId) {
        // إظهار رسالة تحميل للكليات
        collegeSelect.disabled = true;
        collegeSelect.innerHTML = '<option value="">جاري التحميل...</option>';
        
        // جلب الكليات المتاحة
        fetch(`get_available_colleges.php?university_id=${universityId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('حدث خطأ في جلب البيانات');
                }
                return response.json();
            })
            .then(colleges => {
                collegeSelect.innerHTML = '<option value="">اختر الكلية</option>';
                if (colleges.length === 0) {
                    collegeSelect.innerHTML += '<option value="" disabled>لا توجد كليات متاحة</option>';
                } else {
                    colleges.forEach(college => {
                        const option = document.createElement('option');
                        option.value = college.id;
                        option.textContent = college.name;
                        collegeSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                collegeSelect.innerHTML = '<option value="">حدث خطأ في جلب البيانات</option>';
            })
            .finally(() => {
                collegeSelect.disabled = false;
            });
    }
});

document.getElementById('college_select').addEventListener('change', function() {
    const universityId = document.getElementById('university_select').value;
    const collegeId = this.value;
    const managerSelect = document.getElementById('unit_manager_select');
    
    // تفريغ القائمة
    managerSelect.innerHTML = '<option value="">اختر مدير الوحدة</option>';
    
    if (collegeId) {
        // إظهار رسالة تحميل
        managerSelect.disabled = true;
        managerSelect.innerHTML = '<option value="">جاري التحميل...</option>';
        
        // جلب المستخدمين حسب الجامعة والكلية
        fetch(`get_unit_users.php?university_id=${universityId}&college_id=${collegeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('حدث خطأ في جلب البيانات');
                }
                return response.json();
            })
            .then(users => {
                managerSelect.innerHTML = '<option value="">اختر مدير الوحدة</option>';
                if (users.length === 0) {
                    managerSelect.innerHTML += '<option value="" disabled>لا يوجد مستخدمين متاحين</option>';
                } else {
                    users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.full_name;
                        managerSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                managerSelect.innerHTML = '<option value="">حدث خطأ في جلب البيانات</option>';
            })
            .finally(() => {
                managerSelect.disabled = false;
            });
    }
});
</script>

<?php include 'footer.php'; ?>
