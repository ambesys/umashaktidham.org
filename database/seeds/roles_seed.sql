-- Seed roles with levels (gap-based to allow mid-level roles later)
INSERT INTO roles (name, description, level) VALUES
('user', 'Regular member user', 11),
('sponsor', 'Sponsor with donor-level access', 21),
('committee_member', 'Committee member with committee access', 31),
('moderator', 'Moderator with permissions to manage content', 41),
('admin', 'Administrator with full access', 51)
ON DUPLICATE KEY UPDATE description = VALUES(description), level = VALUES(level);