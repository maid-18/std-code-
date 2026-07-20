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
    units.php            السجل المركزي لجهات النظام: الشارات (ألوان/أيقونات)
                         ومصادر بيانات كل وحدة — مرجع واحد لكل البوابات
index.php                صفحة هبوط عامة تعرض شارات جميع الجهات
care_guidance_admin/
    dashboard.php       لوحة تحكم مجمّعة (إحصائيات + رسوم بيانية) لكل الوحدات
    requests.php        قائمة موحّدة لكل الطلبات مع فلترة/بحث/صفحات
    request_details.php عرض + رد رسمي + تحديث حالة لأي نوع طلب
    reports.php          تقرير رسمي قابل للطباعة لكل وحدة
    profile.php          الملف الشخصي وتغيير كلمة المرور
manager/
    dashboard.php        لوحة مدير النظام: شبكة شارات الجهات + ملخص الوحدات،
                         ولمدير النظام صلاحية دخول كامل لوحة الإدارة العامة
supervisor/
    _bootstrap.php       تحقق الدور + تحديد وحدة المشرف (users.unit_type) + ثيم الوحدة
    dashboard.php        لوحة مشرف الوحدة بلون وأيقونة وحدته (إحصائيات + آخر الطلبات)
    requests.php         طلبات وحدته فقط مع بحث/فلترة/صفحات
    reports.php          تقرير رسمي قابل للطباعة لوحدته فقط
    profile.php          الملف الشخصي وتغيير كلمة المرور
login.php / backend/logout.php / backend/update_profile.php
database/std_system.sql               مخطط القاعدة الكامل (تفريغ فعلي من النظام)
database/seed_care_guidance_admin.sql  إضافة دور الإدارة العامة + حساب تجريبي
database/seed_unit_accounts.sql        حسابات مشرفي الوحدات الست + كلمة مرور مدير النظام
```

### شارات الجهات (dept badges)

نمط موحّد معرّف في `assets/css/style.css` (`.dept-badge`) وقيمه في
`includes/units.php`: إطار كحلي `#152238`، خلفية متدرجة بلون الجهة،
أيقونة Bootstrap Icons ونص أبيض عريض RTL.

| الجهة | الأيقونة | اللون |
|---|---|---|
| مدير النظام | `bi-person-gear` | `#152238` |
| الإداره العامة للإرشاد والرعاية الطلابية | `bi-people` | `#2fa6a6` |
| وحدة الإرشاد الأكاديمي | `bi-journal-bookmark-fill` | `#2e6da4` |
| وحدة الموهبة والابتكار | `bi-stars` | `#6b3fbf` |
| وحدة الطوارئ والرعاية السريعة | `bi-exclamation-triangle` | `#c0392b` |
| وحدة الملاحظات والبلاغات | `bi-chat-square-text` | `#d4700a` |
| وحدة الإرشاد المهني | `bi-briefcase` | `#3a52c4` |
| وحدة ذوي الاحتياجات الخاصة | `bi-person-wheelchair` | `#1e8c52` |

## التشغيل محلياً

```bash
mysql -u root -e "CREATE DATABASE std_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql --default-character-set=utf8mb4 -u root std_system < database/std_system.sql
mysql --default-character-set=utf8mb4 -u root std_system < database/seed_care_guidance_admin.sql
mysql --default-character-set=utf8mb4 -u root std_system < database/seed_unit_accounts.sql

DB_HOST=localhost DB_NAME=std_system DB_USER=root DB_PASS= php -S localhost:8000
```

> **مهم:** استورد الملفين دائماً بـ `--default-character-set=utf8mb4`، وإلا يُخزَّن
> النص العربي بترميز خاطئ (mojibake).

متغيرات البيئة `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` تُهيّئ اتصال `includes/db.php`
(تفتَرض افتراضياً `localhost` / `std_system` / `std_app` / `std_app_pw`).

## تسجيل الدخول التجريبي

لكل جهة بريد وكلمة مرور خاصان بها، والنظام يوجّه كل حساب للوحته تلقائياً بعد الدخول:

| الجهة | البريد | كلمة المرور |
|---|---|---|
| مدير النظام | `manager@gmail.com` | `Manager@2026` |
| الإدارة العامة للرعاية والإرشاد | `care.guidance@ub.edu.sa` | `CareAdmin@2026` |
| وحدة الإرشاد الأكاديمي | `academic.unit@ub.edu.sa` | `Academic@2026` |
| وحدة الموهبة والابتكار | `talent.unit@ub.edu.sa` | `Talent@2026` |
| وحدة الطوارئ والرعاية السريعة | `emergency.unit@ub.edu.sa` | `Emergency@2026` |
| وحدة الملاحظات والبلاغات | `reports.unit@ub.edu.sa` | `Reports@2026` |
| وحدة الإرشاد المهني | `career.unit@ub.edu.sa` | `Career@2026` |
| وحدة ذوي الاحتياجات الخاصة | `special.needs.unit@ub.edu.sa` | `Special@2026` |
