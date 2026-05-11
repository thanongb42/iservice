-- เพิ่มคอลัมน์ temperature และ humidity ใน pm25_data
ALTER TABLE `pm25_data`
    ADD COLUMN `temperature` float DEFAULT NULL AFTER `pm25`,
    ADD COLUMN `humidity`    float DEFAULT NULL AFTER `temperature`;
