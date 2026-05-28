-- ============================================================
-- NFCI — Hostinger / phpMyAdmin import (fixed dump)
-- 1. Select database: u309740424_nfcidb
-- 2. Import this file
--
-- Fixes vs mysqldump export:
--   - Correct FK table order (users → pages → … → component_assets)
--   - No SET SQL_LOG_BIN / GTID / admin session vars
--   - No LOCK TABLES / UNLOCK TABLES
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. users (referenced by pages)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Test User', 'testuser@example.com', NULL, '$2y$12$2fXVikYP5LTM.QhIdyX54.Y.uSsPEmlW3VkIsJJiIuXvrd/7O.Qom', NULL, '2026-05-20 01:23:09', '2026-05-20 01:23:09'),
(2, 'Admin', 'admin@nfci.com', NULL, '$2y$12$5ZLmHJR2.xgqbZVSGJSjGOzIfI0v99oQep3LncqYhehr.wXoaFqCa', NULL, '2026-05-20 01:24:12', '2026-05-20 01:24:12');

-- ------------------------------------------------------------
-- 2. pages (referenced by page_components)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_number` int unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','draft','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_number` (`page_number`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_pages_status` (`status`),
  KEY `idx_pages_page_number` (`page_number`),
  KEY `fk_pages_created_by` (`created_by`),
  CONSTRAINT `fk_pages_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pages` (`id`, `page_number`, `title`, `slug`, `meta_description`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 23, 'Our Gallery and Contact', 'our-gallery-and-contact-23', NULL, 'active', 2, '2026-05-20 01:24:31', '2026-05-20 01:24:31', NULL);

-- ------------------------------------------------------------
-- 3. component_types (referenced by components)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `component_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Short code like CTA, IMGGAL, HERO',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Human-readable name',
  `description` text COLLATE utf8mb4_unicode_ci,
  `schema` json DEFAULT NULL COMMENT 'JSON schema defining accepted config shape',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `component_types` (`id`, `code`, `name`, `description`, `schema`, `created_at`, `updated_at`) VALUES
(1, 'CTA', 'Call to Action', 'Button or form-based call to action component', '{"type": "object", "properties": {"heading": {"type": "string"}, "button_url": {"type": "string"}, "subheading": {"type": "string"}, "button_text": {"type": "string"}, "form_fields": {"type": "array"}}}', '2026-05-20 06:52:11', '2026-05-20 06:52:11'),
(2, 'IMGGAL', 'Image Gallery', 'Grid gallery with dynamically increasing photo cards', '{"type": "object", "properties": {"gap": {"type": "string", "default": "16px"}, "columns": {"type": "integer", "default": 3}, "lightbox": {"type": "boolean", "default": true}}}', '2026-05-20 06:52:11', '2026-05-20 06:52:11'),
(3, 'HERO', 'Hero Section', 'Full-width hero banner with text overlay', '{"type": "object", "properties": {"cta_url": {"type": "string"}, "heading": {"type": "string"}, "cta_text": {"type": "string"}, "subheading": {"type": "string"}, "background_image": {"type": "string"}}}', '2026-05-20 06:52:11', '2026-05-20 06:52:11'),
(4, 'TXTBLK', 'Text Block', 'Rich text content block', '{"type": "object", "properties": {"content": {"type": "string"}, "alignment": {"enum": ["left", "center", "right"], "type": "string"}}}', '2026-05-20 06:52:11', '2026-05-20 06:52:11'),
(5, 'TESTIMONIAL', 'Testimonials', 'Customer testimonial carousel or grid', '{"type": "object", "properties": {"layout": {"enum": ["carousel", "grid"], "type": "string"}, "show_rating": {"type": "boolean", "default": true}}}', '2026-05-20 06:52:11', '2026-05-20 06:52:11'),
(6, 'FAQ', 'FAQ Section', 'Accordion-style frequently asked questions', '{"type": "object", "properties": {"items": {"type": "array", "items": {"type": "object", "properties": {"answer": {"type": "string"}, "question": {"type": "string"}}}}}}', '2026-05-20 06:52:11', '2026-05-20 06:52:11'),
(7, 'CONTACT', 'Contact Form', 'Contact form with configurable fields', '{"type": "object", "properties": {"fields": {"type": "array"}, "email_to": {"type": "string"}, "submit_text": {"type": "string", "default": "Send Message"}}}', '2026-05-20 06:52:11', '2026-05-20 06:52:11'),
(8, 'VIDEO', 'Video Embed', 'Embedded video player section', '{"type": "object", "properties": {"autoplay": {"type": "boolean", "default": false}, "controls": {"type": "boolean", "default": true}, "video_url": {"type": "string"}}}', '2026-05-20 06:52:11', '2026-05-20 06:52:11');

-- ------------------------------------------------------------
-- 4. components (referenced by page_components)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `component_type_id` bigint unsigned NOT NULL,
  `variant_number` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. Contact Us CTA, Newsletter CTA',
  `description` text COLLATE utf8mb4_unicode_ci,
  `default_config` json DEFAULT NULL COMMENT 'Default configuration for this variant',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_component_type_variant` (`component_type_id`, `variant_number`),
  CONSTRAINT `fk_components_type` FOREIGN KEY (`component_type_id`) REFERENCES `component_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `components` (`id`, `component_type_id`, `variant_number`, `name`, `description`, `default_config`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Contact Us CTA', 'Simple contact form', '{"heading": "Contact Us", "button_text": "Submit", "form_fields": ["name", "email", "message"]}', '2026-05-20 01:24:23', '2026-05-20 01:24:23'),
(2, 2, 3, 'Photo Gallery Grid', 'Dynamic grid of photo cards', '{"gap": "16px", "columns": 3, "lightbox": true}', '2026-05-20 01:24:23', '2026-05-20 01:24:23');

-- ------------------------------------------------------------
-- 5. page_components (referenced by component_assets)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `page_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint unsigned NOT NULL,
  `component_id` bigint unsigned NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `config_overrides` json DEFAULT NULL COMMENT 'Page-specific config overrides merged with defaults',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_page_components_page` (`page_id`),
  KEY `idx_page_components_sort` (`page_id`, `sort_order`),
  KEY `fk_page_components_component` (`component_id`),
  CONSTRAINT `fk_page_components_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_page_components_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `page_components` (`id`, `page_id`, `component_id`, `sort_order`, `config_overrides`, `is_visible`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, 1, '2026-05-20 01:24:31', '2026-05-20 01:24:31'),
(2, 1, 2, 2, NULL, 1, '2026-05-20 01:24:31', '2026-05-20 01:24:31');

-- ------------------------------------------------------------
-- 6. component_assets
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `component_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_component_id` bigint unsigned NOT NULL,
  `asset_type` enum('image','video','document','icon') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int unsigned DEFAULT NULL COMMENT 'Size in bytes',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_component_assets_pc` (`page_component_id`),
  KEY `idx_component_assets_sort` (`page_component_id`, `sort_order`),
  CONSTRAINT `fk_component_assets_pc` FOREIGN KEY (`page_component_id`) REFERENCES `page_components` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. Laravel: password_reset_tokens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. Laravel: sessions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
