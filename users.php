<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

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
                <!-- سيتم ملء هذه القائمة ديناميكياً باستخدام JavaScript -->
              </select>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">الصلاحيات</label>
          <div class="row">
            <?php
            $permissions = [
              'manage_users' => 'إدارة المستخدمين',
              'add_document' => 'إضافة كتب',
              'edit_document' => 'تعديل الكتب',
              'delete_document' => 'حذف الكتب',
              'add_report' => 'إضافة تقارير',
              'edit_report' => 'تعديل التقارير',
              'delete_report' => 'حذف التقارير',
              'export_documents' => 'تصدير البيانات'
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

  <div class="card">
    <div class="card-header">
      المستخدمون الحاليون
    </div>
    <div class="card-body">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>اسم المستخدم</th>
            <th>الاسم الكامل</th>
            <th>البريد الإلكتروني</th>
            <th>الدور</th>
            <th>تاريخ الإنشاء</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
          foreach ($users as $user): ?>
            <tr>
              <td><?php echo $user['id']; ?></td>
              <td><?php echo $user['username']; ?></td>
              <td><?php echo $user['full_name']; ?></td>
              <td><?php echo $user['email']; ?></td>
              <td><?php echo $user['role']; ?></td>
              <td><?php echo $user['created_at']; ?></td>
              <td>
                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">تعديل</a>
                <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" 
                   onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">حذف</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
