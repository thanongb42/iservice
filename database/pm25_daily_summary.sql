-- ตาราง cache ค่าเฉลี่ยรายวัน PM2.5 (pre-computed)
CREATE TABLE IF NOT EXISTS `pm25_daily_summary` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cid`         VARCHAR(32)       NOT NULL,
    `summary_date` DATE             NOT NULL,
    `avg_pm25`    DECIMAL(6,2)      DEFAULT NULL,
    `max_pm25`    DECIMAL(6,2)      DEFAULT NULL,
    `min_pm25`    DECIMAL(6,2)      DEFAULT NULL,
    `avg_pm10`    DECIMAL(6,2)      DEFAULT NULL,
    `avg_temp`    DECIMAL(5,2)      DEFAULT NULL,
    `avg_humi`    DECIMAL(5,2)      DEFAULT NULL,
    `avg_co2`     DECIMAL(8,2)      DEFAULT NULL,
    `data_points` SMALLINT UNSIGNED DEFAULT 0,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_cid_date` (`cid`, `summary_date`),
    KEY `idx_date` (`summary_date`),
    KEY `idx_cid`  (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
