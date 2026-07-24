-- ============================================================
-- NEXU HOSTING - Sistema de Partners / Afiliados
-- ============================================================

-- Tabla principal de partners
CREATE TABLE `partners` (
  `id`                INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED      NOT NULL COMMENT 'FK al usuario dueño',
  `slug`              VARCHAR(60)       NOT NULL UNIQUE COMMENT 'Para URLs limpias: nexuhosting.com/p/tu-slug',
  `company_name`      VARCHAR(150)      DEFAULT NULL COMMENT 'Nombre empresa/marca',
  `display_name`      VARCHAR(100)      NOT NULL COMMENT 'Nombre público',
  `description`       TEXT              DEFAULT NULL COMMENT 'Bio pública',
  `logo_url`          VARCHAR(500)      DEFAULT NULL,
  `website_url`       VARCHAR(500)      DEFAULT NULL,
  `discord_url`       VARCHAR(500)      DEFAULT NULL,
  `twitter_url`       VARCHAR(500)      DEFAULT NULL,
  `youtube_url`       VARCHAR(500)      DEFAULT NULL,
  `twitch_url`        VARCHAR(500)      DEFAULT NULL,
  `tiktok_url`        VARCHAR(500)      DEFAULT NULL,
  -- Comisión y pagos
  `commission_rate`   DECIMAL(5,2)      NOT NULL DEFAULT 15.00 COMMENT '% por venta',
  `commission_type`   ENUM('recurring','one_time') NOT NULL DEFAULT 'recurring' COMMENT 'Recurrente o solo primera venta',
  `min_payout`        DECIMAL(10,2)     NOT NULL DEFAULT 50.00 COMMENT 'Mínimo para retirar',
  `balance`           DECIMAL(10,2)     NOT NULL DEFAULT 0.00 COMMENT 'Saldo pendiente',
  `total_earned`      DECIMAL(10,2)     NOT NULL DEFAULT 0.00 COMMENT 'Total histórico',
  `total_paid`        DECIMAL(10,2)     NOT NULL DEFAULT 0.00 COMMENT 'Total retirado',
  -- Estado
  `status`            ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
  `tier`              ENUM('bronze','silver','gold','platinum') NOT NULL DEFAULT 'bronze',
  `approved_at`       DATETIME          DEFAULT NULL,
  `approved_by`       INT UNSIGNED      DEFAULT NULL,
  `rejection_reason`  TEXT              DEFAULT NULL,
  -- Tracking
  `clicks`            INT UNSIGNED      NOT NULL DEFAULT 0,
  `signups`           INT UNSIGNED      NOT NULL DEFAULT 0,
  `conversions`       INT UNSIGNED      NOT NULL DEFAULT 0,
  `last_click_at`     DATETIME          DEFAULT NULL,
  `last_conversion_at` DATETIME         DEFAULT NULL,
  -- Configuración
  `custom_discount`   TINYINT UNSIGNED  DEFAULT NULL COMMENT '% descuento extra para sus referidos (opcional)',
  `landing_page`      VARCHAR(100)      DEFAULT NULL COMMENT 'Página personalizada opcional',
  `notify_email`      TINYINT(1)        NOT NULL DEFAULT 1 COMMENT 'Email en nuevas conversiones',
  `notify_discord`    TINYINT(1)        NOT NULL DEFAULT 0,
  `created_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_partners_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_partners_approvedby` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_status_tier` (`status`, `tier`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clicks de tracking (para analytics detallados)
CREATE TABLE `partner_clicks` (
  `id`            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `partner_id`    INT UNSIGNED      NOT NULL,
  `ip_address`    VARCHAR(45)       NOT NULL,
  `user_agent`    VARCHAR(500)      DEFAULT NULL,
  `referrer`      VARCHAR(500)      DEFAULT NULL,
  `landing_url`   VARCHAR(500)      DEFAULT NULL,
  `country`       VARCHAR(2)        DEFAULT NULL,
  `converted`     TINYINT(1)        NOT NULL DEFAULT 0,
  `converted_at`  DATETIME          DEFAULT NULL,
  `order_id`      INT UNSIGNED      DEFAULT NULL,
  `created_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pc_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  INDEX `idx_partner_created` (`partner_id`, `created_at`),
  INDEX `idx_ip_partner` (`ip_address`, `partner_id`),
  INDEX `idx_converted` (`converted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversiones/ventas atribuidas
CREATE TABLE `partner_conversions` (
  `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `partner_id`      INT UNSIGNED      NOT NULL,
  `click_id`        BIGINT UNSIGNED   DEFAULT NULL,
  `order_id`        INT UNSIGNED      NOT NULL,
  `user_id`         INT UNSIGNED      NOT NULL COMMENT 'Cliente referido',
  `amount_pen`      DECIMAL(10,2)     NOT NULL,
  `commission_pen`  DECIMAL(10,2)     NOT NULL,
  `commission_rate` DECIMAL(5,2)      NOT NULL COMMENT 'Rate usado en esta venta',
  `status`          ENUM('pending','approved','paid','cancelled') NOT NULL DEFAULT 'pending',
  `recurring`       TINYINT(1)        NOT NULL DEFAULT 1 COMMENT 'Si genera comisión recurrente',
  `billing_cycle`   INT UNSIGNED      DEFAULT 1 COMMENT 'Ciclo de facturación (1=primero, 2=segundo...)',
  `paid_at`         DATETIME          DEFAULT NULL,
  `created_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pconv_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pconv_click` FOREIGN KEY (`click_id`) REFERENCES `partner_clicks`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pconv_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pconv_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_partner_status` (`partner_id`, `status`),
  INDEX `idx_order` (`order_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Retiros / Pagos a partners
CREATE TABLE `partner_payouts` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `partner_id`      INT UNSIGNED      NOT NULL,
  `amount_pen`      DECIMAL(10,2)     NOT NULL,
  `method`          ENUM('yape','plin','banco_nacion','interbank','bcp','paypal','crypto','transfer') NOT NULL,
  `account_details` JSON              NOT NULL COMMENT 'Datos de cuenta: phone, email, cci, etc',
  `status`          ENUM('pending','processing','completed','rejected') NOT NULL DEFAULT 'pending',
  `txid`            VARCHAR(100)      DEFAULT NULL COMMENT 'ID de transacción externo',
  `notes`           TEXT              DEFAULT NULL,
  `requested_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at`    DATETIME          DEFAULT NULL,
  `processed_by`    INT UNSIGNED      DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pp_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pp_processedby` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_partner_status` (`partner_id`, `status`),
  INDEX `idx_requested` (`requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Materiales de marketing (banners, assets)
CREATE TABLE `partner_assets` (
  `id`          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)      NOT NULL,
  `type`        ENUM('banner','logo','badge','video','document') NOT NULL DEFAULT 'banner',
  `url`         VARCHAR(500)      NOT NULL,
  `width`       INT UNSIGNED      DEFAULT NULL,
  `height`      INT UNSIGNED      DEFAULT NULL,
  `file_size`   INT UNSIGNED      DEFAULT NULL,
  `mime_type`   VARCHAR(100)      DEFAULT NULL,
  `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
  `sort_order`  INT               NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type_active` (`type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración global del programa
CREATE TABLE `partner_settings` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `default_rate`          DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  `min_payout`            DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  `cookie_days`           INT UNSIGNED NOT NULL DEFAULT 30,
  `recurring_commissions` TINYINT(1)   NOT NULL DEFAULT 1,
  `max_recurring_months`  INT UNSIGNED NOT NULL DEFAULT 12,
  `auto_approve`          TINYINT(1)   NOT NULL DEFAULT 0,
  `require_kyc`           TINYINT(1)   NOT NULL DEFAULT 1,
  `allowed_countries`     JSON         DEFAULT NULL,
  `banner_text`           TEXT         DEFAULT NULL,
  `tos_url`               VARCHAR(500) DEFAULT NULL,
  `faq_url`               VARCHAR(500) DEFAULT NULL,
  `updated_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `partner_settings` (`id`) VALUES (1);

-- Assets de ejemplo
INSERT INTO `partner_assets` (`name`, `type`, `url`, `width`, `height`, `sort_order`) VALUES
('Banner 728x90 - Leaderboard', 'banner', '/assets/partners/banner-728x90.png', 728, 90, 1),
('Banner 300x250 - Medium Rectangle', 'banner', '/assets/partners/banner-300x250.png', 300, 250, 2),
('Banner 336x280 - Large Rectangle', 'banner', '/assets/partners/banner-336x280.png', 336, 280, 3),
('Banner 160x600 - Wide Skyscraper', 'banner', '/assets/partners/banner-160x600.png', 160, 600, 4),
('Banner 300x600 - Half Page', 'banner', '/assets/partners/banner-300x600.png', 300, 600, 5),
('Logo Nexu Hosting - Horizontal', 'logo', '/assets/partners/logo-horizontal.png', 400, 120, 10),
('Logo Nexu Hosting - Vertical', 'logo', '/assets/partners/logo-vertical.png', 200, 200, 11),
('Badge "Partner Oficial"', 'badge', '/assets/partners/badge-partner.png', 200, 80, 20),
('Guía de Branding PDF', 'document', '/assets/partners/branding-guide.pdf', NULL, NULL, 30);

-- Vista para estadísticas rápidas
CREATE OR REPLACE VIEW `v_partner_stats` AS
SELECT
  p.id,
  p.user_id,
  p.slug,
  p.display_name,
  p.status,
  p.tier,
  p.commission_rate,
  p.balance,
  p.total_earned,
  p.clicks,
  p.signups,
  p.conversions,
  CASE WHEN p.clicks > 0 THEN ROUND(p.conversions / p.clicks * 100, 2) ELSE 0 END AS conversion_rate,
  p.last_click_at,
  p.last_conversion_at,
  u.email,
  u.username
FROM partners p
JOIN users u ON p.user_id = u.id;

-- Trigger para actualizar stats del partner en conversión
DELIMITER //
CREATE TRIGGER `trg_partner_conversion_after_insert`
AFTER INSERT ON `partner_conversions`
FOR EACH ROW
BEGIN
  UPDATE `partners`
  SET
    `conversions` = `conversions` + 1,
    `balance` = `balance` + NEW.commission_pen,
    `total_earned` = `total_earned` + NEW.commission_pen,
    `last_conversion_at` = NEW.created_at
  WHERE `id` = NEW.partner_id;
END//
DELIMITER ;

-- Trigger para actualizar signups
DELIMITER //
CREATE TRIGGER `trg_partner_signup_after_insert`
AFTER INSERT ON `users`
FOR EACH ROW
BEGIN
  -- Si el usuario viene de un referral cookie, actualizar partner
  -- Esto se maneja en PHP, pero el trigger sirve como respaldo
END//
DELIMITER ;