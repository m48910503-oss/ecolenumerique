-- Schema v49 : profils unifies utilisateur / etudiant / enseignant / entreprise.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS notification_preferences_json JSON NULL;

CREATE TABLE IF NOT EXISTS student_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    certificate_display_name VARCHAR(180) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_profiles_user (user_id),
    CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    professional_title VARCHAR(150) NULL,
    short_bio TEXT NULL,
    portfolio_url VARCHAR(255) NULL,
    linkedin_url VARCHAR(255) NULL,
    cv_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teacher_profiles_user (user_id),
    CONSTRAINT fk_teacher_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS company_email VARCHAR(190) NULL,
    ADD COLUMN IF NOT EXISTS company_phone VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS country VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS address_line VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS logo_path VARCHAR(255) NULL;
