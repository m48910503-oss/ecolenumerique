-- Schema v48 : notifications popup non rejouees + flux entreprise avance (invites, achats par places, attribution).

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS popup_shown_at DATETIME NULL AFTER is_read,
    ADD INDEX idx_notifications_popup_shown (user_id, is_read, popup_shown_at, id);

ALTER TABLE company_invites
    MODIFY COLUMN email VARCHAR(190) NULL,
    MODIFY COLUMN status ENUM('pending','awaiting_approval','accepted','revoked','expired') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS invite_mode ENUM('email','link') NOT NULL DEFAULT 'email' AFTER email,
    ADD COLUMN IF NOT EXISTS approval_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none' AFTER status,
    ADD COLUMN IF NOT EXISTS approval_required TINYINT(1) NOT NULL DEFAULT 0 AFTER approval_status,
    ADD COLUMN IF NOT EXISTS requested_user_id BIGINT UNSIGNED NULL AFTER invited_by,
    ADD COLUMN IF NOT EXISTS approved_by BIGINT UNSIGNED NULL AFTER requested_user_id,
    ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approved_by,
    ADD INDEX idx_company_invites_mode_status (company_id, invite_mode, status),
    ADD INDEX idx_company_invites_requested_user (requested_user_id),
    ADD INDEX idx_company_invites_approval_status (company_id, approval_status);

ALTER TABLE company_invites
    ADD CONSTRAINT fk_company_invites_requested_user
        FOREIGN KEY (requested_user_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_company_invites_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE courses
    ADD COLUMN IF NOT EXISTS enterprise_price_xof INT UNSIGNED NULL AFTER price_xof,
    ADD COLUMN IF NOT EXISTS enterprise_discount_percent DECIMAL(5,2) NULL AFTER enterprise_price_xof,
    ADD INDEX idx_courses_enterprise_price (enterprise_price_xof),
    ADD INDEX idx_courses_enterprise_discount (enterprise_discount_percent);

ALTER TABLE order_items
    ADD COLUMN IF NOT EXISTS company_id BIGINT UNSIGNED NULL AFTER course_id,
    ADD INDEX idx_order_items_company_course (company_id, course_id);

ALTER TABLE order_items
    ADD CONSTRAINT fk_order_items_company
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS company_course_seat_purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NOT NULL,
    purchased_by BIGINT UNSIGNED NOT NULL,
    seats_total INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price_xof INT UNSIGNED NOT NULL DEFAULT 0,
    total_xof INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active','canceled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_course_seat_purchase_order_item (order_item_id),
    INDEX idx_company_course_seat_purchases_company_course (company_id, course_id, status),
    INDEX idx_company_course_seat_purchases_order (order_id),
    CONSTRAINT fk_company_course_seat_purchases_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_course_seat_purchases_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_course_seat_purchases_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_course_seat_purchases_order_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_course_seat_purchases_user FOREIGN KEY (purchased_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_course_seat_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    member_user_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED NOT NULL,
    status ENUM('active','revoked') NOT NULL DEFAULT 'active',
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_course_member (company_id, course_id, member_user_id),
    INDEX idx_company_course_assignments_company_course_status (company_id, course_id, status),
    INDEX idx_company_course_assignments_member (member_user_id, status),
    CONSTRAINT fk_company_course_assignments_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_course_assignments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_course_assignments_member FOREIGN KEY (member_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_course_assignments_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
