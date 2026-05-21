-- ============================================================
-- NFCI Dynamic Page-Component System — MySQL Schema
-- Execute these queries in order on your MySQL server.
-- ============================================================

-- 1. Create the database
CREATE DATABASE IF NOT EXISTS `nfci_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `nfci_db`;

-- ============================================================
-- 2. Users table (for JWT auth)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name`              VARCHAR(255)    NOT NULL,
  `email`             VARCHAR(255)    NOT NULL UNIQUE,
  `email_verified_at` TIMESTAMP       NULL DEFAULT NULL,
  `password`          VARCHAR(255)    NOT NULL,
  `remember_token`    VARCHAR(100)    NULL DEFAULT NULL,
  `created_at`        TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP       NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Pages — each identified by a unique page_number
-- ============================================================
CREATE TABLE IF NOT EXISTS `pages` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_number`      INT UNSIGNED    NOT NULL UNIQUE,
  `title`            VARCHAR(255)    NOT NULL,
  `slug`             VARCHAR(255)    NOT NULL UNIQUE,
  `meta_description` TEXT            NULL DEFAULT NULL,
  `status`           ENUM('active','draft','archived') NOT NULL DEFAULT 'draft',
  `created_by`       BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at`       TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP       NULL DEFAULT NULL,
  `deleted_at`       TIMESTAMP       NULL DEFAULT NULL,

  INDEX `idx_pages_status` (`status`),
  INDEX `idx_pages_page_number` (`page_number`),
  CONSTRAINT `fk_pages_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Component Types — registry of all component categories
--    e.g. CTA, IMGGAL, HERO, TESTIMONIAL, FAQ, etc.
-- ============================================================
CREATE TABLE IF NOT EXISTS `component_types` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code`        VARCHAR(50)     NOT NULL UNIQUE COMMENT 'Short code like CTA, IMGGAL, HERO',
  `name`        VARCHAR(255)    NOT NULL        COMMENT 'Human-readable name',
  `description` TEXT            NULL DEFAULT NULL,
  `schema`      JSON            NULL DEFAULT NULL COMMENT 'JSON schema defining accepted config shape',
  `created_at`  TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP       NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. Components — specific variants of a component type
--    e.g. CTA 1, CTA 2, IMGGAL 3
-- ============================================================
CREATE TABLE IF NOT EXISTS `components` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `component_type_id` BIGINT UNSIGNED NOT NULL,
  `variant_number`    INT UNSIGNED    NOT NULL,
  `name`              VARCHAR(255)    NOT NULL COMMENT 'e.g. Contact Us CTA, Newsletter CTA',
  `description`       TEXT            NULL DEFAULT NULL,
  `default_config`    JSON            NULL DEFAULT NULL COMMENT 'Default configuration for this variant',
  `created_at`        TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP       NULL DEFAULT NULL,

  UNIQUE KEY `uq_component_type_variant` (`component_type_id`, `variant_number`),
  CONSTRAINT `fk_components_type`
    FOREIGN KEY (`component_type_id`) REFERENCES `component_types`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. Page Components — junction table linking pages to components
--    with ordering and per-page config overrides
-- ============================================================
CREATE TABLE IF NOT EXISTS `page_components` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_id`          BIGINT UNSIGNED NOT NULL,
  `component_id`     BIGINT UNSIGNED NOT NULL,
  `sort_order`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `config_overrides` JSON            NULL DEFAULT NULL COMMENT 'Page-specific config overrides merged with defaults',
  `is_visible`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`       TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP       NULL DEFAULT NULL,

  INDEX `idx_page_components_page` (`page_id`),
  INDEX `idx_page_components_sort` (`page_id`, `sort_order`),
  CONSTRAINT `fk_page_components_page`
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_page_components_component`
    FOREIGN KEY (`component_id`) REFERENCES `components`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. Component Assets — images/files attached to a page component
--    (e.g. gallery images for IMGGAL, hero banners, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS `component_assets` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_component_id`  BIGINT UNSIGNED NOT NULL,
  `asset_type`         ENUM('image','video','document','icon') NOT NULL DEFAULT 'image',
  `file_path`          VARCHAR(500)    NOT NULL,
  `original_name`      VARCHAR(255)    NULL DEFAULT NULL,
  `alt_text`           VARCHAR(255)    NULL DEFAULT NULL,
  `mime_type`          VARCHAR(100)    NULL DEFAULT NULL,
  `file_size`          INT UNSIGNED    NULL DEFAULT NULL COMMENT 'Size in bytes',
  `sort_order`         INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at`         TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`         TIMESTAMP       NULL DEFAULT NULL,

  INDEX `idx_component_assets_pc` (`page_component_id`),
  INDEX `idx_component_assets_sort` (`page_component_id`, `sort_order`),
  CONSTRAINT `fk_component_assets_pc`
    FOREIGN KEY (`page_component_id`) REFERENCES `page_components`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. Seed default component types
-- ============================================================
INSERT INTO `component_types` (`code`, `name`, `description`, `schema`, `created_at`, `updated_at`) VALUES
('CTA',         'Call to Action',    'Button or form-based call to action component',
  '{"type":"object","properties":{"heading":{"type":"string"},"subheading":{"type":"string"},"button_text":{"type":"string"},"button_url":{"type":"string"},"form_fields":{"type":"array"}}}',
  NOW(), NOW()),
('IMGGAL',      'Image Gallery',     'Grid gallery with dynamically increasing photo cards',
  '{"type":"object","properties":{"columns":{"type":"integer","default":3},"gap":{"type":"string","default":"16px"},"lightbox":{"type":"boolean","default":true}}}',
  NOW(), NOW()),
('HERO',        'Hero Section',      'Full-width hero banner with text overlay',
  '{"type":"object","properties":{"heading":{"type":"string"},"subheading":{"type":"string"},"background_image":{"type":"string"},"cta_text":{"type":"string"},"cta_url":{"type":"string"}}}',
  NOW(), NOW()),
('TXTBLK',      'Text Block',        'Rich text content block',
  '{"type":"object","properties":{"content":{"type":"string"},"alignment":{"type":"string","enum":["left","center","right"]}}}',
  NOW(), NOW()),
('TESTIMONIAL', 'Testimonials',      'Customer testimonial carousel or grid',
  '{"type":"object","properties":{"layout":{"type":"string","enum":["carousel","grid"]},"show_rating":{"type":"boolean","default":true}}}',
  NOW(), NOW()),
('FAQ',         'FAQ Section',       'Accordion-style frequently asked questions',
  '{"type":"object","properties":{"items":{"type":"array","items":{"type":"object","properties":{"question":{"type":"string"},"answer":{"type":"string"}}}}}}',
  NOW(), NOW()),
('CONTACT',     'Contact Form',      'Contact form with configurable fields',
  '{"type":"object","properties":{"fields":{"type":"array"},"submit_text":{"type":"string","default":"Send Message"},"email_to":{"type":"string"}}}',
  NOW(), NOW()),
('VIDEO',       'Video Embed',       'Embedded video player section',
  '{"type":"object","properties":{"video_url":{"type":"string"},"autoplay":{"type":"boolean","default":false},"controls":{"type":"boolean","default":true}}}',
  NOW(), NOW());

-- ============================================================
-- 9. Password reset tokens (Laravel default)
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email`      VARCHAR(255) NOT NULL PRIMARY KEY,
  `token`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. Sessions table (Laravel default)
-- ============================================================
CREATE TABLE IF NOT EXISTS `sessions` (
  `id`            VARCHAR(255) NOT NULL PRIMARY KEY,
  `user_id`       BIGINT UNSIGNED NULL DEFAULT NULL,
  `ip_address`    VARCHAR(45) NULL DEFAULT NULL,
  `user_agent`    TEXT NULL DEFAULT NULL,
  `payload`       LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  INDEX `idx_sessions_user` (`user_id`),
  INDEX `idx_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Done! Your NFCI database is ready.
-- ============================================================
