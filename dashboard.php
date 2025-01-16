<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();
include 'header.php';

// إحصائيات النظام
$stats = [
  'documents' => $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
  'reports' => $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn(),
  'units' => $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn(),
  'divisions' => $pdo->query("SELECT COUNT(*) FROM university_divisions")->fetchColumn()
];

// آخر الكتب
$latestDocs = $pdo->query("
  SELECT d.*, 
         CASE 
           WHEN d.sender_type = 'ministry' THEN (SELECT name FROM ministry_departments WHERE id = d.sender_id)
           WHEN d.sender_type = 'division' THEN (SELECT name FROM university_divisions WHERE id = d.sender_id)
           WHEN d.sender_type = 'unit' THEN (SELECT name FROM units WHERE id = d.sender_id)
         END as sender_name
  FROM documents d 
  ORDER BY created_at DESC 
  LIMIT 5
")->fetchAll();

// آخر التقارير
$latestReports = $pdo->query("
  SELECT r.*, u.name as unit_name 
  FROM reports r 
  JOIN units u ON r.unit_id = u.id 
  ORDER BY r.created_at DESC 
  LIMIT 5
")->fetchAll();
?>

<div class="container mt-4">
  <h2>لوحة التحكم</h2>
  
  <!-- إحصائيات النظام -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <h5 class="card-title">الكتب</h5>
          <h2><?php echo $stats['documents']; ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <h5 class="card-title">التقارير</h5>
          <h2><?php echo $stats['reports']; ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body">
          <h5 class="card-title">الوحدات</h5>
          <h2><?php echo $stats['units']; ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-white">
        <div class="card-body">
          <h5 class="card-title">الشعب</h5>
          <h2><?php echo $stats['divisions']; ?></h2>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row">
    <!-- آخر الكتب -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          آخر الكتب
        </div>
        <div class="card-body">
          <div class="list-group">
            <?php foreach ($latestDocs as $doc): ?>
              <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                  <h5 class="mb-1"><?php echo $doc['title']; ?></h5>
                  <small><?php echo $doc['created_at']; ?></small>
                </div>
                <p class="mb-1">من: <?php echo $doc['sender_name']; ?></p>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    
    <!-- آخر التقارير -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          آخر التقارير
        </div>
        <div class="card-body">
          <div class="list-group">
            <?php foreach ($latestReports as $report): ?>
              <a href="view_report.php?id=<?php echo $report['id']; ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                  <h5 class="mb-1"><?php echo $report['title']; ?></h5>
                  <small><?php echo $report['created_at']; ?></small>
                </div>
                <p class="mb-1">الوحدة: <?php echo $report['unit_name']; ?></p>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
