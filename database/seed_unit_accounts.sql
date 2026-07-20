-- ============================================================
-- حسابات مشرفي الوحدات + كلمة مرور معروفة لمدير النظام
-- (بريد وكلمة مرور لكل جهة في النظام)
--
-- يُنفَّذ بعد استيراد std_system.sql و seed_care_guidance_admin.sql:
--   mysql --default-character-set=utf8mb4 -u root std_system < database/seed_unit_accounts.sql
--
-- بيانات الدخول:
--   مدير النظام                    manager@gmail.com            Manager@2026
--   الإدارة العامة للرعاية والإرشاد care.guidance@ub.edu.sa      CareAdmin@2026
--   وحدة الإرشاد الأكاديمي          academic.unit@ub.edu.sa      Academic@2026
--   وحدة الموهبة والابتكار          talent.unit@ub.edu.sa        Talent@2026
--   وحدة الطوارئ والرعاية السريعة   emergency.unit@ub.edu.sa     Emergency@2026
--   وحدة الملاحظات والبلاغات        reports.unit@ub.edu.sa       Reports@2026
--   وحدة الإرشاد المهني             career.unit@ub.edu.sa        Career@2026
--   وحدة ذوي الاحتياجات الخاصة      special.needs.unit@ub.edu.sa Special@2026
-- ============================================================

SET NAMES utf8mb4;

-- كلمة مرور معروفة لحساب مدير النظام الموجود مسبقاً (id = 1)
UPDATE users
SET password = '$2y$12$Qob3n3SIVBdEOM4JTfaIVOG4CN3xAc9mELhRFT7yGDChdwa/vOxOi'
WHERE id = 1 AND email = 'manager@gmail.com';

-- مشرفو الوحدات الست (user_type_id = 6: unit_supervisor)
-- unit_type يطابق مفاتيح includes/units.php
INSERT INTO users
    (id, user_type_id, unit_type, student_number, full_name, national_id, phone, email, password,
     date_of_birth, gender, college_id, degree_id, major_id, academic_advisor_id, profile_image,
     status, activation, last_login, created_at, updated_at)
VALUES
    (110, 6, 'academic', NULL, 'مشرف وحدة الإرشاد الأكاديمي', NULL, NULL, 'academic.unit@ub.edu.sa',
     '$2y$12$JoYIr89yYUFXRRegRHH8lerRLbXBQQ.B6r/r0IOZn71w.MMF9irYW',
     NULL, 'male', NULL, NULL, NULL, NULL, NULL, 'active', 1, NULL, NOW(), NOW()),
    (111, 6, 'talent', NULL, 'مشرف وحدة الموهبة والابتكار', NULL, NULL, 'talent.unit@ub.edu.sa',
     '$2y$12$yZhlJ0caP5pls9PDL0ckf.9PJsYkn3xsFkgDDrNxxFDTcBcC5VVM2',
     NULL, 'male', NULL, NULL, NULL, NULL, NULL, 'active', 1, NULL, NOW(), NOW()),
    (112, 6, 'emergency', NULL, 'مشرف وحدة الطوارئ والرعاية السريعة', NULL, NULL, 'emergency.unit@ub.edu.sa',
     '$2y$12$IZzwRHVJwTUY.o/Z6ckS8ONw18i5j.d8pkYFt6AMK7/p8nPwD0Hi2',
     NULL, 'male', NULL, NULL, NULL, NULL, NULL, 'active', 1, NULL, NOW(), NOW()),
    (113, 6, 'reports', NULL, 'مشرف وحدة الملاحظات والبلاغات', NULL, NULL, 'reports.unit@ub.edu.sa',
     '$2y$12$djVFz8vi3Vvq3rsR5FjsBe6w4liND9CJIG82vHPtYfQwJ.mtleAPm',
     NULL, 'male', NULL, NULL, NULL, NULL, NULL, 'active', 1, NULL, NOW(), NOW()),
    (114, 6, 'career', NULL, 'مشرف وحدة الإرشاد المهني', NULL, NULL, 'career.unit@ub.edu.sa',
     '$2y$12$2BDWMwJvIrnr5jxUIBnG5eEXmPQigm4WYvnK0YoVHRD5foKlDJixm',
     NULL, 'male', NULL, NULL, NULL, NULL, NULL, 'active', 1, NULL, NOW(), NOW()),
    (115, 6, 'special_needs', NULL, 'مشرف وحدة ذوي الاحتياجات الخاصة', NULL, NULL, 'special.needs.unit@ub.edu.sa',
     '$2y$12$g08/gXGfw97ys1oy19OSKuPdsYXyuWzGjBSDmCYAmKZ/oUqvhr49a',
     NULL, 'male', NULL, NULL, NULL, NULL, NULL, 'active', 1, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    unit_type = VALUES(unit_type),
    full_name = VALUES(full_name),
    email     = VALUES(email);
