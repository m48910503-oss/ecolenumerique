-- Schema v45 : commandes gratuites + attribution coupon/promo par ligne de commande.

ALTER TABLE orders
    MODIFY COLUMN provider ENUM('fedapay','crypto','free') NOT NULL;

ALTER TABLE order_items
    ADD COLUMN promo_scope ENUM('none','promotion','targeted','company','coupon') NOT NULL DEFAULT 'none' AFTER unit_price_xof,
    ADD COLUMN promotion_id BIGINT UNSIGNED NULL AFTER promo_scope,
    ADD COLUMN coupon_code VARCHAR(32) NULL AFTER promotion_id,
    ADD INDEX idx_order_items_promo_scope (promo_scope),
    ADD INDEX idx_order_items_coupon_code (coupon_code),
    ADD INDEX idx_order_items_promotion_id (promotion_id),
    ADD CONSTRAINT fk_order_items_promotion FOREIGN KEY (promotion_id) REFERENCES course_promotions(id) ON DELETE SET NULL;
