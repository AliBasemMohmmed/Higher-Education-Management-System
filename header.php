<?php
require_once 'auth.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// جلب عدد الإشعارات غير المقروءة
$unreadNotifications = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadNotifications = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("خطأ في جلب الإشعارات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نظام إدارة التعليم العالي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #004d40; }
        .navbar-brand, .nav-link { color: white !important; }
        .dropdown-menu { min-width: 200px; }
        .dropdown-item:hover { background-color: #f8f9fa; }
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            padding: 0.25rem 0.5rem;
            border-radius: 50%;
            background-color: #dc3545;
            color: white;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-university me-2"></i>نظام إدارة التعليم العالي
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- شريط البحث -->
            <?php if (hasPermission('search_system')): ?>
            <form class="d-flex me-auto" action="search.php" method="GET">
                <input class="form-control me-2" type="search" name="q" placeholder="بحث في النظام..." required>
                <button class="btn btn-outline-light" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <?php endif; ?>

            <!-- القائمة الرئيسية -->
            <ul class="navbar-nav me-auto">
                <?php if (hasPermission('manage_departments')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="departments.php">
                        <i class="fas fa-building me-1"></i>الأقسام
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('manage_universities')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="universities.php">
                        <i class="fas fa-graduation-cap me-1"></i>الجامعات
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('manage_colleges')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="colleges.php">
                        <i class="fas fa-building me-1"></i>الكليات
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('manage_divisions')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="divisions.php">
                        <i class="fas fa-layer-group me-1"></i>الشعب
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('manage_units')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="units.php">
                        <i class="fas fa-boxes me-1"></i>الوحدات
                    </a>
                </li>
                <?php endif; ?>

                <!-- قائمة الكتب والمراسلات -->
                <?php if (hasPermission('view_documents')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-file-alt me-1"></i>الكتب والمراسلات
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="documents.php">عرض الكتب</a></li>
                        <?php if (hasPermission('create_documents')): ?>
                        <li><a class="dropdown-item" href="process_document.php">إضافة كتاب جديد</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="document_workflow.php">تدفق الكتب</a></li>
                        <li><a class="dropdown-item" href="archive.php">الأرشيف</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- قائمة التقارير -->
                <?php if (hasPermission('view_reports')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-chart-bar me-1"></i>التقارير
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="reports.php">التقارير العامة</a></li>
                        <li><a class="dropdown-item" href="advanced_reports.php">التقارير المتقدمة</a></li>
                        <li><a class="dropdown-item" href="statistics.php">الإحصائيات</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <!-- قائمة المستخدم -->
            <ul class="navbar-nav">
                <!-- الإشعارات -->
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger notification-badge" style="display: none;"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notifications-dropdown" style="width: 300px; max-height: 400px; overflow-y: auto;">
                        <h6 class="dropdown-header">الإشعارات</h6>
                        <div class="notifications-list">

                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center" href="notifications.php">عرض كل الإشعارات</a>
                    </div>
                </li>

                <!-- قائمة المستخدم المنسدلة -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user me-2"></i>الملف الشخصي
                        </a></li>
                        
                        <?php if (hasPermission('access_admin_dashboard')): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">إدارة النظام</h6></li>
                        <li><a class="dropdown-item" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم
                        </a></li>
                        <li><a class="dropdown-item" href="manage_permissions.php">
                            <i class="fas fa-key me-2"></i>إدارة الصلاحيات
                        </a></li>
                        <li><a class="dropdown-item" href="system_monitor.php">
                            <i class="fas fa-desktop me-2"></i>مراقبة النظام
                        </a></li>
                        <!-- <li><a class="dropdown-item" href="manage_permission_types.php">
                            <i class="fas fa-key me-2"></i>إدارة نوع الصلاحيات
                        </a></li> -->
                        <li><a class="dropdown-item" href="backup.php">
                            <i class="fas fa-database me-2"></i>النسخ الاحتياطي
                        </a></li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">الأدوات</h6></li>
                        <li><a class="dropdown-item" href="reminders.php">
                            <i class="fas fa-clock me-2"></i>التذكيرات
                        </a></li>
                        <li><a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i>الإعدادات
                        </a></li>
                        <li><a class="dropdown-item" href="users.php">
                            <i class="fas fa-users me-2"></i>المستخدمين
                        </a></li>
                        <?php if (hasPermission('export_data')): ?>
                        <li><a class="dropdown-item" href="export.php">
                            <i class="fas fa-file-export me-2"></i>تصدير البيانات
                        </a></li>
                        <?php endif; ?>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                        </a></li>
                    </ul>
                </li>
            </ul>

            <!-- عرض اسم المستخدم -->
            <!-- <span class="navbar-text">
                مرحباً، <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'مستخدم'; ?>
            </span> -->
        </div>
    </div>
</nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // تفعيل جميع القوائم المنسدلة في Bootstrap
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // إضافة تأثير hover للقوائم المنسدلة
    $('.dropdown').hover(
        function() {
            if (window.innerWidth >= 992) { // فقط للشاشات الكبيرة
                $(this).find('.dropdown-menu').addClass('show');
            }
        },
        function() {
            if (window.innerWidth >= 992) {
                $(this).find('.dropdown-menu').removeClass('show');
            }
        }
    );

    // تفعيل التنقل بين عناصر القائمة باستخدام لوحة المفاتيح
    $('.dropdown-menu a').on('keydown', function(e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            var items = $(this).closest('.dropdown-menu').find('a:not(.disabled)');
            var index = items.index(this);
            var nextIndex = e.key === 'ArrowDown' ? 
                (index + 1) % items.length : 
                (index - 1 + items.length) % items.length;
            items.eq(nextIndex).focus();
        }
    });

    // إغلاق القائمة المنسدلة عند النقر خارجها
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });

    // تحديث عدد الإشعارات كل دقيقة
    function updateNotifications() {
        $.get('get_notifications_count.php', function(data) {
            if (data.count > 0) {
                $('.notification-badge').text(data.count).show();
            } else {
                $('.notification-badge').hide();
            }
        });
    }
    setInterval(updateNotifications, 60000); // تحديث كل دقيقة
});

// تفعيل tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

<style>
/* تحسينات إضافية للقوائم المنسدلة */
.dropdown-menu {
    margin-top: 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    border: none;
    animation: fadeIn 0.2s ease-in;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
}

.dropdown-item i {
    margin-left: 0.5rem;
    width: 20px;
    text-align: center;
}

.dropdown-header {
    color: #004d40;
    font-weight: bold;
    padding: 0.5rem 1rem;
}

.dropdown-divider {
    margin: 0.3rem 0;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* تحسين مظهر الإشعارات */
.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    padding: 0.25rem 0.5rem;
    border-radius: 50%;
    background-color: #dc3545;
    color: white;
    font-size: 0.75rem;
    transform: translate(50%, -50%);
}

/* تحسين مظهر القوائم في الشاشات الصغيرة */
@media (max-width: 991.98px) {
    .dropdown-menu {
        border: none;
        box-shadow: none;
        padding: 0;
        margin: 0;
    }
    
    .dropdown-item {
        padding: 0.75rem 1.5rem;
    }
    
    .navbar-collapse {
        background-color: #004d40;
        padding: 1rem;
        border-radius: 0 0 0.5rem 0.5rem;
    }
}
</style>
</body>
</html>
