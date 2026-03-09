-- Migration: เพิ่มคอลัมน์ requester_line_user_id ใน service_requests
-- วันที่: 2026-03-08
-- ใช้สำหรับเก็บ LINE User ID ของผู้ยื่นคำขอ เพื่อส่งแจ้งเตือนผ่าน LINE Messaging API

ALTER TABLE service_requests
    ADD COLUMN requester_line_user_id VARCHAR(50) NULL DEFAULT NULL
        COMMENT 'LINE User ID ของผู้ยื่นคำขอ (ได้จาก LINE Login)'
    AFTER requester_position;
