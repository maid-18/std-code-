# نظام عمادة شؤون الطلاب — الإدارة العامة للرعاية والإرشاد

وحدة إدارية جديدة (`care_guidance_admin`) أعلى من مستوى "مشرف الوحدة"، تجمع وتُدير طلبات
جميع وحدات الرعاية والإرشاد الطلابي من مكان واحد: الإرشاد الأكاديمي، الدعم الأكاديمي،
الموهبة والابتكار، الطوارئ والرعاية السريعة، الملاحظات والبلاغات، الإرشاد المهني،
ذوي الاحتياجات الخاصة، والإرشاد النفسي والاجتماعي (مواعيد ورسائل).

على عكس صفحات "مشرف الوحدة" القديمة (عرض فقط)، يمكن لهذه الإدارة الرد رسمياً على
الطلاب وتغيير حالة كل طلب.

## البنية

```
includes/        db.php, auth.php, functions.php, header.php, footer.php
care_guidance_admin/
    dashboard.php       لوحة تحكم مجمّعة (إحصائيات + رسوم بيانية) لكل الوحدات
    requests.php        قائمة موحّدة لكل الطلبات مع فلترة/بحث/صفحات
    request_details.php عرض + رد رسمي + تحديث حالة لأي نوع طلب
    reports.php          تقرير رسمي قابل للطباعة لكل وحدة
    profile.php          الملف الشخصي وتغيير كلمة المرور
login.php / backend/logout.php / backend/update_profile.php
database/std_system.sql               مخطط القاعدة الكامل (تفريغ فعلي من النظام)
database/seed_care_guidance_admin.sql  إضافة الدور الجديد + حساب تجريبي
```

## التشغيل محلياً

```bash
mysql -u root -e "CREATE DATABASE std_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql --default-character-set=utf8mb4 -u root std_system < database/std_system.sql
mysql --default-character-set=utf8mb4 -u root std_system < database/seed_care_guidance_admin.sql

DB_HOST=localhost DB_NAME=std_system DB_USER=root DB_PASS= php -S localhost:8000
```

> **مهم:** استورد الملفين دائماً بـ `--default-character-set=utf8mb4`، وإلا يُخزَّن
> النص العربي بترميز خاطئ (mojibake).

متغيرات البيئة `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` تُهيّئ اتصال `includes/db.php`
(تفتَرض افتراضياً `localhost` / `std_system` / `std_app` / `std_app_pw`).

## تسجيل الدخول التجريبي

- البريد: `care.guidance@ub.edu.sa`
- كلمة المرور: `CareAdmin@2026`
