-- Add English menu name column to nav_menu
ALTER TABLE nav_menu ADD COLUMN IF NOT EXISTS menu_name_en VARCHAR(255) DEFAULT NULL;

-- Parent menus
UPDATE nav_menu SET menu_name_en = 'Home'               WHERE id = 1;
UPDATE nav_menu SET menu_name_en = 'Online Services'    WHERE id = 2;
UPDATE nav_menu SET menu_name_en = 'Learning Resources' WHERE id = 3;
UPDATE nav_menu SET menu_name_en = 'Contact Us'         WHERE id = 4;

-- Children of Online Services
UPDATE nav_menu SET menu_name_en = 'Photography Service'       WHERE id = 10;
UPDATE nav_menu SET menu_name_en = 'MC / Emcee Service'        WHERE id = 9;
UPDATE nav_menu SET menu_name_en = 'Internet Service'          WHERE id = 5;
UPDATE nav_menu SET menu_name_en = 'IT Support'                WHERE id = 6;
UPDATE nav_menu SET menu_name_en = 'NAS Storage'               WHERE id = 7;
UPDATE nav_menu SET menu_name_en = 'Official Email'            WHERE id = 8;

-- Children of Learning Resources
UPDATE nav_menu SET menu_name_en = 'Internet Guide'            WHERE id = 12;
UPDATE nav_menu SET menu_name_en = 'Email Guide'               WHERE id = 13;
UPDATE nav_menu SET menu_name_en = 'NAS Guide'                 WHERE id = 11;

-- Children of Contact Us
UPDATE nav_menu SET menu_name_en = 'Location Map'              WHERE id = 14;
