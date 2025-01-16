<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();
include 'header.php';
?>

<div class="container mt-4">
  <h2>إدارة الجامعات</h2>
  
  <?php if (hasPermission('add_university')): ?>
  <div class="card mb-4">
    <div class="card-header">
      إضافة جامعة جديدة
    </div>
    <div class="card-body">
      <form method="POST" action="process_university.php">
        <div class="mb-3">
          <label class="form-label">اسم الجامعة</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">الموقع</label>
          <input type="text" name="location" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">القسم المسؤول</label>
          <select name="ministry_department_id" class="form-control" required>
            <?php
            $departments = $pdo->query("SELECT * FROM ministry_departments")->fetchAll();
            foreach ($departments as $dept) {
              echo "<option value='{$dept['id']}'>{$dept['name']}</option>";
            }
            ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">إضافة الجامعة</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      الجامعات الحالية
    </div>
    <div class="card-body">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>اسم الجامعة</th>
            <th>الموقع</th>
            <th>القسم المسؤول</th>
            <th>تاريخ الإضافة</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->query("
            SELECT u.*, d.name as department_name 
            FROM universities u 
            JOIN ministry_departments d ON u.ministry_department_id = d.id 
            ORDER BY u.id DESC
          ");
          while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['location']}</td>
                    <td>{$row['department_name']}</td>
                    <td>{$row['created_at']}</td>
                    <td>";
            if (hasPermission('edit_university')) {
              echo "<a href='edit_university.php?id={$row['id']}' class='btn btn-sm btn-primary'>تعديل</a> ";
            }
            if (hasPermission('delete_university')) {
              echo "<a href='delete_university.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"هل أنت متأكد؟\")'>حذف</a>";
            }
            echo "</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
