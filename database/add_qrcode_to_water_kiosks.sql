-- Add QR Code Image Column to water_kiosks table
ALTER TABLE water_kiosks ADD COLUMN qrcode_img VARCHAR(255) NULL COMMENT 'Path to QR code image' AFTER address;

-- Update water_kiosks with QR code image paths
UPDATE water_kiosks SET qrcode_img = CONCAT('uploads/qrcode_smart_water/qrcode_', kiosk_code, '.png') WHERE kiosk_code LIKE 'RSC%';

-- Verify the updates
SELECT kiosk_code, qrcode_img FROM water_kiosks WHERE qrcode_img IS NOT NULL ORDER BY kiosk_code;
