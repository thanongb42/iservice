-- เพิ่ม UNIQUE KEY บน pm25_data เพื่อป้องกันข้อมูลซ้ำ
-- (INSERT IGNORE จะข้ามแถวที่ cid+sensor_timestamp ซ้ำกัน)

ALTER TABLE `pm25_data`
    ADD UNIQUE KEY `uq_cid_ts` (`cid`, `sensor_timestamp`);
