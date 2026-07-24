-- =====================================================================
-- Nexu Hosting - Esquema Transaccional MySQL / MariaDB
-- Motor: InnoDB (soporte de transacciones + integridad referencial FK)
-- Codificación: utf8mb4 (soporte completo de emojis / caracteres especiales)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Tabla: users
-- Control total de identidad, credenciales y privilegios de rol
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(50)  NOT NULL UNIQUE,
  `email`         VARCHAR(100) NOT NULL UNIQUE,
  `password`      VARCHAR(255) NOT NULL COMMENT 'Hash PASSWORD_ARGON2ID',
  `role`          ENUM('admin','client') NOT NULL DEFAULT 'client',
  `status`        ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until`  DATETIME NULL DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla: plans
-- Catálogo de especificaciones técnicas de hosting / servidores Minecraft
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plans` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL UNIQUE,
  `price_PEN`   DECIMAL(10,2) NOT NULL,
  `ram_gb`      SMALLINT UNSIGNED NOT NULL,
  `cpu_cores`   SMALLINT UNSIGNED NOT NULL,
  `disk_gb`     SMALLINT UNSIGNED NOT NULL,
  `plan_type`   ENUM('web','minecraft') NOT NULL,
  `description` VARCHAR(255) NULL,
  `is_active`   BOOLEAN NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_plans_type` (`plan_type`),
  INDEX `idx_plans_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla: orders
-- Núcleo financiero: pedidos, método de pago y estado de verificación
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT UNSIGNED NOT NULL,
  `plan_id`        INT UNSIGNED NOT NULL,
  `total_amount`   DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('yape','plin','banco_nacion','interbank','bcp') NOT NULL,
  `status`         ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `voucher_image`  VARCHAR(255) NULL COMMENT 'Nombre de archivo aleatorio, sin ruta absoluta',
  `operation_code` VARCHAR(100) NULL COMMENT 'N° de operación declarado por el cliente',
  `admin_notes`    TEXT NULL,
  `reviewed_by`    INT UNSIGNED NULL COMMENT 'ID del admin que verificó la orden',
  `reviewed_at`    DATETIME NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user`  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_orders_plan`  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_admin` FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla: servers
-- Instancias de infraestructura aprovisionadas tras la verificación
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `servers` (
  `id`                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`                 INT UNSIGNED NOT NULL,
  `plan_id`                 INT UNSIGNED NOT NULL,
  `order_id`                INT UNSIGNED NOT NULL,
  `server_name`             VARCHAR(100) NOT NULL,
  `node_ip`                 VARCHAR(50) NULL,
  `pterodactyl_server_id`   INT UNSIGNED NULL,
  `status`                  ENUM('installing','active','suspended','terminated') NOT NULL DEFAULT 'installing',
  `cpu_usage_pct`           DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `ram_usage_pct`           DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `disk_usage_pct`          DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `next_due_date`           DATE NULL,
  `created_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_servers_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_servers_plan`  FOREIGN KEY (`plan_id`)  REFERENCES `plans`(`id`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_servers_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE RESTRICT,
  INDEX `idx_servers_status` (`status`),
  INDEX `idx_servers_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla: audit_log
-- Registro de auditoría de acciones administrativas críticas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id`    INT UNSIGNED NOT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50)  NOT NULL,
  `target_id`   INT UNSIGNED NOT NULL,
  `details`     TEXT NULL,
  `ip_address`  VARCHAR(45) NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Datos semilla de ejemplo (planes)
-- ---------------------------------------------------------------------
INSERT INTO `plans` (`name`, `slug`, `price_PEN`, `ram_gb`, `cpu_cores`, `disk_gb`, `plan_type`, `description`) VALUES
('Web Starter',       'web-starter',       19.90,  2,  1, 20,  'web',       'Ideal para landing pages y blogs pequeños'),
('Web Business',      'web-business',      49.90,  4,  2, 60,  'web',       'Para tiendas online y sitios con tráfico medio'),
('Web Enterprise',    'web-enterprise',    99.90,  8,  4, 150, 'web',       'Alto rendimiento con SSD NVMe dedicado'),
('Minecraft Pebble',  'mc-pebble',         14.90,  2,  1, 10,  'minecraft', 'Servidor pequeño para grupos de amigos'),
('Minecraft Boulder', 'mc-boulder',        34.90,  6,  2, 30,  'minecraft', 'Modpacks grandes y comunidades activas'),
('Minecraft Summit',  'mc-summit',         79.90,  12, 4, 80,  'minecraft', 'Red de servidores / BungeeCord de alto tráfico');
