-- ============================================================
-- الإدارة العامة للرعاية والإرشاد (General Administration for
-- Student Care & Guidance) — new role above unit_supervisor.
--
-- Run after importing std_system.sql:
--   mysql -u root std_system < database/seed_care_guidance_admin.sql
--
-- Seed login for testing:
--   email:    care.guidance@ub.edu.sa
--   password: CareAdmin@2026
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO user_types (id, title, arabic_title)
VALUES (7, 'care_guidance_admin', 'الإدارة العامة للرعاية والإرشاد')
ON DUPLICATE KEY UPDATE title = VALUES(title), arabic_title = VALUES(arabic_title);

INSERT INTO users
    (id, user_type_id, unit_type, student_number, full_name, national_id, phone, email, password,
     date_of_birth, gender, college_id, degree_id, major_id, academic_advisor_id, profile_image,
     status, activation, last_login, created_at, updated_at)
VALUES
    (100, 7, NULL, NULL, 'الإدارة العامة للرعاية والإرشاد', NULL, NULL, 'care.guidance@ub.edu.sa',
     '$2y$12$0asI1U2sUI/SCQWGGb8NDeEkDTt/AGdt6IvEn8YT1/dWp.u5xfjcO',
     NULL, 'male', NULL, NULL, NULL, NULL, NULL, 'active', 1, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);
