-- pm25_jumbo_stations.sql
-- เพิ่ม Jumbo Smart Bus Stop sensors (RS01, RS02, RS03) เข้าระบบ PM2.5
-- Import ใน phpMyAdmin: iservice_db (local) / rangsitadmin_iservice_db (production)

-- 1. เพิ่มคอลัมน์ api_provider ใน pm25_sensors
ALTER TABLE pm25_sensors
    ADD COLUMN api_provider ENUM('freshnergy','jumbo') NOT NULL DEFAULT 'freshnergy'
    AFTER cid;

-- 2. เพิ่ม 3 สถานีป้ายรถเมล์อัจฉริยะ (lat/lng ตั้งทีหลังผ่าน Admin → จัดการสถานี)
INSERT INTO pm25_sensors (location_name, cid, api_provider, lat, lng, is_active) VALUES
    ('ป้ายรถเมล์อัจฉริยะ RS01', 'RS01', 'jumbo', 13.990572, 100.644996, 1),
    ('ป้ายรถเมล์อัจฉริยะ RS02', 'RS02', 'jumbo', 13.988048, 100.633534, 1),
    ('ป้ายรถเมล์อัจฉริยะ RS03', 'RS03', 'jumbo', 13.985627, 100.622690, 1);
