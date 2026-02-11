-- Migration: Replace qr_code_link with qrcode_img in water_kiosks table
-- This migration removes the old qr_code_link column and uses the new qrcode_img column

-- Step 1: Add qrcode_img column if it doesn't exist
ALTER TABLE water_kiosks ADD COLUMN IF NOT EXISTS qrcode_img VARCHAR(255) NULL COMMENT 'Path to QR code image' AFTER location_name;

-- Step 2: Populate qrcode_img with standard paths for all water kiosks
UPDATE water_kiosks SET qrcode_img = CONCAT('uploads/qrcode_smart_water/qrcode_', kiosk_code, '.png') WHERE kiosk_code LIKE 'RSC%' AND qrcode_img IS NULL;

-- Step 3: Remove old qr_code_link column
ALTER TABLE water_kiosks DROP COLUMN IF EXISTS qr_code_link;

-- Verify the migration
SELECT 
    kiosk_code, 
    location_name, 
    qrcode_img 
FROM water_kiosks 
WHERE qrcode_img IS NOT NULL 
ORDER BY kiosk_code;

