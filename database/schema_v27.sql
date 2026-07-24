-- Schema v27 : parrainage par cours (override des taux).
ALTER TABLE courses
    ADD COLUMN referral_override TINYINT(1) NOT NULL DEFAULT 0 AFTER price_xof,
    ADD COLUMN referral_l1_rate DECIMAL(5,2) NULL AFTER referral_override,
    ADD COLUMN referral_l2_rate DECIMAL(5,2) NULL AFTER referral_l1_rate,
    ADD COLUMN referral_l2_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER referral_l2_rate;
