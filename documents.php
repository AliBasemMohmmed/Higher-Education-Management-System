<?php
require_once 'functions.php';
include 'header.php';
?>

<div class="container mt-4">
  <h2>إدارة الكتب والمراسلات</h2>
  
  <!-- نموذج إضافة كتاب جديد -->
  <div class="card mb-4">
    <div class="card-header">
      إضافة كتاب جديد
    </div>
    <div class="card-body">
      <form method="POST" action="process_document.php" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">عنوان الكتاب</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">المحتوى</label>
          <textarea name="content" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">الملف المرفق</label>
          <input type="file" name="document_file" class="form-control">
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">نوع المرسل</label>
              <select name="sender_type" class="form-control" required>
                <option value="ministry">قسم الوزارة</option>
                <option value="division">شعبة</option>
                <option value="unit">وحدة</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">المرسل</label>
              <select name="sender_id" class="form-control" required>
                <!-- سيتم ملء هذه القائمة ديناميكياً باستخدام JavaScript -->
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">نوع المستلم</label>
              <select name="receiver_type" class="form-control" required>
                <option value="division">شعبة</option>
                <option value="unit">وحدة</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">المستلم</label>
              <select name="receiver_id" class="form-control" required>
                <!-- سيتم ملء هذه القائمة ديناميكياً باستخدام JavaScript -->
              </select>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">إرسال الكتاب</button>
      </form>
    </div>
  </div>

  <!-- عرض الكتب والمراسلات -->
  <div class="card">
    <div class="card-header">
      الكتب والمراسلات
    </div>
    <div class="card-body">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>العنوان</th>
            <th>المرسل</th>
            <th>المستلم</th>
            <th>الحالة</th>
            <th>تاريخ الإرسال</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->query("SELECT * FROM documents ORDER BY created_at DESC");
          while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['title']}</td>
                    <td>{$row['sender_type']} - {$row['sender_id']}</td>
                    <td>{$row['receiver_type']} - {$row['receiver_id']}</td>
                    <td>{$row['status']}</td>
                    <td>{$row['created_at']}</td>
                    <td>
                      <a href='view_document.php?id={$row['id']}' class='btn btn-sm btn-info'>عرض</a>
                      <a href='delete_document.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"هل أنت متأكد؟\")'>حذف</a>
                    </td>
                  </tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
