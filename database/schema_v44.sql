-- Schema v44 : mode coupon avec code unique sur les promotions.

ALTER TABLE course_promotions
    ADD COLUMN promo_mode ENUM('promotion','coupon') NOT NULL DEFAULT 'promotion' AFTER teacher_id,
    ADD COLUMN coupon_code VARCHAR(32) NULL AFTER promo_value,
    ADD UNIQUE KEY uq_course_promotions_coupon_code (coupon_code),
    ADD INDEX idx_course_promotions_mode_end (promo_mode, promo_end_at);
