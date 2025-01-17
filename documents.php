<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// إضافة ملفات CSS و JavaScript في header.php
$additionalCSS = '<link rel="stylesheet" href="assets/css/documents.css">';
$additionalJS = '<script src="assets/js/documents.js" defer></script>';

include 'header.php';

// تأكد من وجود متغير القائمة المنسدلة
echo '<div class="context-menu"></div>';

// جلب الكتب حسب صلاحيات المستخدم
$userRole = getUserRole();
$userId = $_SESSION['user_id'];

$sql = "SELECT d.*, 
        CASE 
            WHEN d.sender_type = 'ministry' THEN (SELECT name FROM ministry_departments WHERE id = d.sender_id)
            WHEN d.sender_type = 'division' THEN (SELECT name FROM university_divisions WHERE id = d.sender_id)
            WHEN d.sender_type = 'unit' THEN (SELECT name FROM units WHERE id = d.sender_id)
            WHEN d.sender_type = 'user' THEN (SELECT full_name FROM users WHERE id = d.sender_id)
        END as sender_name,
        CASE 
            WHEN d.receiver_type = 'ministry' THEN (SELECT name FROM ministry_departments WHERE id = d.receiver_id)
            WHEN d.receiver_type = 'division' THEN (SELECT name FROM university_divisions WHERE id = d.receiver_id)
            WHEN d.receiver_type = 'unit' THEN (SELECT name FROM units WHERE id = d.receiver_id)
        END as receiver_name
        FROM documents d
        WHERE 1=1";

// تطبيق الفلترة حسب الصلاحيات
if (!hasPermission('view_all_documents')) {
    $sql .= " AND (
        (d.sender_type = ? AND d.sender_id = ?) OR 
        (d.receiver_type = ? AND d.receiver_id = ?)
    )";
    $params = [$_SESSION['entity_type'], $_SESSION['entity_id'], 
               $_SESSION['entity_type'], $_SESSION['entity_id']];
}

$sql .= " ORDER BY d.created_at DESC";
?>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2>إدارة الكتب والمراسلات</h2>
        </div>
        <div class="col-auto">
            <?php if (hasPermission('create_documents')): ?>
            <a href="create_document.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> إنشاء كتاب جديد
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- تصفية وبحث -->
    <div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">حالة الكتاب</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>مسودة</option>
                    <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>قيد الإرسال</option>
                    <option value="sent" <?php echo ($_GET['status'] ?? '') === 'sent' ? 'selected' : ''; ?>>تم الإرسال</option>
                    <option value="received" <?php echo ($_GET['status'] ?? '') === 'received' ? 'selected' : ''; ?>>تم الاستلام</option>
                    <option value="processed" <?php echo ($_GET['status'] ?? '') === 'processed' ? 'selected' : ''; ?>>تمت المعالجة</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $_GET['start_date'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $_GET['end_date'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">بحث</label>
                <input type="text" name="search" class="form-control" placeholder="ابحث في العنوان أو المحتوى..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-secondary" id="filterBtn">
                    <i class="fas fa-filter"></i> تطبيق الفلتر
                </button>
            </div>
        </form>
    </div>
</div> 

    <!-- إضافة حقل البحث -->
    <div class="mb-3">
        <input type="text" 
               id="searchTable" 
               class="form-control" 
               placeholder="ابحث في الجدول..."
               style="width: 300px;">
    </div>

    <!-- عرض الكتب -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>الكتب والمراسلات</span>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="exportBtn" title="تصدير Excel">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="printBtn" title="طباعة">
                    <i class="fas fa-print"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العنوان</th>
                            <th>المرسل</th>
                            <th>المستلم</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>آخر تحديث</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare($sql);
                        if (isset($params)) {
                            $stmt->execute($params);
                        } else {
                            $stmt->execute();
                        }
                        
                        while ($row = $stmt->fetch()) {
                            $statusClass = getStatusClass($row['status']);
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['title']}</td>
                                    <td>{$row['sender_name']}</td>
                                    <td>{$row['receiver_name']}</td>
                                    <td><span class='badge {$statusClass}'>" . getStatusLabel($row['status']) . "</span></td>
                                    <td>" . formatDate($row['created_at']) . "</td>
                                    <td>" . formatDate($row['updated_at']) . "</td>
                                    <td class='actions-cell'>
                                        <div class='btn-group'>
                                            <a href='view_document.php?id={$row['id']}' class='btn btn-sm btn-info' title='عرض'>
                                                <i class='fas fa-eye'></i>
                                            </a>";
                            
                            if ($row['status'] === 'draft' && hasPermission('send_documents')) {
                                echo "<a href='send_document.php?id={$row['id']}' class='btn btn-sm btn-success' title='إرسال'>
                                        <i class='fas fa-paper-plane'></i>
                                    </a>";
                            }
                            
                            if (hasPermission('delete_documents')) {
                                echo "<a href='delete_document.php?id={$row['id']}' class='btn btn-sm btn-danger' 
                                        onclick='return confirm(\"هل أنت متأكد من حذف هذا الكتاب؟\")' title='حذف'>
                                        <i class='fas fa-trash'></i>
                                    </a>";
                            }
                            
                            echo "</div></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/documents.js"></script>
<link href="assets/css/documents.css" rel="stylesheet">

<?php include 'footer.php'; ?>
