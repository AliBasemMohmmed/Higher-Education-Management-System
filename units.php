<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();
include 'header.php';
?>

<div class="container mt-4">
  <h2>إدارة الوحدات</h2>
  
  <?php if (hasPermission('add_unit')): ?>
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
          <label class="form-label">الكلية</label>
          <select name="college_id" class="form-control" required>
            <?php
            $colleges = $pdo->query("SELECT * FROM colleges")->fetchAll();
            foreach ($colleges as $college) {
              echo "<option value='{$college['id']}'>{$college['name']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">الشعبة المسؤولة</label>
          <select name="division_id" class="form-control" required>
            <?php
            $divisions = $pdo->query("SELECT * FROM university_divisions")->fetchAll();
            foreach ($divisions as $division) {
              echo "<option value='{$division['id']}'>{$division['name']}</option>";
            }
            ?>
          </select>
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
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>اسم الوحدة</th>
            <th>الكلية</th>
            <th>الشعبة المسؤولة</th>
            <th>تاريخ الإنشاء</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->query("
            SELECT u.*, c.name as college_name, d.name as division_name 
            FROM units u 
            JOIN colleges c ON u.college_id = c.id 
            JOIN university_divisions d ON u.division_id = d.id 
            ORDER BY u.id DESC
          ");
          while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['college_name']}</td>
                    <td>{$row['division_name']}</td>
                    <td>{$row['created_at']}</td>
                    <td>";
            if (hasPermission('edit_unit')) {
              echo "<a href='edit_unit.php?id={$row['id']}' class='btn btn-sm btn-primary'>تعديل</a> ";
            }
            if (hasPermission('delete_unit')) {
              echo "<a href='delete_unit.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"هل أنت متأكد؟\")'>حذف</a>";
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
