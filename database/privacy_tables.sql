-- Privacy & Analytics Tables v2
-- ใช้ visitor_hash (fingerprint) จัดการ session server-side
-- 30 นาที inactive = session ใหม่

-- ── Sessions (1 row ต่อ session) ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `visitor_sessions` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id`    VARCHAR(64)  NOT NULL UNIQUE,   -- server-generated UUID
    `client_sid`    VARCHAR(64)  DEFAULT '',         -- client hint (for survey dedup)
    `visitor_hash`  VARCHAR(64)  NOT NULL,           -- hash(ip+ua) fingerprint
    `device_type`   ENUM('desktop','mobile','tablet') DEFAULT 'desktop',
    `browser`       VARCHAR(100) DEFAULT '',
    `referrer`      VARCHAR(500) DEFAULT '',
    `consent_given` TINYINT(1)   DEFAULT 0,
    `page_count`    SMALLINT UNSIGNED DEFAULT 0,
    `started_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `last_activity` DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_visitor_hash`    (`visitor_hash`),
    INDEX `idx_started_at`      (`started_at`),
    INDEX `idx_last_activity`   (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pageviews (1 row ต่อการเปิดหน้า) ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `visitor_pageviews` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id`    VARCHAR(64)  NOT NULL,
    `visitor_hash`  VARCHAR(64)  NOT NULL,
    `page_url`      VARCHAR(500) NOT NULL,
    `page_title`    VARCHAR(255) DEFAULT '',
    `page_category` VARCHAR(50)  DEFAULT 'general',  -- 'index','pm25_dashboard','pm25_report','pm25_chart'
    `visited_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_session`         (`session_id`),
    INDEX `idx_visitor_hash`    (`visitor_hash`),
    INDEX `idx_category_date`   (`page_category`, `visited_at`),
    INDEX `idx_visited_at`      (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Satisfaction Surveys ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `satisfaction_surveys` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_sid`   VARCHAR(64)  NOT NULL,
    `page_url`     VARCHAR(500) NOT NULL,
    `rating`       TINYINT UNSIGNED NOT NULL,
    `comment`      TEXT,
    `category`     VARCHAR(50)  DEFAULT 'general',
    `visitor_hash` VARCHAR(64)  DEFAULT '',
    `submitted_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_submitted_at` (`submitted_at`),
    INDEX `idx_rating`       (`rating`),
    INDEX `idx_category`     (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Old tables (drop if migrating from v1) ────────────────────────────────────
-- DROP TABLE IF EXISTS visitor_logs;
