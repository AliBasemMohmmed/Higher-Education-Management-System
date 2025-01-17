<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من وجود الجدول وتحديثه
try {
    // الخطوة 1: إنشاء الجدول الأساسي
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS universities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(255),
            ministry_department_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // الخطوة 2: إضافة الأعمدة الجديدة بشكل منفصل
    $alterQueries = [
        "ALTER TABLE universities ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE universities ADD COLUMN IF NOT EXISTS created_by INT",
        "ALTER TABLE universities ADD COLUMN IF NOT EXISTS updated_by INT"
    ];

    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            error_log("خطأ في تنفيذ استعلام التعديل: " . $query . " - " . $e->getMessage());
            continue;
        }
    }

    // الخطوة 3: إضافة المفاتيح الأجنبية
    $foreignKeys = [
        "ALTER TABLE universities ADD CONSTRAINT fk_ministry_dept FOREIGN KEY (ministry_department_id) REFERENCES ministry_departments(id)",
        "ALTER TABLE universities ADD CONSTRAINT fk_created_by FOREIGN KEY (created_by) REFERENCES users(id)",
        "ALTER TABLE universities ADD CONSTRAINT fk_updated_by FOREIGN KEY (updated_by) REFERENCES users(id)"
    ];

    foreach ($foreignKeys as $fk) {
        try {
            $pdo->exec($fk);
        } catch (PDOException $e) {
            // تجاهل الأخطاء إذا كانت المفاتيح موجودة بالفعل
            error_log("ملاحظة عند إضافة المفتاح الأجنبي: " . $e->getMessage());
            continue;
        }
    }

} catch (PDOException $e) {
    error_log("خطأ في إعداد قاعدة البيانات: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ في إعداد قاعدة البيانات. يرجى الاتصال بمسؤول النظام.";
}

include 'header.php';
?>

<div class="container mt-4">
    <h2>إدارة الجامعات</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
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
                    <input type="text" name="location" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">القسم المسؤول</label>
                    <select name="ministry_department_id" class="form-control" required>
                        <option value="">اختر القسم</option>
                        <?php
                        $departments = $pdo->query("SELECT * FROM ministry_departments ORDER BY name")->fetchAll();
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

    <!-- عرض الجامعات -->
    <div class="card">
        <div class="card-header">
            الجامعات الحالية
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
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
                        try {
                            $stmt = $pdo->query("
                                SELECT u.*, d.name as department_name 
                                FROM universities u 
                                LEFT JOIN ministry_departments d ON u.ministry_department_id = d.id 
                                ORDER BY u.id DESC
                            ");
                            while ($row = $stmt->fetch()) {
                                echo "<tr>
                                        <td>{$row['id']}</td>
                                        <td>{$row['name']}</td>
                                        <td>{$row['location']}</td>
                                        <td>{$row['department_name']}</td>
                                        <td>{$row['created_at']}</td>
                                        <td class='text-nowrap'>";
                                if (hasPermission('edit_university')) {
                                    echo "<a href='edit_university.php?id={$row['id']}' class='btn btn-sm btn-primary me-1'>
                                            <i class='fas fa-edit'></i> تعديل
                                          </a>";
                                }
                                if (hasPermission('delete_university')) {
                                    echo "<a href='delete_university.php?id={$row['id']}' 
                                          class='btn btn-sm btn-danger'
                                          onclick='return confirm(\"هل أنت متأكد من حذف هذه الجامعة؟\")'>
                                            <i class='fas fa-trash'></i> حذف
                                          </a>";
                                }
                                echo "</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='6' class='text-danger'>حدث خطأ في عرض البيانات</td></tr>";
                            error_log($e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
