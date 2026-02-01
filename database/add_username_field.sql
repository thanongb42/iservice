-- Add username field to users table
-- Run this SQL to update the database

ALTER TABLE users
ADD COLUMN username VARCHAR(50) UNIQUE AFTER user_id;

-- Update existing users with username based on email (before @ sign)
UPDATE users
SET username = SUBSTRING_INDEX(email, '@', 1)
WHERE username IS NULL;

-- Make username NOT NULL after setting values
ALTER TABLE users
MODIFY COLUMN username VARCHAR(50) NOT NULL UNIQUE;

-- Add index for faster username lookups
CREATE INDEX idx_username ON users(username);
