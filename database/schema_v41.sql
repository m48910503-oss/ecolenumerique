-- Schema v41 : analytics catalogue (clics, panier, achat) + attribution par bloc.

CREATE TABLE IF NOT EXISTS catalog_event_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('course_click','preview_open','add_to_cart','buy_now','purchase') NOT NULL,
    block_key VARCHAR(64) NOT NULL,
    course_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    session_key VARCHAR(96) NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_catalog_events_type_block_date (event_type, block_key, created_at),
    INDEX idx_catalog_events_course (course_id),
    INDEX idx_catalog_events_user (user_id),
    INDEX idx_catalog_events_session (session_key),
    CONSTRAINT fk_catalog_events_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    CONSTRAINT fk_catalog_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS catalog_order_item_attributions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_item_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    block_key VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_catalog_attr_order_item (order_item_id),
    INDEX idx_catalog_attr_order (order_id),
    INDEX idx_catalog_attr_block (block_key),
    INDEX idx_catalog_attr_course (course_id),
    CONSTRAINT fk_catalog_attr_order_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_catalog_attr_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_catalog_attr_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_catalog_attr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
