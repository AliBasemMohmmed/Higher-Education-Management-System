<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

include 'header.php';

// جلب كل الإشعارات للمستخدم مع الترقيم
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $countStmt->execute([$_SESSION['user_id']]);
    $total = $countStmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?, ?
    ");
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->bindValue(3, $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPages = ceil($total / $perPage);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'حدث خطأ في جلب الإشعارات';
    $notifications = [];
    $totalPages = 0;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-bell text-primary me-2"></i>
                    الإشعارات
                </h2>
                <span class="badge bg-primary rounded-pill notification-count">
                    <?php echo $total; ?> إشعار
                </span>
            </div>

            <div class="card shadow-sm">
                <div class="list-group list-group-flush" id="notificationsList">
                    <?php if (empty($notifications)): ?>
                        <div class="list-group-item text-center py-5">
                            <i class="fas fa-bell-slash text-muted mb-3" style="font-size: 3rem;"></i>
                            <h5 class="text-muted">لا توجد إشعارات</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item notification-item" data-id="<?php echo $notification['id']; ?>" style="cursor: pointer;">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h5 class="mb-2 <?php echo !$notification['is_read'] ? 'text-primary fw-bold' : ''; ?>">
                                        <?php if (!$notification['is_read']): ?>
                                            <i class="fas fa-circle text-primary me-2" style="font-size: 0.5rem;"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </h5>
                                    <small class="text-muted">
                                        <?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script src="assets/js/notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationManager = new NotificationManager();
});
</script>

<!-- Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-bell me-2"></i>
                    <span class="modal-title-text"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="notification-time text-muted mb-3">
                    <i class="far fa-clock me-1"></i>
                    <span class="time-text"></span>
                </div>
                <div class="notification-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    إغلاق
                </button>
                <button type="button" class="btn btn-primary" id="markAsRead">
                    <i class="fas fa-check me-1"></i>
                    تحديد كمقروء
                </button>
            </div>
        </div>
    </div>
</div>

<!-- إضافة CSS -->
<style>
.notification-item {
    transition: all 0.3s ease;
    border-right: 3px solid transparent;
}

.notification-item:hover {
    background-color: #f8f9fa;
    border-right-color: #0d6efd;
    transform: translateX(-5px);
}

.notification-item.unread {
    background-color: #f0f7ff;
}

.notification-time {
    font-size: 0.85rem;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 15px;
}

.notification-icon.info { background-color: #cfe2ff; color: #0d6efd; }
.notification-icon.success { background-color: #d1e7dd; color: #198754; }
.notification-icon.warning { background-color: #fff3cd; color: #ffc107; }
.notification-icon.danger { background-color: #f8d7da; color: #dc3545; }

.modal-body {
    max-height: 400px;
    overflow-y: auto;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.notification-item {
    animation: fadeIn 0.3s ease;
}
</style>

<script>
const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
let currentNotificationId = null;

// تحسين عرض الإشعارات
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function() {
        currentNotificationId = this.dataset.id;
        const title = this.querySelector('h5').textContent.trim();
        const message = this.querySelector('p').textContent.trim();
        const time = this.querySelector('small').textContent.trim();
        const type = this.dataset.type || 'info';
        
        document.querySelector('.modal-title-text').textContent = title;
        document.querySelector('.notification-content').textContent = message;
        document.querySelector('.time-text').textContent = time;
        
        // إضافة أيقونة حسب نوع الإشعار
        const iconClass = getNotificationIconClass(type);
        document.querySelector('.modal-header i').className = `${iconClass} me-2`;
        
        notificationModal.show();
        
        // إضافة تأثير النقر
        this.style.backgroundColor = '#e9ecef';
        setTimeout(() => {
            this.style.backgroundColor = '';
        }, 200);
    });
});

function getNotificationIconClass(type) {
    const icons = {
        'info': 'fas fa-info-circle',
        'success': 'fas fa-check-circle',
        'warning': 'fas fa-exclamation-triangle',
        'danger': 'fas fa-times-circle',
        'default': 'fas fa-bell'
    };
    return icons[type] || icons.default;
}

document.getElementById('markAsRead').addEventListener('click', function() {
    if (!currentNotificationId) return;
    
    const button = this;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري التحديث...';

    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentNotificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notification = document.querySelector(`.notification-item[data-id="${currentNotificationId}"]`);
            
            // تأثير بصري للتحديث
            notification.style.transition = 'all 0.3s ease';
            notification.style.backgroundColor = '#d1e7dd';
            
            setTimeout(() => {
                notification.querySelector('h5').classList.remove('text-primary', 'fw-bold');
                const icon = notification.querySelector('.fa-circle');
                if (icon) {
                    icon.style.transition = 'all 0.3s ease';
                    icon.style.opacity = '0';
                    setTimeout(() => icon.remove(), 300);
                }
                notification.style.backgroundColor = '';
            }, 300);

            // تحديث عداد الإشعارات غير المقروءة
            updateUnreadCount();
            
            setTimeout(() => {
                notificationModal.hide();
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-check me-1"></i> تحديد كمقروء';
            }, 500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check me-1"></i> تحديد كمقروء';
    });
});

function updateUnreadCount() {
    fetch('get_unread_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.badge.bg-danger');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                } else {
                    badge.remove();
                }
            }
        });
}
</script>

<?php include 'footer.php'; ?>
