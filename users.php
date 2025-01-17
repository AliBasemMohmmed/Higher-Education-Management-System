<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من وجود العمود وإضافته
try {
    $alterQueries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS university_id INT AFTER role",
        "ALTER TABLE users ADD CONSTRAINT IF NOT EXISTS fk_users_university FOREIGN KEY (university_id) REFERENCES universities(id)"
    ];

    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            error_log("خطأ في تنفيذ استعلام التعديل: " . $query . " - " . $e->getMessage());
            continue;
        }
    }
} catch (PDOException $e) {
    error_log("خطأ في تحديث جدول المستخدمين: " . $e->getMessage());
}

// تحقق من صلاحيات إدارة المستخدمين
if (!hasPermission('manage_users')) {
  die('غير مصرح لك بإدارة المستخدمين');
}

include 'header.php';
?>

<div class="container mt-4">
  <h2>إدارة المستخدمين</h2>
  
  <div class="card mb-4">
    <div class="card-header">
      إضافة مستخدم جديد
    </div>
    <div class="card-body">
      <form method="POST" action="process_user.php">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">اسم المستخدم</label>
              <input type="text" name="username" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">الاسم الكامل</label>
              <input type="text" name="full_name" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">البريد الإلكتروني</label>
              <input type="email" name="email" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">كلمة المرور</label>
              <input type="password" name="password" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">الدور</label>
              <select name="role" class="form-control" required>
                <option value="admin">مدير النظام</option>
                <option value="ministry">موظف وزارة</option>
                <option value="division">موظف شعبة</option>
                <option value="unit">موظف وحدة</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">الجهة</label>
              <select name="entity_id" class="form-control">
                <option value="">اختر الجهة</option>
                <?php
                $stmt = $pdo->query("SELECT id, name FROM universities ORDER BY name");
                while ($row = $stmt->fetch()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
                <!-- سيتم ملء هذه القائمة ديناميكياً باستخدام JavaScript -->
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">الجامعة</label>
              <select name="university_id" class="form-control">
                <option value="">اختر الجامعة</option>
                <?php
                $stmt = $pdo->query("SELECT id, name FROM universities ORDER BY name");
                while ($row = $stmt->fetch()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
              </select>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">الصلاحيات</label>
          <div class="row">
            <?php
            $permissions = [
                // صلاحيات المستخدمين
    'manage_users' => 'إدارة المستخدمين',
    'view_users' => 'عرض المستخدمين',
    'add_user' => 'إضافة مستخدم',
    'edit_user' => 'تعديل مستخدم',
    'delete_user' => 'حذف مستخدم',
    
    // صلاحيات الجامعات
    'manage_universities' => 'إدارة الجامعات',
    'view_universities' => 'عرض الجامعات',
    'add_university' => 'إضافة جامعة',
    'edit_university' => 'تعديل جامعة',
    'delete_university' => 'حذف جامعة',
    
    // صلاحيات الكليات
    'manage_colleges' => 'إدارة الكليات',
    'view_colleges' => 'عرض الكليات',
    'add_college' => 'إضافة كلية',
    'edit_college' => 'تعديل كلية',
    'delete_college' => 'حذف كلية',
    
    // صلاحيات الأقسام الوزارية
    'manage_ministry_departments' => 'إدارة الأقسام الوزارية',
    'view_ministry_departments' => 'عرض الأقسام الوزارية',
    'add_ministry_department' => 'إضافة قسم وزاري',
    'edit_ministry_department' => 'تعديل قسم وزاري',
    'delete_ministry_department' => 'حذف قسم وزاري',
    
    // صلاحيات الشعب الجامعية
    'manage_divisions' => 'إدارة الشعب الجامعية',
    'view_divisions' => 'عرض الشعب الجامعية',
    'add_division' => 'إضافة شعبة جامعية',
    'edit_division' => 'تعديل شعبة جامعية',
    'delete_division' => 'حذف شعبة جامعية',
    
    // صلاحيات الوحدات
    'manage_units' => 'إدارة الوحدات',
    'view_units' => 'عرض الوحدات',
    'add_unit' => 'إضافة وحدة',
    'edit_unit' => 'تعديل وحدة',
    'delete_unit' => 'حذف وحدة',
    
    // صلاحيات المراسلات والكتب
    'manage_correspondence' => 'إدارة المراسلات',
    'view_correspondence' => 'عرض المراسلات',
    'add_correspondence' => 'إضافة مراسلة',
    'edit_correspondence' => 'تعديل مراسلة',
    'delete_correspondence' => 'حذف مراسلة',
    
    // صلاحيات التقارير
    'manage_reports' => 'إدارة التقارير',
    'view_reports' => 'عرض التقارير',
    'generate_reports' => 'إنشاء التقارير',
    'export_reports' => 'تصدير التقارير',
    
    // صلاحيات النظام
    'view_logs' => 'عرض سجلات النظام',
    'manage_settings' => 'إدارة إعدادات النظام',
    'manage_permissions' => 'إدارة الصلاحيات',
    'view_statistics' => 'عرض الإحصائيات'
            ];
            
            foreach ($permissions as $key => $label): ?>
              <div class="col-md-3">
                <div class="form-check">
                  <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" class="form-check-input">
                  <label class="form-check-label"><?php echo $label; ?></label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">إضافة المستخدم</button>
      </form>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>اسم المستخدم</th>
              <th>الاسم الكامل</th>
              <th>البريد الإلكتروني</th>
              <th>الدور</th>
              <th>الجامعة</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $users = $pdo->query("
                SELECT u.*, COALESCE(un.name, 'غير محدد') as university_name 
                FROM users u 
                LEFT JOIN universities un ON u.university_id = un.id 
                ORDER BY u.id DESC
            ")->fetchAll();
            
            foreach ($users as $user) {
                echo "<tr>
                        <td>{$user['id']}</td>
                        <td>{$user['username']}</td>
                        <td>{$user['full_name']}</td>
                        <td>{$user['email']}</td>
                        <td>{$user['role']}</td>
                        <td>{$user['university_name']}</td>
                        <td>
                            <div class='btn-group'>
                                <a href='edit_user.php?id={$user['id']}' class='btn btn-sm btn-primary'>
                                    <i class='fas fa-edit'></i> تعديل
                                </a>
                                <a href='manage_user_entities.php?user_id={$user['id']}' class='btn btn-sm btn-info'>
                                    <i class='fas fa-building'></i> الجهات
                                </a>
                                <a href='delete_user.php?id={$user['id']}' class='btn btn-sm btn-danger' 
                                   onclick='return confirm(\"هل أنت متأكد من حذف هذا المستخدم؟\")'>
                                    <i class='fas fa-trash'></i> حذف
                                </a>
                            </div>
                        </td>
                    </tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
