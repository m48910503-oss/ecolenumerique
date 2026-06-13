-- Schema v43 : deduplication des webhooks paiement (anti-doublon).

CREATE TABLE IF NOT EXISTS payment_webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(32) NOT NULL,
    event_key CHAR(64) NOT NULL,
    event_name VARCHAR(120) NOT NULL DEFAULT '',
    transaction_reference VARCHAR(120) NULL,
    payload_json LONGTEXT NULL,
    processed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payment_webhook_events_provider_key (provider, event_key),
    INDEX idx_payment_webhook_events_processed (processed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
