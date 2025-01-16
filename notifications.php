<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();
include 'header.php';

// جلب الإشعارات غير المقروءة
$unreadNotifications = $pdo->prepare("
  SELECT * FROM notifications 
  WHERE user_id = ? AND is_read = 0 
  ORDER BY created_at DESC
");
$unreadNotifications->execute([$_SESSION['user_id']]);

// جلب جميع الإشعارات
$allNotifications = $pdo->prepare("
  SELECT * FROM notifications 
  WHERE user_id = ? 
  ORDER BY created_at DESC 
  LIMIT 50
");
$allNotifications->execute([$_SESSION['user_id']]);
?>

<div class="container mt-4">
  <h2>الإشعارات</h2>
  
  <div class="row">
    <div class="col-md-4">
      <div class="card mb-4">
        <div class="card-header">
          الإشعارات غير المقروءة
        </div>
        <div class="card-body">
          <div class="list-group">
            <?php while ($notification = $unreadNotifications->fetch()): ?>
              <a href="view_notification.php?id=<?php echo $notification['id']; ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><?php echo $notification['title']; ?></h6>
                  <small><?php echo $notification['created_at']; ?></small>
                </div>
                <p class="mb-1"><?php echo substr($notification['content'], 0, 100); ?>...</p>
              </a>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">
          جميع الإشعارات
        </div>
        <div class="card-body">
          <div class="list-group">
            <?php while ($notification = $allNotifications->fetch()): ?>
              <a href="view_notification.php?id=<?php echo $notification['id']; ?>" 
                 class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><?php echo $notification['title']; ?></h6>
                  <small><?php echo $notification['created_at']; ?></small>
                </div>
                <p class="mb-1"><?php echo substr($notification['content'], 0, 100); ?>...</p>
              </a>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
