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
            <form id="addUnitForm" method="POST" action="process_unit.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label">اسم الوحدة</label>
                    <input type="text" name="name" class="form-control" required>
                    <div class="invalid-feedback">يرجى إدخال اسم الوحدة</div>
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
                    <label class="form-label">الشعبة</label>
                    <select name="division_id" id="division_select" class="form-control" required>
                        <option value="">اختر الشعبة</option>
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
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>إضافة الوحدة
                </button>
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
                                    echo "<tr data-unit-id='{$row['id']}'>
                                            <td>{$row['id']}</td>
                                            <td class='unit-name'>{$row['name']}</td>
                                            <td>{$row['university_name']}</td>
                                            <td class='unit-description'>{$row['description']}</td>
                                            <td>" . date('Y-m-d H:i', strtotime($row['created_at'])) . "</td>
                                            <td class='text-nowrap'>";
                                    
                                    if ($userRole === 'admin' || 
                                        ($userEntityType === 'division' && hasPermission('edit_unit'))) {
                                        echo "<button onclick='editUnit({$row['id']})' class='btn btn-sm btn-outline-primary me-1 rounded-pill'>
                                                <i class='fas fa-edit'></i>
                                              </button>";
                                    }
                                    
                                    if ($userRole === 'admin' || 
                                        ($userEntityType === 'division' && hasPermission('delete_unit'))) {
                                        echo "<button onclick='deleteUnit({$row['id']}, `" . htmlspecialchars($row['name'], ENT_QUOTES) . "`)' 
                                              class='btn btn-sm btn-outline-danger rounded-pill'>
                                                <i class='fas fa-trash'></i>
                                              </button>";
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

<!-- إضافة مودال التعديل -->
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUnitModalLabel">تعديل الوحدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- سيتم تحميل نموذج التعديل هنا -->
            </div>
        </div>
    </div>
</div>

<script>
// دالة التعديل
async function editUnit(unitId) {
    try {
        // عرض مؤشر التحميل مع تأثيرات حركية
        Swal.fire({
            title: 'جاري تحميل البيانات',
            html: `
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-3x mb-3 text-primary"></i>
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                    <p class="mt-2 text-muted">يرجى الانتظار قليلاً...</p>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            showClass: {
                popup: 'animate__animated animate__fadeIn'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOut'
            }
        });

        // تأخير مصطنع لإظهار التحميل بشكل أوضح
        await new Promise(resolve => setTimeout(resolve, 800));

        // جلب بيانات الوحدة
        const response = await fetch(`get_unit_details.php?id=${unitId}`);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'حدث خطأ أثناء جلب البيانات');
        }

        // تأخير إضافي قبل إغلاق نافذة التحميل
        await new Promise(resolve => setTimeout(resolve, 400));

        // إغلاق مؤشر التحميل بتأثير حركي
        await Swal.close();

        // تأخير قصير قبل فتح المودال
        await new Promise(resolve => setTimeout(resolve, 200));

        // تحضير نموذج التعديل
        const modalContent = `
            <form id="editUnitForm" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="${data.id}">
                <input type="hidden" name="action" value="edit">
                
                <div class="mb-3">
                    <label class="form-label">اسم الوحدة</label>
                    <input type="text" name="name" class="form-control" value="${data.name}" required>
                    <div class="invalid-feedback">يرجى إدخال اسم الوحدة</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">الجامعة</label>
                    <select name="university_id" id="modal_university_select" class="form-select" required>
                        <option value="">اختر الجامعة</option>
                        ${data.universities.map(univ => `
                            <option value="${univ.id}" ${univ.id == data.university_id ? 'selected' : ''}>
                                ${univ.name}
                            </option>
                        `).join('')}
                    </select>
                    <div class="invalid-feedback">يرجى اختيار الجامعة</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">الكلية</label>
                    <select name="college_id" id="modal_college_select" class="form-select" required>
                        <option value="">اختر الكلية</option>
                        ${data.colleges.map(college => `
                            <option value="${college.id}" ${college.id == data.college_id ? 'selected' : ''}>
                                ${college.name}
                            </option>
                        `).join('')}
                    </select>
                    <div class="invalid-feedback">يرجى اختيار الكلية</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">الشعبة</label>
                    <select name="division_id" id="modal_division_select" class="form-select" required>
                        <option value="">اختر الشعبة</option>
                        ${data.divisions.map(division => `
                            <option value="${division.id}" ${division.id == data.division_id ? 'selected' : ''}>
                                ${division.name}
                            </option>
                        `).join('')}
                    </select>
                    <div class="invalid-feedback">يرجى اختيار الشعبة</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3">${data.description || ''}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">حالة الوحدة</label>
                    <select name="is_active" class="form-select">
                        <option value="1" ${data.is_active == 1 ? 'selected' : ''}>نشط</option>
                        <option value="0" ${data.is_active == 0 ? 'selected' : ''}>غير نشط</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ التعديلات
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                </div>
            </form>
        `;

        // عرض المودال
        document.querySelector('#editUnitModal .modal-body').innerHTML = modalContent;
        const modal = new bootstrap.Modal(document.getElementById('editUnitModal'));
        modal.show();

        // تفعيل الأحداث للنموذج
        const form = document.getElementById('editUnitForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!form.checkValidity()) {
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            try {
                const formData = new FormData(form);
                const response = await fetch('process_unit.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    modal.hide();
                    // تحديث صف الجدول مع تأثير حركي
                    const row = document.querySelector(`tr[data-unit-id="${data.id}"]`);
                    if (row) {
                        // إضافة تأثير وميض للصف
                        row.style.backgroundColor = '#28a745';
                        row.style.transition = 'background-color 0.5s';
                        
                        // تحديث البيانات في الصف
                        row.querySelector('.unit-name').textContent = formData.get('name');
                        row.querySelector('.unit-description').textContent = formData.get('description') || '';
                        
                        // إعادة لون الخلفية بعد الانتهاء
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 1000);
                    }

                    // عرض رسالة النجاح مع تأثيرات حركية
                    Swal.fire({
                        title: 'تم التعديل!',
                        text: 'تم تعديل الوحدة بنجاح',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true,
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        },
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    }).then(() => {
                        // تحديث الصفحة بتأثير حركي
                        document.body.style.opacity = '0';
                        document.body.style.transition = 'opacity 0.5s';
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    });
                } else {
                    throw new Error(result.message || 'حدث خطأ أثناء التعديل');
                }
            } catch (error) {
                Swal.fire({
                    title: 'خطأ!',
                    text: error.message,
                    icon: 'error'
                });
            }
        });

        // تفعيل التحديث التلقائي للقوائم المنسدلة
        document.getElementById('modal_university_select').addEventListener('change', async function() {
            const universityId = this.value;
            const collegeSelect = document.getElementById('modal_college_select');
            const divisionSelect = document.getElementById('modal_division_select');
            
            // تفريغ القوائم
            collegeSelect.innerHTML = '<option value="">اختر الكلية</option>';
            divisionSelect.innerHTML = '<option value="">اختر الشعبة</option>';
            
            if (universityId) {
                try {
                    // جلب الكليات
                    const collegesResponse = await fetch(`get_available_colleges.php?university_id=${universityId}`);
                    const colleges = await collegesResponse.json();
                    colleges.forEach(college => {
                        const option = document.createElement('option');
                        option.value = college.id;
                        option.textContent = college.name;
                        collegeSelect.appendChild(option);
                    });

                    // جلب الشعب
                    const divisionsResponse = await fetch(`get_divisions.php?university_id=${universityId}`);
                    const divisions = await divisionsResponse.json();
                    divisions.forEach(division => {
                        const option = document.createElement('option');
                        option.value = division.id;
                        option.textContent = division.name;
                        divisionSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error('Error:', error);
                }
            }
        });

    } catch (error) {
        Swal.fire({
            title: 'خطأ!',
            text: error.message,
            icon: 'error'
        });
    }
}

// دالة الحذف
async function deleteUnit(unitId, unitName) {
    const result = await Swal.fire({
        title: 'تأكيد الحذف',
        html: `
            <div class="text-danger">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <p>هل أنت متأكد من حذف الوحدة "${unitName}"؟</p>
                <p class="text-muted small">لا يمكن التراجع عن هذا الإجراء</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        customClass: {
            confirmButton: 'btn btn-danger mx-2',
            cancelButton: 'btn btn-secondary mx-2'
        },
        buttonsStyling: false,
        showClass: {
            popup: 'animate__animated animate__fadeIn'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOut'
        },
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            try {
                const response = await fetch(`delete_unit.php?id=${unitId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('فشل في الاتصال بالخادم');
                }

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'حدث خطأ أثناء الحذف');
                }

                return data;
            } catch (error) {
                Swal.showValidationMessage(`
                    <i class="fas fa-times-circle text-danger"></i>
                    فشل في الحذف: ${error.message}
                `);
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result.isConfirmed) {
        await Swal.fire({
            title: '<i class="fas fa-check-circle text-success"></i>',
            html: '<strong>تم الحذف بنجاح!</strong>',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            customClass: {
                popup: 'animate__animated animate__fadeInUp'
            }
        });

        // تحديث الصفحة
        window.location.reload();
    }
}

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

        // جلب الشعب المتاحة
        divisionSelect.disabled = true;
        divisionSelect.innerHTML = '<option value="">جاري التحميل...</option>';
        
        fetch(`get_divisions.php?university_id=${universityId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('حدث خطأ في جلب البيانات');
                }
                return response.json();
            })
            .then(divisions => {
                divisionSelect.innerHTML = '<option value="">اختر الشعبة</option>';
                if (divisions.length === 0) {
                    divisionSelect.innerHTML += '<option value="" disabled>لا توجد شعب متاحة</option>';
                } else {
                    divisions.forEach(division => {
                        const option = document.createElement('option');
                        option.value = division.id;
                        option.textContent = division.name;
                        divisionSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                divisionSelect.innerHTML = '<option value="">حدث خطأ في جلب البيانات</option>';
            })
            .finally(() => {
                divisionSelect.disabled = false;
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

// إضافة معالج النموذج
document.getElementById('addUnitForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }

    try {
        // عرض مؤشر التحميل
        Swal.fire({
            title: 'جاري إضافة الوحدة',
            html: `
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-3x mb-3 text-primary"></i>
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            showClass: {
                popup: 'animate__animated animate__fadeIn'
            }
        });

        const formData = new FormData(this);
        const response = await fetch('process_unit.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // إغلاق مؤشر التحميل
            Swal.close();

            // عرض رسالة النجاح مع تأثيرات حركية
            await Swal.fire({
                title: '<i class="fas fa-check-circle text-success fa-lg"></i>',
                html: `
                    <div class="animate__animated animate__fadeInUp">
                        <h4 class="text-success mb-3">تمت الإضافة بنجاح!</h4>
                        <p class="mb-0">تم إضافة الوحدة الجديدة بنجاح</p>
                    </div>
                `,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });

            // تحديث الصفحة بتأثير حركي
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            throw new Error(result.message || 'حدث خطأ أثناء إضافة الوحدة');
        }
    } catch (error) {
        Swal.fire({
            title: 'خطأ!',
            text: error.message,
            icon: 'error',
            showClass: {
                popup: 'animate__animated animate__shakeX'
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>
