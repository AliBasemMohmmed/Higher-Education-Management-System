<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();
include 'header.php';
?>

<div class="container mt-4">
  <h2>إدارة الشعب</h2>
  
  <?php if (hasPermission('add_division')): ?>
  <div class="card mb-4">
    <div class="card-header">
      إضافة شعبة جديدة
    </div>
    <div class="card-body">
      <form method="POST" action="process_division.php">
        <div class="mb-3">
          <label class="form-label">اسم الشعبة</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">الجامعة</label>
          <select name="university_id" class="form-control" required>
            <?php
            $universities = $pdo->query("SELECT * FROM universities")->fetchAll();
            foreach ($universities as $univ) {
              echo "<option value='{$univ['id']}'>{$univ['name']}</option>";
            }
            ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">إضافة الشعبة</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      الشعب الحالية
    </div>
    <div class="card-body">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>اسم الشعبة</th>
            <th>الجامعة</th>
            <th>تاريخ الإنشاء</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->query("
            SELECT d.*, u.name as university_name 
            FROM university_divisions d 
            JOIN universities u ON d.university_id = u.id 
            ORDER BY d.id DESC
          ");
          while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['university_name']}</td>
                    <td>{$row['created_at']}</td>
                    <td>";
            if (hasPermission('edit_division')) {
              echo "<a href='edit_division.php?id={$row['id']}' class='btn btn-sm btn-primary'>تعديل</a> ";
            }
            if (hasPermission('delete_division')) {
              echo "<a href='delete_division.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"هل أنت متأكد؟\")'>حذف</a>";
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
