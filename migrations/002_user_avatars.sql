-- Profile pictures
ALTER TABLE users ADD COLUMN avatar_path VARCHAR(500) NULL AFTER password_hash;
