-- Schema v59 : propositions de categories pour validation admin.
-- Compatibilite legacy : certaines bases historiques n'exposent pas de cle
-- indexee exploitable sur users.id, donc cette migration n'ajoute pas de
-- foreign keys vers users.

CREATE TABLE IF NOT EXISTS category_proposals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    normalized_name VARCHAR(180) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    category_id BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    admin_note VARCHAR(255) NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_category_proposals_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category_proposals_status (status),
    INDEX idx_category_proposals_teacher (teacher_user_id),
    INDEX idx_category_proposals_name (normalized_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
