-- Schema v47 : pilotage avance des comptes entreprise (phases 1,2,3,4,5,7).

ALTER TABLE company_members
    MODIFY COLUMN role ENUM('owner','admin','manager','member','viewer') NOT NULL DEFAULT 'member';

ALTER TABLE company_members
    ADD INDEX idx_company_members_role (role);

ALTER TABLE users
    ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER auth_provider,
    ADD COLUMN mfa_enabled_at DATETIME NULL AFTER mfa_enabled,
    ADD INDEX idx_users_mfa_enabled (mfa_enabled);

CREATE TABLE IF NOT EXISTS company_teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_teams_name (company_id, name),
    INDEX idx_company_teams_company_status (company_id, status),
    CONSTRAINT fk_company_teams_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_teams_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    member_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_team_member (team_id, member_id),
    INDEX idx_company_team_members_company (company_id),
    INDEX idx_company_team_members_member (member_id),
    CONSTRAINT fk_company_team_members_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_team_members_team FOREIGN KEY (team_id) REFERENCES company_teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_team_members_member FOREIGN KEY (member_id) REFERENCES company_members(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_team_members_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_onboarding_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    task_key VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    due_at DATETIME NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending','in_progress','done','blocked') NOT NULL DEFAULT 'pending',
    owner_user_id BIGINT UNSIGNED NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_onboarding_task (company_id, task_key),
    INDEX idx_company_onboarding_status (company_id, status),
    INDEX idx_company_onboarding_due_at (due_at),
    CONSTRAINT fk_company_onboarding_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_onboarding_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_sso_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('saml','oidc') NOT NULL DEFAULT 'oidc',
    domain VARCHAR(190) NULL,
    issuer VARCHAR(255) NULL,
    entry_point VARCHAR(255) NULL,
    cert_pem TEXT NULL,
    client_id VARCHAR(190) NULL,
    client_secret_enc TEXT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_sso_company (company_id),
    INDEX idx_company_sso_enabled (is_enabled),
    CONSTRAINT fk_company_sso_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_sso_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_activity_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    meta_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company_activity_company_created (company_id, created_at),
    INDEX idx_company_activity_actor (actor_user_id),
    CONSTRAINT fk_company_activity_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_activity_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_metrics_daily (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    metric_date DATE NOT NULL,
    members_total INT UNSIGNED NOT NULL DEFAULT 0,
    members_active INT UNSIGNED NOT NULL DEFAULT 0,
    invites_pending INT UNSIGNED NOT NULL DEFAULT 0,
    activation_30d DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    wau INT UNSIGNED NOT NULL DEFAULT 0,
    mau INT UNSIGNED NOT NULL DEFAULT 0,
    wau_mau_ratio DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    retention_90d DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    expansion_30d INT NOT NULL DEFAULT 0,
    churn_30d DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_metrics_daily (company_id, metric_date),
    INDEX idx_company_metrics_daily_date (metric_date),
    CONSTRAINT fk_company_metrics_daily_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_success_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    owner_user_id BIGINT UNSIGNED NULL,
    review_date DATE NOT NULL,
    cadence ENUM('monthly','quarterly') NOT NULL DEFAULT 'monthly',
    risk_level ENUM('low','medium','high') NOT NULL DEFAULT 'low',
    status ENUM('planned','done','blocked') NOT NULL DEFAULT 'planned',
    notes TEXT NULL,
    next_actions TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_success_reviews_company_date (company_id, review_date),
    INDEX idx_company_success_reviews_status (status),
    CONSTRAINT fk_company_success_reviews_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_company_success_reviews_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
