ALTER TABLE admin_announcements
    DROP COLUMN IF EXISTS title_b,
    DROP COLUMN IF EXISTS ab_enabled,
    DROP COLUMN IF EXISTS sent_count_b,
    DROP COLUMN IF EXISTS read_count_b,
    DROP COLUMN IF EXISTS click_count_b;

ALTER TABLE notifications
    DROP COLUMN IF EXISTS variant;
