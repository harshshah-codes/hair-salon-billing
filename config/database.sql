-- =====================================================================
--  Nirav Salon & Spa - Production Database Schema (MySQL 8)
--  Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `salon_saas`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `salon_saas`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- roles
-- ---------------------------------------------------------------------
CREATE TABLE `roles` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80)  NOT NULL,
  `slug`        VARCHAR(80)  NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `permissions` JSON         NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`       INT UNSIGNED NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `phone`         VARCHAR(20)  NULL,
  `password`      VARCHAR(255) NOT NULL,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login_at` TIMESTAMP NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`    TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  INDEX `idx_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- customers
-- ---------------------------------------------------------------------
CREATE TABLE `customers` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(120) NOT NULL,
  `email`             VARCHAR(190) NULL,
  `phone`             VARCHAR(20)  NULL,
  `address`           VARCHAR(255) NULL,
  `dob`               DATE         NULL,
  `gender`            ENUM('male','female','other') NULL,
  `notes`             TEXT         NULL,
  `lifetime_spend`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `outstanding`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `last_visit_at`     TIMESTAMP NULL,
  `status`            ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`        TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_customers_name` (`name`),
  INDEX `idx_customers_phone` (`phone`),
  INDEX `idx_customers_status` (`status`),
  INDEX `idx_customers_last_visit` (`last_visit_at`),
  INDEX `idx_customers_outstanding` (`outstanding`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- employees
-- ---------------------------------------------------------------------
CREATE TABLE `employees` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(120) NOT NULL,
  `phone`           VARCHAR(20)  NULL,
  `email`           VARCHAR(190) NULL,
  `role`            VARCHAR(80)  NULL,
  `photo`           VARCHAR(255) NULL,
  `commission_type` ENUM('percentage','fixed','none') NOT NULL DEFAULT 'none',
  `commission_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `hire_date`       DATE NULL,
  `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_employees_name` (`name`),
  INDEX `idx_employees_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- services
-- ---------------------------------------------------------------------
CREATE TABLE `services` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(160) NOT NULL,
  `category`         VARCHAR(120) NULL,
  `duration_minutes` INT UNSIGNED NULL,
  `price`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `description`      TEXT NULL,
  `status`           ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`       TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_services_name` (`name`),
  INDEX `idx_services_category` (`category`),
  INDEX `idx_services_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- packages (reusable package templates)
-- ---------------------------------------------------------------------
CREATE TABLE `packages` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(160) NOT NULL,
  `selling_price`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `credits`        INT UNSIGNED NOT NULL DEFAULT 0,
  `validity_days`  INT UNSIGNED NOT NULL DEFAULT 30,
  `description`    TEXT NULL,
  `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`     TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_packages_name` (`name`),
  INDEX `idx_packages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- customer_packages (packages owned by a customer, incl. custom ones)
-- ---------------------------------------------------------------------
CREATE TABLE `customer_packages` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id`     INT UNSIGNED NOT NULL,
  `package_id`      INT UNSIGNED NULL,
  `name`            VARCHAR(160) NOT NULL,
  `price`           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `credits`         INT UNSIGNED NOT NULL DEFAULT 0,
  `remaining_credits` INT UNSIGNED NOT NULL DEFAULT 0,
  `validity_days`   INT UNSIGNED NULL,
  `starts_at`       DATE NULL,
  `expires_at`      DATE NULL,
  `status`          ENUM('active','expired','exhausted','cancelled') NOT NULL DEFAULT 'active',
  `notes`           TEXT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cp_customer` (`customer_id`),
  INDEX `idx_cp_package` (`package_id`),
  INDEX `idx_cp_status` (`status`),
  INDEX `idx_cp_expires` (`expires_at`),
  CONSTRAINT `fk_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- customer_package_transactions (debit/credit movements on packages)
-- ---------------------------------------------------------------------
CREATE TABLE `customer_package_transactions` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_package_id` INT UNSIGNED NOT NULL,
  `invoice_id`         INT UNSIGNED NULL,
  `type`               ENUM('credit','debit') NOT NULL DEFAULT 'debit',
  `credits`            INT UNSIGNED NOT NULL DEFAULT 0,
  `amount`             DECIMAL(12,2) NULL,
  `description`        VARCHAR(255) NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cpt_package` (`customer_package_id`),
  INDEX `idx_cpt_invoice` (`invoice_id`),
  CONSTRAINT `fk_cpt_package` FOREIGN KEY (`customer_package_id`) REFERENCES `customer_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- invoices
-- ---------------------------------------------------------------------
CREATE TABLE `invoices` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number`    VARCHAR(40)  NOT NULL UNIQUE,
  `customer_id`       INT UNSIGNED NOT NULL,
  `subtotal`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount`   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_note`     VARCHAR(255) NULL,
  `gst_rate`          DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `gst_amount`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `package_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total`             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `amount_payable`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `due_amount`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notes`             TEXT NULL,
  `status`            ENUM('draft','final','cancelled') NOT NULL DEFAULT 'draft',
  `payment_status`    ENUM('unpaid','partial','paid')   NOT NULL DEFAULT 'unpaid',
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoices_number` (`invoice_number`),
  INDEX `idx_invoices_customer` (`customer_id`),
  INDEX `idx_invoices_status` (`status`),
  INDEX `idx_invoices_payment` (`payment_status`),
  INDEX `idx_invoices_created` (`created_at`),
  CONSTRAINT `fk_invoices_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- invoice_items
-- ---------------------------------------------------------------------
CREATE TABLE `invoice_items` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id`   INT UNSIGNED NOT NULL,
  `service_id`   INT UNSIGNED NULL,
  `service_name` VARCHAR(160) NOT NULL,
  `price`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `qty`          INT UNSIGNED NOT NULL DEFAULT 1,
  `total`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  INDEX `idx_items_invoice` (`invoice_id`),
  INDEX `idx_items_service` (`service_id`),
  CONSTRAINT `fk_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- employee_allocations (which employee performed which item, for how much)
-- ---------------------------------------------------------------------
CREATE TABLE `employee_allocations` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id`      INT UNSIGNED NOT NULL,
  `invoice_item_id` INT UNSIGNED NOT NULL,
  `employee_id`     INT UNSIGNED NOT NULL,
  `amount`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_alloc_invoice` (`invoice_id`),
  INDEX `idx_alloc_item` (`invoice_item_id`),
  INDEX `idx_alloc_employee` (`employee_id`),
  CONSTRAINT `fk_alloc_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alloc_item` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alloc_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- payments
-- ---------------------------------------------------------------------
CREATE TABLE `payments` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id`   INT UNSIGNED NOT NULL,
  `customer_id`  INT UNSIGNED NOT NULL,
  `user_id`      INT UNSIGNED NULL,
  `amount`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `method`       ENUM('cash','card','upi') NOT NULL DEFAULT 'cash',
  `reference`    VARCHAR(120) NULL,
  `received_at`  TIMESTAMP NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_payments_invoice` (`invoice_id`),
  INDEX `idx_payments_customer` (`customer_id`),
  INDEX `idx_payments_method` (`method`),
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `fk_payments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ledger_entries
-- ---------------------------------------------------------------------
CREATE TABLE `ledger_entries` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id`    INT UNSIGNED NOT NULL,
  `invoice_id`     INT UNSIGNED NULL,
  `payment_id`     INT UNSIGNED NULL,
  `type`           ENUM('invoice','payment','package','adjustment') NOT NULL DEFAULT 'invoice',
  `amount`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `balance_after`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `description`    VARCHAR(255) NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ledger_customer` (`customer_id`),
  INDEX `idx_ledger_invoice` (`invoice_id`),
  CONSTRAINT `fk_ledger_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- customer_notes
-- ---------------------------------------------------------------------
CREATE TABLE `customer_notes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NULL,
  `note`        TEXT NOT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_notes_customer` (`customer_id`),
  CONSTRAINT `fk_notes_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- settings (key/value store)
-- ---------------------------------------------------------------------
CREATE TABLE `settings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(120) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- activity_logs
-- ---------------------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NULL,
  `action`       VARCHAR(80)  NOT NULL,
  `entity_type`  VARCHAR(80)  NULL,
  `entity_id`    INT UNSIGNED NULL,
  `data`         JSON NULL,
  `ip_address`   VARCHAR(45) NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activity_user` (`user_id`),
  INDEX `idx_activity_entity` (`entity_type`, `entity_id`),
  INDEX `idx_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
