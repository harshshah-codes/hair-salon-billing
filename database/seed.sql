-- ============================================================
-- Nirav Hair Storm - Seed data
-- The installer (public/install.php) runs this file after schema.sql
-- and then creates the default admin account with a hashed password.
-- ============================================================

SET NAMES utf8mb4;

-- Roles -------------------------------------------------------------------
INSERT INTO `roles` (`name`, `slug`, `description`, `permissions`, `is_system`) VALUES
('Super Admin', 'superadmin', 'Full access — can create, edit and delete in every module', '{"*": true}', 1),
('Admin', 'admin', 'Can add (create) records but cannot edit or delete', '{"dashboard":true,"customers":["view","create"],"billing":["view","create"],"packages":["view","create"],"services":["view","create"],"employees":["view","create"],"reports":["view"]}', 1),
('Manager', 'manager', 'Day-to-day business management', '{"dashboard":true,"customers":["view","create","edit","delete"],"billing":["view","create"],"packages":["view","create","edit"],"services":["view","create","edit"],"employees":["view"],"reports":["view"],"settings":["view"]}', 1),
('Front Desk', 'front-desk', 'Billing and customer management', '{"dashboard":true,"customers":["view","create","edit"],"billing":["view","create"],"packages":["view"],"services":["view"],"employees":["view"],"reports":["view"]}', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Default superadmin (password set by installer via password_hash)
INSERT INTO `users` (`role_id`, `name`, `email`, `password`, `phone`, `status`)
SELECT r.`id`, 'Administrator', 'admin@salon.local', 'CHANGE_ME_INSTALLER', '9000000000', 'active'
FROM `roles` r WHERE r.`slug` = 'superadmin'
AND NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'admin@salon.local');

-- Services ---------------------------------------------------------------
INSERT INTO `services` (`name`, `category`, `duration_minutes`, `price`, `description`, `status`) VALUES
('Haircut', 'Hair', 30, 300.00, 'Classic haircut with wash and style', 'active'),
('Beard Trim', 'Grooming', 20, 150.00, 'Beard shaping with hot towel', 'active'),
('Hair Color', 'Color', 90, 1800.00, 'Premium ammonia-free hair colouring', 'active'),
('Keratin Treatment', 'Treatment', 120, 3500.00, 'Smoothness and frizz control treatment', 'active'),
('Facial', 'Skin', 45, 900.00, 'Signature deep-cleansing facial', 'active'),
('Manicure', 'Nails', 40, 500.00, 'Classic manicure with paraffin', 'active'),
('Pedicure', 'Nails', 45, 600.00, 'Classic pedicure with scrub', 'active'),
('Full Body Massage', 'Spa', 60, 1500.00, 'Relaxing aromatherapy body massage', 'active'),
('Head Massage', 'Spa', 25, 350.00, 'Stress-relief head and neck massage', 'active'),
('Hair Spa', 'Treatment', 45, 800.00, 'Deep conditioning hair spa ritual', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Packages ---------------------------------------------------------------
INSERT INTO `packages` (`name`, `selling_price`, `credits`, `validity_days`, `description`, `status`) VALUES
('Grooming Starter', 1500.00, 6, 60, '6 credits for haircuts and basic grooming', 'active'),
('Signature Hair Plan', 5000.00, 12, 90, '12 credits for hair services', 'active'),
('Complete Spa Ritual', 9000.00, 15, 120, '15 credits across spa and skin services', 'active'),
('Deluxe Family Pack', 12000.00, 20, 180, '20 credits shared across all services', 'active'),
('Monthly Regular', 2500.00, 4, 30, '4 credits valid for one month', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Employees --------------------------------------------------------------
INSERT INTO `branches` (`name`, `address`, `phone`, `status`) VALUES
('Main Branch', '12, MG Road, Pune, Maharashtra 411001', '9822001122', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `employees` (`branch_id`, `name`, `mobile`, `email`, `designation`, `commission_rate`, `status`, `joined_at`) VALUES
((SELECT `id` FROM `branches` LIMIT 1), 'Rajesh Verma', '9811000001', 'rajesh@salon.local', 'Senior Hair Stylist', 40.00, 'active', '2021-03-15'),
((SELECT `id` FROM `branches` LIMIT 1), 'Priya Sharma', '9811000002', 'priya@salon.local', 'Beautician', 35.00, 'active', '2022-01-10'),
((SELECT `id` FROM `branches` LIMIT 1), 'Amit Patel', '9811000003', 'amit@salon.local', 'Hair Colorist', 38.00, 'active', '2021-11-01'),
((SELECT `id` FROM `branches` LIMIT 1), 'Sneha Kulkarni', '9811000004', 'sneha@salon.local', 'Spa Therapist', 32.00, 'active', '2023-05-20'),
((SELECT `id` FROM `branches` LIMIT 1), 'Vikram Singh', '9811000005', 'vikram@salon.local', 'Barber', 30.00, 'active', '2022-08-14'),
((SELECT `id` FROM `branches` LIMIT 1), 'Neha Gupta', '9811000006', 'neha@salon.local', 'Nail Technician', 28.00, 'inactive', '2023-02-11')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Settings ---------------------------------------------------------------
INSERT INTO `settings` (`key`, `value`) VALUES
('business_name', 'Nirav Hair Storm'),
('business_address', '12, MG Road, Pune, Maharashtra 411001'),
('business_phone', '9822001122'),
('business_email', 'hello@niravhairstorm.in'),
('business_gst', '27AABCN1234F1Z5'),
('business_logo', ''),
('invoice_prefix', 'INV-'),
('invoice_footer', 'Thank you for visiting Nirav Hair Storm. We look forward to seeing you again!'),
('invoice_terms', '1. Services are billed at the prevailing rate card.\n2. Packages are non-transferable and subject to validity.\n3. No cash refunds on services rendered.'),
('gst_percent', '18.00'),
('theme_mode', 'light'),
('invoice_sequence', '0'),
('reports_default_days', '30')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
