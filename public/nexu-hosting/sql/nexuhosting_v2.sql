sigue-- ============================================================
-- NEXU HOSTING v2.0 - Esquema Transaccional Completo
-- Compatible: MySQL 8.0+ / MariaDB 10.5+
-- Charset: utf8mb4 (soporte emoji y caracteres especiales)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

CREATE DATABASE IF NOT EXISTS `nexuhosting`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `nexuhosting`;

-- ------------------------------------------------------------
-- TABLA: users
-- Control de identidad, roles y estado de cuentas
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`               INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `username`         VARCHAR(50)       NOT NULL UNIQUE,
  `email`            VARCHAR(100)      NOT NULL UNIQUE,
  `password`         VARCHAR(255)      NOT NULL COMMENT 'Argon2ID o bcrypt via password_hash()',
  `full_name`        VARCHAR(150)      DEFAULT NULL,
  `phone`            VARCHAR(25)       DEFAULT NULL,
  `country`          VARCHAR(60)       DEFAULT NULL,
  `avatar_url`       VARCHAR(500)      DEFAULT NULL,
  `role`             ENUM('admin','client')           NOT NULL DEFAULT 'client',
  `status`           ENUM('active','suspended','banned') NOT NULL DEFAULT 'active',
  -- OAuth
  `google_id`        VARCHAR(100)      DEFAULT NULL UNIQUE,
  `discord_id`       VARCHAR(100)      DEFAULT NULL UNIQUE,
  `oauth_provider`   VARCHAR(20)       DEFAULT NULL COMMENT 'google|discord|local',
  -- Seguridad
  `email_verified`   TINYINT(1)        NOT NULL DEFAULT 0,
  `email_verify_token` VARCHAR(64)     DEFAULT NULL,
  `two_fa_secret`    VARCHAR(64)       DEFAULT NULL,
  `two_fa_enabled`   TINYINT(1)        NOT NULL DEFAULT 0,
  -- Rate limiting
  `login_attempts`   TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `locked_until`     DATETIME          DEFAULT NULL,
  -- Preferencias
  `preferred_currency` ENUM('PEN','USD','EUR') NOT NULL DEFAULT 'PEN',
  `language`         VARCHAR(5)        NOT NULL DEFAULT 'es',
  `balance`          DECIMAL(10,2)     NOT NULL DEFAULT 0.00 COMMENT 'Crédito en cuenta',
  -- Timestamps
  `created_at`       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at`    DATETIME          DEFAULT NULL,
  `last_login_ip`    VARCHAR(45)       DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: collaborators
-- Sección pública de colaboradores / equipo con Discord
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `collaborators`;
CREATE TABLE `collaborators` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)  NOT NULL,
  `role_title`  VARCHAR(100)  NOT NULL COMMENT 'ej: CEO, Head of Support, Developer',
  `description` TEXT          DEFAULT NULL,
  `avatar_url`  VARCHAR(500)  DEFAULT NULL,
  `discord_tag` VARCHAR(50)   DEFAULT NULL COMMENT 'ej: usuario#0001',
  `discord_id`  VARCHAR(100)  DEFAULT NULL COMMENT 'ID numérico Discord para enlace',
  `twitter_url` VARCHAR(500)  DEFAULT NULL,
  `github_url`  VARCHAR(500)  DEFAULT NULL,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `sort_order`  INT           NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: plans
-- Catálogo de planes de hosting web y servidores Minecraft
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `plans`;
CREATE TABLE `plans` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100)   NOT NULL,
  `slug`         VARCHAR(120)   NOT NULL UNIQUE,
  `description`  TEXT           DEFAULT NULL,
  `plan_type`    ENUM('web','minecraft','vps','other') NOT NULL DEFAULT 'minecraft',
  -- Especificaciones técnicas
  `ram_gb`       DECIMAL(5,2)   NOT NULL DEFAULT 0 COMMENT 'GB de RAM DDR4/DDR5',
  `cpu_cores`    DECIMAL(4,1)   NOT NULL DEFAULT 0 COMMENT 'Núcleos vCPU',
  `disk_gb`      INT UNSIGNED   NOT NULL DEFAULT 0 COMMENT 'GB NVMe SSD',
  `bandwidth_tb` DECIMAL(5,2)   DEFAULT NULL COMMENT 'TB de ancho de banda, NULL = ilimitado',
  `player_slots` INT UNSIGNED   DEFAULT NULL COMMENT 'Slots de jugadores (Minecraft)',
  -- Precios en soles peruanos (PEN) - moneda base
  `price_pen`    DECIMAL(10,2)  NOT NULL COMMENT 'Precio mensual en PEN',
  `features`     JSON           DEFAULT NULL COMMENT 'Lista de características adicionales',
  -- Estado
  `is_active`    TINYINT(1)     NOT NULL DEFAULT 1,
  `is_featured`  TINYINT(1)     NOT NULL DEFAULT 0,
  `sort_order`   INT            NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type_active` (`plan_type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: orders
-- Núcleo financiero: cada pedido con su comprobante
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED   NOT NULL,
  `plan_id`         INT UNSIGNED   NOT NULL,
  -- Montos almacenados en PEN y USD para histórico
  `amount_pen`      DECIMAL(10,2)  NOT NULL COMMENT 'Monto en Soles',
  `amount_usd`      DECIMAL(10,2)  DEFAULT NULL COMMENT 'Monto en USD al tipo de cambio del momento',
  `exchange_rate`   DECIMAL(10,4)  DEFAULT NULL COMMENT 'TC usado en el pedido',
  `currency_paid`   ENUM('PEN','USD','EUR') NOT NULL DEFAULT 'PEN',
  -- Pago
  `payment_method`  ENUM('yape','plin','banco_nacion','interbank','bcp','stripe','paypal','crypto') NOT NULL,
  `status`          ENUM('pending','verified','rejected','refunded') NOT NULL DEFAULT 'pending',
  `voucher_image`   VARCHAR(300)   DEFAULT NULL COMMENT 'Ruta relativa al comprobante subido',
  `voucher_hash`    VARCHAR(64)    DEFAULT NULL COMMENT 'SHA-256 del archivo para integridad',
  `admin_notes`     TEXT           DEFAULT NULL COMMENT 'Notas del admin (motivo de rechazo, etc.)',
  `verified_by`     INT UNSIGNED   DEFAULT NULL COMMENT 'FK al admin que aprobó/rechazó',
  `verified_at`     DATETIME       DEFAULT NULL,
  -- Facturación
  `billing_cycle`   ENUM('monthly','quarterly','annually') NOT NULL DEFAULT 'monthly',
  `invoice_number`  VARCHAR(30)    DEFAULT NULL UNIQUE,
  -- Timestamps
  `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_verifiedby` FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_user_status` (`user_id`, `status`),
  INDEX `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: servers
-- Instancias de infraestructura aprovisionadas post-pago
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `servers`;
CREATE TABLE `servers` (
  `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`              INT UNSIGNED  NOT NULL,
  `plan_id`              INT UNSIGNED  NOT NULL,
  `order_id`             INT UNSIGNED  NOT NULL,
  `server_name`          VARCHAR(100)  NOT NULL,
  `node_ip`              VARCHAR(50)   DEFAULT NULL COMMENT 'IP del nodo físico',
  `server_ip`            VARCHAR(50)   DEFAULT NULL COMMENT 'IP pública del servidor del cliente',
  `server_port`          SMALLINT UNSIGNED DEFAULT NULL,
  `pterodactyl_server_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID en Pterodactyl Panel',
  `pterodactyl_uuid`     VARCHAR(64)   DEFAULT NULL,
  `panel_url`            VARCHAR(500)  DEFAULT NULL,
  `status`               ENUM('installing','active','suspended','terminated') NOT NULL DEFAULT 'installing',
  -- Métricas simuladas (actualizadas por cron o API Pterodactyl)
  `ram_used_percent`     TINYINT UNSIGNED DEFAULT 0,
  `cpu_used_percent`     TINYINT UNSIGNED DEFAULT 0,
  `disk_used_percent`    TINYINT UNSIGNED DEFAULT 0,
  `uptime_seconds`       BIGINT UNSIGNED  DEFAULT 0,
  -- Fechas
  `expires_at`           DATETIME      DEFAULT NULL,
  `created_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_servers_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_servers_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_servers_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE RESTRICT,
  INDEX `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: audit_log
-- Registro inmutable de acciones críticas del sistema
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    DEFAULT NULL COMMENT 'NULL = acción del sistema',
  `action`     VARCHAR(100)    NOT NULL COMMENT 'ej: order.approved, user.login, plan.created',
  `entity`     VARCHAR(50)     DEFAULT NULL COMMENT 'ej: orders, users, plans',
  `entity_id`  INT UNSIGNED    DEFAULT NULL,
  `old_value`  JSON            DEFAULT NULL,
  `new_value`  JSON            DEFAULT NULL,
  `ip_address` VARCHAR(45)     DEFAULT NULL,
  `user_agent` VARCHAR(500)    DEFAULT NULL,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_action` (`user_id`, `action`),
  INDEX `idx_entity` (`entity`, `entity_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: sessions
-- Sesiones PHP persistentes en BD (opcional, para escalar)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id`         VARCHAR(128)  NOT NULL,
  `user_id`    INT UNSIGNED  DEFAULT NULL,
  `ip_address` VARCHAR(45)   DEFAULT NULL,
  `user_agent` VARCHAR(500)  DEFAULT NULL,
  `payload`    BLOB          NOT NULL,
  `last_active` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_last_active` (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: password_resets
-- Tokens de recuperación de contraseña con expiración
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(128) NOT NULL UNIQUE,
  `expires_at` DATETIME     NOT NULL,
  `used`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: support_tickets
-- Sistema de tickets de soporte
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL,
  `subject`      VARCHAR(255) NOT NULL,
  `department`   ENUM('sales','billing','technical','abuse') NOT NULL DEFAULT 'technical',
  `priority`     ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status`       ENUM('open','answered','client_reply','closed') NOT NULL DEFAULT 'open',
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ticket_messages`;
CREATE TABLE `ticket_messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id`  INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `message`    TEXT         NOT NULL,
  `is_staff`   TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tm_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tm_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: announcements
-- Noticias y anuncios del sistema
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(255)  NOT NULL,
  `content`      LONGTEXT      NOT NULL,
  `excerpt`      TEXT          DEFAULT NULL,
  `author_id`    INT UNSIGNED  DEFAULT NULL,
  `is_featured`  TINYINT(1)    NOT NULL DEFAULT 0,
  `is_published` TINYINT(1)    NOT NULL DEFAULT 1,
  `published_at` DATETIME      DEFAULT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ann_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: service_status
-- Estado público de nodos e infraestructura
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `service_status`;
CREATE TABLE `service_status` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `type`        ENUM('node','api','network','panel') NOT NULL DEFAULT 'node',
  `location`    VARCHAR(100) DEFAULT NULL,
  `status`      ENUM('operational','degraded','maintenance','outage') NOT NULL DEFAULT 'operational',
  `uptime_pct`  DECIMAL(5,2) NOT NULL DEFAULT 99.99,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: exchange_rates
-- Tasas de cambio PEN/USD/EUR actualizadas periódicamente
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `exchange_rates`;
CREATE TABLE `exchange_rates` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `from_currency` VARCHAR(3)     NOT NULL,
  `to_currency`   VARCHAR(3)     NOT NULL,
  `rate`          DECIMAL(12,6)  NOT NULL,
  `source`        VARCHAR(50)    DEFAULT 'manual',
  `updated_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pair` (`from_currency`, `to_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Usuario administrador
-- Contraseña: Admin2024! (CAMBIAR EN PRODUCCIÓN)
-- Hash Argon2ID generado con password_hash('Admin2024!', PASSWORD_ARGON2ID)
INSERT INTO `users`
  (`username`, `email`, `password`, `full_name`, `role`, `status`, `email_verified`, `preferred_currency`)
VALUES
  ('admin', 'admin@nexuhosting.com',
   '$argon2id$v=19$m=65536,t=4,p=1$bmV4dWhvc3RpbmdTYWx0MQ$XkT2LzWfCqMHOzZRXHnPr0fMi9VPdLSfbrgj5YPnBuI',
   'Administrador Nexu', 'admin', 'active', 1, 'PEN');

-- Planes Minecraft
INSERT INTO `plans` (`name`, `slug`, `description`, `plan_type`, `ram_gb`, `cpu_cores`, `disk_gb`, `bandwidth_tb`, `player_slots`, `price_pen`, `features`, `is_featured`, `sort_order`) VALUES
('Starter', 'mc-starter', 'Perfecto para jugar con amigos. Instalación en segundos.', 'minecraft', 2.0, 2.0, 20, NULL, 10,
 35.00,
 '["2 GB RAM DDR4","2 vCPU AMD Ryzen","20 GB NVMe SSD","10 Slots de jugadores","Panel Pterodactyl","Backups diarios","Anti-DDoS incluido","Soporte 24/7"]',
 0, 1),
('Gamer', 'mc-gamer', 'El plan más popular para comunidades medianas con mods.', 'minecraft', 4.0, 4.0, 40, NULL, 25,
 69.00,
 '["4 GB RAM DDR4","4 vCPU AMD Ryzen","40 GB NVMe SSD","25 Slots de jugadores","Panel Pterodactyl","Backups cada 6 horas","Mods y plugins ilimitados","Soporte prioritario"]',
 1, 2),
('Pro', 'mc-pro', 'Para servidores grandes y modpacks pesados como ATM9.', 'minecraft', 8.0, 6.0, 80, NULL, 50,
 129.00,
 '["8 GB RAM DDR4","6 vCPU AMD Ryzen","80 GB NVMe SSD","50 Slots de jugadores","Panel Pterodactyl","Backups cada 3 horas","IP dedicada opcional","Soporte VIP"]',
 0, 3),
('Elite', 'mc-elite', 'Rendimiento máximo para comunidades masivas y redes de servidores.', 'minecraft', 16.0, 8.0, 160, NULL, 100,
 239.00,
 '["16 GB RAM DDR5","8 vCPU AMD Ryzen 9","160 GB NVMe SSD","100+ Slots","Panel Pterodactyl","Backups horarios","IP dedicada incluida","Soporte VIP 24/7","Acceso SSH"]',
 0, 4);

-- Planes Web Hosting
INSERT INTO `plans` (`name`, `slug`, `description`, `plan_type`, `ram_gb`, `cpu_cores`, `disk_gb`, `bandwidth_tb`, `player_slots`, `price_pen`, `features`, `is_featured`, `sort_order`) VALUES
('Web Básico', 'web-basico', 'Hosting web para proyectos personales y portafolios.', 'web', 1.0, 1.0, 10, 1.0, NULL,
 25.00,
 '["1 GB RAM","1 vCPU","10 GB NVMe SSD","1 TB Ancho de banda","1 dominio gratuito","SSL gratuito","cPanel / hPanel","Soporte 24/7"]',
 0, 1),
('Web Pro', 'web-pro', 'Para tiendas online y sitios con alto tráfico.', 'web', 2.0, 2.0, 25, 3.0, NULL,
 49.00,
 '["2 GB RAM","2 vCPU","25 GB NVMe SSD","3 TB Ancho de banda","3 dominios","SSL gratuito","WordPress optimizado","Backups diarios"]',
 1, 2);

-- Colaboradores de ejemplo
INSERT INTO `collaborators` (`name`, `role_title`, `description`, `discord_tag`, `discord_id`, `is_active`, `sort_order`) VALUES
('Carlos Mendoza', 'CEO & Fundador', 'Fundador de Nexu Hosting con más de 8 años de experiencia en infraestructura de servidores y hosting para videojuegos. Apasionado por el rendimiento y la calidad de servicio.', 'CarlosMendoza#0001', '123456789012345678', 1, 1),
('Ana García', 'Head of Support', 'Responsable del equipo de soporte técnico. Garantiza que cada cliente reciba ayuda en menos de 2 horas. Especialista en Pterodactyl y Linux.', 'AnaGarcia#0002', '234567890123456789', 1, 2),
('Diego Torres', 'Network Engineer', 'Ingeniero de redes encargado de la infraestructura de nodos, protección DDoS y optimización de latencia para Sudamérica.', 'DiegoTorres#0003', '345678901234567890', 1, 3),
('Lucía Ramírez', 'Desarrolladora Frontend', 'Diseñadora y desarrolladora de la plataforma web y panel de clientes. Apasionada por las interfaces modernas y la UX.', 'LuciaRamirez#0004', '456789012345678901', 1, 4);

-- Estado de servicios
INSERT INTO `service_status` (`name`, `type`, `location`, `status`, `uptime_pct`) VALUES
('Panel de Control (NCP)', 'panel', 'Global', 'operational', 99.99),
('Nodo MC - Sudamérica (Lima)', 'node', 'Lima, Perú', 'operational', 99.97),
('Nodo MC - Sudamérica (São Paulo)', 'node', 'São Paulo, Brasil', 'operational', 99.95),
('Nodo Web Hosting', 'node', 'Lima, Perú', 'operational', 99.98),
('Protección DDoS', 'network', 'Global', 'operational', 100.00),
('API Nexu Hosting', 'api', 'Global', 'operational', 99.99);

-- Tasas de cambio iniciales (actualizar periódicamente)
INSERT INTO `exchange_rates` (`from_currency`, `to_currency`, `rate`, `source`) VALUES
('PEN', 'USD', 0.265000, 'manual'),
('PEN', 'EUR', 0.245000, 'manual'),
('USD', 'PEN', 3.774000, 'manual'),
('EUR', 'PEN', 4.083000, 'manual'),
('USD', 'EUR', 0.925000, 'manual'),
('EUR', 'USD', 1.081000, 'manual');

-- Anuncio de bienvenida
INSERT INTO `announcements` (`title`, `content`, `excerpt`, `author_id`, `is_featured`, `is_published`, `published_at`) VALUES
('🚀 ¡Bienvenidos a Nexu Hosting v2.0!',
 '<p>Nos complace anunciar el lanzamiento oficial de <strong>Nexu Hosting v2.0</strong>, la plataforma de servidores de juegos y hosting web más avanzada del mercado peruano.</p><p>Con nuestra nueva plataforma puedes:</p><ul><li>Contratar servidores Minecraft y Web Hosting con pagos locales (Yape, Plin, Banco de la Nación)</li><li>Gestionar tus servidores desde el panel Pterodactyl integrado</li><li>Monitorear el rendimiento en tiempo real</li><li>Recibir soporte técnico 24/7 en español</li></ul>',
 'Lanzamiento oficial de la plataforma Nexu Hosting v2.0 con soporte para pagos peruanos y panel avanzado.',
 1, 1, 1, NOW());
