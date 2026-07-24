-- Schema v28 : promotions par cours.
ALTER TABLE courses
    ADD COLUMN promo_type ENUM('amount','percent') NULL AFTER price_xof,
    ADD COLUMN promo_value DECIMAL(10,2) NULL AFTER promo_type,
    ADD COLUMN promo_start_at DATETIME NULL AFTER promo_value,
    ADD COLUMN promo_end_at DATETIME NULL AFTER promo_start_at;
