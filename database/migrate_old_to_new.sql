SET FOREIGN_KEY_CHECKS=0;

-- 1. Truncate Targets
TRUNCATE TABLE iservice_db.request_nas_details;
TRUNCATE TABLE iservice_db.request_it_support_details;
TRUNCATE TABLE iservice_db.request_internet_details;
TRUNCATE TABLE iservice_db.request_qrcode_details;
TRUNCATE TABLE iservice_db.request_photography_details;
TRUNCATE TABLE iservice_db.request_webdesign_details;
TRUNCATE TABLE iservice_db.request_printer_details;
TRUNCATE TABLE iservice_db.request_mc_details;
TRUNCATE TABLE iservice_db.request_email_details;
TRUNCATE TABLE iservice_db.service_requests;
TRUNCATE TABLE iservice_db.users;
TRUNCATE TABLE iservice_db.departments;
TRUNCATE TABLE iservice_db.prefixes;
TRUNCATE TABLE iservice_db.my_service;

-- 2. Migrate Core Data (Using Explicit Columns)
INSERT INTO iservice_db.prefixes (prefix_id, prefix_name, prefix_short, prefix_type, is_active, display_order)
SELECT prefix_id, prefix_name, prefix_short, prefix_type, is_active, display_order FROM green_theme_db.prefixes;

INSERT INTO iservice_db.my_service (id, service_code, service_name, service_name_en, description, icon, color_code, service_url, is_active, display_order, created_at, updated_at)
SELECT id, service_code, service_name, service_name_en, description, icon, color_code, service_url, is_active, display_order, created_at, updated_at FROM green_theme_db.my_service;

INSERT INTO iservice_db.users (user_id, username, password, prefix_id, first_name, last_name, email, role, department_id, position, status, last_login, created_at, updated_at)
SELECT user_id, username, password, prefix_id, first_name, last_name, email, role, department_id, position, status, last_login, created_at, updated_at FROM green_theme_db.users;
-- Note: 'phone' column in users might be missing in source or target or position mismatched. 
-- green_theme_db.users had 'phone' after email. iservice_db.users has 'phone' after email. 
-- Let's check green_theme_db description again to get 'phone' if it exists. 
-- DESCRIBE output showed 'phone' exists. So I will add it.

-- Updating Users insert to include phone
TRUNCATE TABLE iservice_db.users;
INSERT INTO iservice_db.users (user_id, username, password, prefix_id, first_name, last_name, email, phone, role, department_id, position, status, last_login, created_at, updated_at)
SELECT user_id, username, password, prefix_id, first_name, last_name, email, phone, role, department_id, position, status, last_login, created_at, updated_at FROM green_theme_db.users;


INSERT INTO iservice_db.departments 
    (department_id, parent_department_id, department_code, department_name, short_name, level, level_type, manager_user_id, status, created_at, updated_at)
SELECT 
    department_id, parent_department_id, department_code, department_name, short_name, level, level_type, manager_user_id, status, created_at, updated_at
FROM green_theme_db.departments;

-- 3. Migrate Service Requests
-- Mapping old fields to new fields
INSERT INTO iservice_db.service_requests (
    request_id, 
    user_id, 
    request_code, 
    service_code, 
    service_name,
    requester_prefix_id, 
    requester_name, 
    requester_position, 
    requester_phone, 
    requester_email,
    department_id, 
    department_name,
    subject, 
    description,
    status, 
    priority, 
    assigned_to, 
    created_at, 
    updated_at
)
SELECT
    id,
    COALESCE(user_id, 1), -- Fallback for null user_id
    request_code,
    service_code,
    COALESCE(service_name, service_code),
    requester_prefix_id,
    requester_name,
    position,
    requester_phone,
    requester_email,
    department_id,
    department,
    COALESCE(subject, CONCAT('Request ', request_code)), -- Ensure subject is not null
    COALESCE(description, notes, '-'), -- Fallback description
    status,
    priority,
    assigned_to_user_id,
    created_at,
    updated_at
FROM green_theme_db.service_requests;

-- 4. Migrate Existing Details (Using Explicit Columns to avoid mismatch)

INSERT INTO iservice_db.request_nas_details (id, request_id, folder_name, storage_size_gb, permission_type, shared_with, purpose, backup_required)
SELECT id, request_id, folder_name, storage_size_gb, permission_type, shared_with, purpose, backup_required FROM green_theme_db.request_nas_details;

INSERT INTO iservice_db.request_it_support_details (id, request_id, issue_type, device_type, device_brand, symptoms, location, urgency_level, error_message, when_occurred)
SELECT id, request_id, issue_type, device_type, device_brand, symptoms, location, urgency_level, error_message, when_occurred FROM green_theme_db.request_it_support_details;

INSERT INTO iservice_db.request_internet_details (id, request_id, request_type, location, building, room_number, number_of_users, required_speed, current_issue)
SELECT id, request_id, request_type, location, building, room_number, number_of_users, required_speed, current_issue FROM green_theme_db.request_internet_details;

INSERT INTO iservice_db.request_qrcode_details (id, request_id, qr_type, qr_content, qr_size, color_primary, color_background, logo_url, output_format, quantity, purpose)
SELECT id, request_id, qr_type, qr_content, qr_size, color_primary, color_background, logo_url, output_format, quantity, purpose FROM green_theme_db.request_qrcode_details;

INSERT INTO iservice_db.request_photography_details (id, request_id, event_name, event_type, event_date, event_time_start, event_time_end, event_location, number_of_photographers, video_required, drone_required, delivery_format, special_requirements)
SELECT id, request_id, event_name, event_type, event_date, event_time_start, event_time_end, event_location, number_of_photographers, video_required, drone_required, delivery_format, special_requirements FROM green_theme_db.request_photography_details;

INSERT INTO iservice_db.request_webdesign_details (id, request_id, website_type, project_name, purpose, target_audience, number_of_pages, features_required, has_existing_site, existing_url, domain_name, hosting_required, reference_sites, color_preferences, budget)
SELECT id, request_id, website_type, project_name, purpose, target_audience, number_of_pages, features_required, has_existing_site, existing_url, domain_name, hosting_required, reference_sites, color_preferences, budget FROM green_theme_db.request_webdesign_details;

INSERT INTO iservice_db.request_printer_details (id, request_id, issue_type, printer_type, printer_brand, printer_model, serial_number, location, problem_description, error_code, toner_color, supplies_needed)
SELECT id, request_id, issue_type, printer_type, printer_brand, printer_model, serial_number, location, problem_description, error_code, toner_color, supplies_needed FROM green_theme_db.request_printer_details;


-- 5. Copy Extra Tables that were missing in iservice_db
CREATE TABLE IF NOT EXISTS iservice_db.nav_menu SELECT * FROM green_theme_db.nav_menu;
CREATE TABLE IF NOT EXISTS iservice_db.tech_news SELECT * FROM green_theme_db.tech_news;
CREATE TABLE IF NOT EXISTS iservice_db.learning_resources SELECT * FROM green_theme_db.learning_resources;
CREATE TABLE IF NOT EXISTS iservice_db.related_agencies SELECT * FROM green_theme_db.related_agencies;
CREATE TABLE IF NOT EXISTS iservice_db.system_settings SELECT * FROM green_theme_db.system_settings;
CREATE TABLE IF NOT EXISTS iservice_db.pm25_data SELECT * FROM green_theme_db.pm25_data;
CREATE TABLE IF NOT EXISTS iservice_db.service_requests_backup SELECT * FROM green_theme_db.service_requests_backup;

SET FOREIGN_KEY_CHECKS=1;
