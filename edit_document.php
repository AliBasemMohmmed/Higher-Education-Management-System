<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

// التحقق من وجود معرف الوثيقة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: documents.php');
    exit;
}

$documentId = $_GET['id'];

// جلب بيانات الوثيقة
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$documentId]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    header('Location: documents.php');
    exit;
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h3>تعديل الكتاب</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="process_edit_document.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($document['id']); ?>">
                
                <div class="mb-3">
                    <label class="form-label">معرف الكتاب</label>
                    <input type="text" name="document_id" class="form-control" value="<?php echo htmlspecialchars($document['document_id']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">عنوان الكتاب</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($document['title']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">محتوى الكتاب</label>
                    <textarea name="content" class="form-control" rows="5" required><?php echo htmlspecialchars($document['content']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">الملف المرفق الحالي</label>
                    <?php if ($document['file_path']): ?>
                        <div class="mb-2">
                            <a href="<?php echo htmlspecialchars($document['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-file"></i> عرض الملف الحالي
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="document_file" class="form-control">
                    <small class="text-muted">اترك هذا الحقل فارغاً للاحتفاظ بالملف الحالي</small>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ التعديلات
                    </button>
                    <a href="documents.php" class="btn btn-light">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 