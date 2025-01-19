<?php
// قائمة الصلاحيات المتاحة في النظام
$available_permissions = [
    // صلاحيات المستخدمين
    'manage_users' => 'إدارة المستخدمين',
    'view_users' => 'عرض المستخدمين',
    'add_user' => 'إضافة مستخدم',
    'edit_user' => 'تعديل مستخدم',
    'delete_user' => 'حذف مستخدم',
    
    // صلاحيات الجامعات
    'manage_universities' => 'إدارة الجامعات',
    'view_universities' => 'عرض الجامعات',
    'add_university' => 'إضافة جامعة',
    'edit_university' => 'تعديل جامعة',
    'delete_university' => 'حذف جامعة',
    
    // صلاحيات الكليات
    'manage_colleges' => 'إدارة الكليات',
    'view_colleges' => 'عرض الكليات',
    'add_college' => 'إضافة كلية',
    'edit_college' => 'تعديل كلية',
    'delete_college' => 'حذف كلية',
    
    // صلاحيات الأقسام الوزارية
    'manage_ministry_departments' => 'إدارة الأقسام الوزارية',
    'view_ministry_departments' => 'عرض الأقسام الوزارية',
    'add_ministry_department' => 'إضافة قسم وزاري',
    'edit_ministry_department' => 'تعديل قسم وزاري',
    'delete_ministry_department' => 'حذف قسم وزاري',
    
    // صلاحيات الشعب الجامعية
    'manage_divisions' => 'إدارة الشعب الجامعية',
    'view_divisions' => 'عرض الشعب الجامعية',
    'add_division' => 'إضافة شعبة جامعية',
    'edit_division' => 'تعديل شعبة جامعية',
    'delete_division' => 'حذف شعبة جامعية',
    
    // صلاحيات الوحدات
    'manage_units' => 'إدارة الوحدات',
    'view_units' => 'عرض الوحدات',
    'add_unit' => 'إضافة وحدة',
    'edit_unit' => 'تعديل وحدة',
    'delete_unit' => 'حذف وحدة',
    
    // صلاحيات المراسلات والكتب
    'manage_correspondence' => 'إدارة المراسلات',
    'view_correspondence' => 'عرض المراسلات',
    'add_correspondence' => 'إضافة مراسلة',
    'edit_correspondence' => 'تعديل مراسلة',
    'delete_correspondence' => 'حذف مراسلة',
    
    // صلاحيات التقا
    'manage_reports' => 'إدارة التقارير',
    'view_reports' => 'عرض التقارير',
    'generate_reports' => 'إنشاء التقارير',
    'export_reports' => 'تصدير التقارير',
    
    // صلاحيات النظام
    'view_logs' => 'عرض سجلات النظام',
    'manage_settings' => 'إدارة إعدادات النظام',
    'manage_permissions' => 'إدارة الصلاحيات',
    'view_statistics' => 'عرض الإحصائيات'
];

// ترتيب الصلاحيات أبجدياً حسب الوصف
asort($available_permissions); 