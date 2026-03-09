-- สร้างตาราง visitor_stats สำหรับนับจำนวนผู้เข้าชมรายวัน
-- Run ใน phpMyAdmin บน production: rangsitadmin_iservice_db

CREATE TABLE IF NOT EXISTS `visitor_stats` (
    `visit_date`  DATE  NOT NULL,
    `visit_count` INT   NOT NULL DEFAULT 0,
    PRIMARY KEY (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Daily website visitor counter';
