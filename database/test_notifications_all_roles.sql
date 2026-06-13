-- Test manuel: notifications popup + push pour tous les roles.
-- Roles cibles: student, teacher, admin, admin_secondary, company_admin.
-- Compatible MySQL/MariaDB en utilisant LIKE sur roles_json.

SET @test_batch := DATE_FORMAT(NOW(), '%Y%m%d%H%i%s');
SET @test_stamp := DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s');

SET @uid_student := (
    SELECT id
    FROM users
    WHERE status = 'active'
      AND roles_json LIKE '%"student"%'
    ORDER BY id ASC
    LIMIT 1
);

SET @uid_teacher := (
    SELECT id
    FROM users
    WHERE status = 'active'
      AND roles_json LIKE '%"teacher"%'
      AND (@uid_student IS NULL OR id <> @uid_student)
    ORDER BY id ASC
    LIMIT 1
);

SET @uid_admin := (
    SELECT id
    FROM users
    WHERE status = 'active'
      AND roles_json LIKE '%"admin"%'
      AND (@uid_student IS NULL OR id <> @uid_student)
      AND (@uid_teacher IS NULL OR id <> @uid_teacher)
    ORDER BY id ASC
    LIMIT 1
);

SET @uid_admin_secondary := (
    SELECT id
    FROM users
    WHERE status = 'active'
      AND roles_json LIKE '%"admin_secondary"%'
      AND (@uid_student IS NULL OR id <> @uid_student)
      AND (@uid_teacher IS NULL OR id <> @uid_teacher)
      AND (@uid_admin IS NULL OR id <> @uid_admin)
    ORDER BY id ASC
    LIMIT 1
);

SET @uid_company_admin := (
    SELECT id
    FROM users
    WHERE status = 'active'
      AND roles_json LIKE '%"company_admin"%'
      AND (@uid_student IS NULL OR id <> @uid_student)
      AND (@uid_teacher IS NULL OR id <> @uid_teacher)
      AND (@uid_admin IS NULL OR id <> @uid_admin)
      AND (@uid_admin_secondary IS NULL OR id <> @uid_admin_secondary)
    ORDER BY id ASC
    LIMIT 1
);

INSERT INTO notifications (user_id, title, body, url)
SELECT
    @uid_student,
    CONCAT('[TEST PUSH ', @test_batch, '] Etudiant'),
    CONCAT('Test popup+push genere le ', @test_stamp, ' (role: student).'),
    '/student/dashboard'
FROM DUAL
WHERE @uid_student IS NOT NULL;

INSERT INTO notifications (user_id, title, body, url)
SELECT
    @uid_teacher,
    CONCAT('[TEST PUSH ', @test_batch, '] Enseignant'),
    CONCAT('Test popup+push genere le ', @test_stamp, ' (role: teacher).'),
    '/teacher/dashboard'
FROM DUAL
WHERE @uid_teacher IS NOT NULL;

INSERT INTO notifications (user_id, title, body, url)
SELECT
    @uid_admin,
    CONCAT('[TEST PUSH ', @test_batch, '] Admin'),
    CONCAT('Test popup+push genere le ', @test_stamp, ' (role: admin).'),
    '/admin/dashboard'
FROM DUAL
WHERE @uid_admin IS NOT NULL;

INSERT INTO notifications (user_id, title, body, url)
SELECT
    @uid_admin_secondary,
    CONCAT('[TEST PUSH ', @test_batch, '] Admin secondaire'),
    CONCAT('Test popup+push genere le ', @test_stamp, ' (role: admin_secondary).'),
    '/admin/dashboard'
FROM DUAL
WHERE @uid_admin_secondary IS NOT NULL;

INSERT INTO notifications (user_id, title, body, url)
SELECT
    @uid_company_admin,
    CONCAT('[TEST PUSH ', @test_batch, '] Entreprise'),
    CONCAT('Test popup+push genere le ', @test_stamp, ' (role: company_admin).'),
    '/company/dashboard'
FROM DUAL
WHERE @uid_company_admin IS NOT NULL;

SELECT 'student' AS role_name, @uid_student AS user_id,
       CASE WHEN @uid_student IS NULL THEN 'SKIPPED (no active user)' ELSE 'INSERTED' END AS result
UNION ALL
SELECT 'teacher', @uid_teacher,
       CASE WHEN @uid_teacher IS NULL THEN 'SKIPPED (no active user)' ELSE 'INSERTED' END
UNION ALL
SELECT 'admin', @uid_admin,
       CASE WHEN @uid_admin IS NULL THEN 'SKIPPED (no active user)' ELSE 'INSERTED' END
UNION ALL
SELECT 'admin_secondary', @uid_admin_secondary,
       CASE WHEN @uid_admin_secondary IS NULL THEN 'SKIPPED (no active user)' ELSE 'INSERTED' END
UNION ALL
SELECT 'company_admin', @uid_company_admin,
       CASE WHEN @uid_company_admin IS NULL THEN 'SKIPPED (no active user)' ELSE 'INSERTED' END;

SELECT
    n.id AS notification_id,
    n.user_id,
    n.title,
    n.url,
    n.created_at,
    q.id AS queue_id,
    q.status AS push_status,
    q.attempt_count
FROM notifications n
LEFT JOIN push_notification_queue q ON q.notification_id = n.id
WHERE n.title LIKE CONCAT('[TEST PUSH ', @test_batch, ']%')
ORDER BY n.id DESC;
