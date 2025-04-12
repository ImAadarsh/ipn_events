-- Create the user permissions table
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_event_unique` (`user_id`, `event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ipn_events_dash_users table instead of users table
CREATE TABLE IF NOT EXISTS `ipn_events_dash_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin', 'user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert initial users (from .env file - this should be run manually with proper credentials)
-- INSERT INTO `ipn_events_dash_users` (`username`, `password`, `role`) VALUES 
-- ('imkaadarsh', '@@PASSWORD_HASH@@', 'admin'),
-- ('gaurava.ipn', '@@PASSWORD_HASH@@', 'admin'),
-- ('pooja.ipn', '@@PASSWORD_HASH@@', 'user'),
-- ('vijeta.ipn', '@@PASSWORD_HASH@@', 'user'),
-- ('shvetambri.ipn', '@@PASSWORD_HASH@@', 'user'),
-- ('aarti.endeavour', '@@PASSWORD_HASH@@', 'user');

-- Insert default permissions for admin users (giving them access to everything)
INSERT INTO `user_permissions` (`user_id`, `event_type`, `can_view`, `can_export`) VALUES 
('imkaadarsh', 'conclaves', 1, 1),
('imkaadarsh', 'yuva', 1, 1),
('imkaadarsh', 'leaderssummit', 1, 1),
('imkaadarsh', 'misb', 1, 1),
('imkaadarsh', 'ils', 1, 1),
('imkaadarsh', 'quest', 1, 1),
('gaurava.ipn', 'conclaves', 1, 1),
('gaurava.ipn', 'yuva', 1, 1),
('gaurava.ipn', 'leaderssummit', 1, 1),
('gaurava.ipn', 'misb', 1, 1),
('gaurava.ipn', 'ils', 1, 1),
('gaurava.ipn', 'quest', 1, 1);

-- Sample permissions for regular users (customize as needed)
INSERT INTO `user_permissions` (`user_id`, `event_type`, `can_view`, `can_export`) VALUES 
('pooja.ipn', 'conclaves', 1, 0),
('pooja.ipn', 'yuva', 1, 0),
('vijeta.ipn', 'leaderssummit', 1, 0),
('vijeta.ipn', 'misb', 1, 0),
('shvetambri.ipn', 'ils', 1, 0),
('aarti.endeavour', 'quest', 1, 0); 