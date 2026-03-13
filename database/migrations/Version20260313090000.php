<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the current Bolakaz baseline schema.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            ! $this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        $this->addSql(<<<'SQL'
CREATE TABLE `ads` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `text_align` VARCHAR(20) NOT NULL,
  `image_path` VARCHAR(250) NOT NULL,
  `discount` VARCHAR(20) NOT NULL,
  `collection` VARCHAR(250) NOT NULL,
  `link` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `attributes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` VARCHAR(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` ENUM('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_attributes_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `banner` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(250) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `caption_heading` VARCHAR(255) DEFAULT NULL,
  `caption_text` VARCHAR(255) DEFAULT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `cart` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` BIGINT UNSIGNED DEFAULT NULL,
  `quantity` INT NOT NULL,
  `color` VARCHAR(120) NOT NULL,
  `size` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cart_variant_id` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `category` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `cat_image` VARCHAR(250) DEFAULT NULL,
  `cat_slug` VARCHAR(150) NOT NULL,
  `is_parent` TINYINT(1) NOT NULL DEFAULT '1',
  `parent_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `coupons` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` ENUM('fixed','percent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `value` DECIMAL(20,2) NOT NULL,
  `status` ENUM('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  `expire_date` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `influencer_id` INT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `details` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sales_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` BIGINT UNSIGNED DEFAULT NULL,
  `quantity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_details_variant_id` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `gallery` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1=Active | 0=Inactive',
  `product_id` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `gallery_images` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `gallery_id` INT NOT NULL,
  `file_name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  `uploaded_on` DATETIME NOT NULL,
  `product_id` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `item_rating` (
  `ratingId` INT NOT NULL AUTO_INCREMENT,
  `itemId` INT NOT NULL,
  `userId` INT NOT NULL,
  `ratingNumber` INT NOT NULL,
  `title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  `comments` TEXT COLLATE utf8_unicode_ci NOT NULL,
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = Block, 0 = Unblock',
  PRIMARY KEY (`ratingId`),
  UNIQUE KEY `uniq_item_user` (`itemId`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `newsletter` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `category_name` VARCHAR(30) NOT NULL,
  `subcategory_id` BIGINT UNSIGNED DEFAULT NULL,
  `name` TEXT NOT NULL,
  `description` TEXT NOT NULL,
  `additional_info` TEXT DEFAULT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `price` DOUBLE PRECISION NOT NULL,
  `color` VARCHAR(200) DEFAULT NULL,
  `size` VARCHAR(250) DEFAULT 'M',
  `brand` VARCHAR(200) DEFAULT NULL,
  `material` VARCHAR(200) NOT NULL,
  `qty` INT NOT NULL,
  `photo` VARCHAR(200) NOT NULL,
  `date_view` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `counter` INT DEFAULT NULL,
  `product_status` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `products_v2` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `subcategory_id` BIGINT UNSIGNED DEFAULT NULL,
  `slug` VARCHAR(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` LONGTEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` VARCHAR(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` ENUM('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `base_price` DECIMAL(12,2) DEFAULT NULL,
  `main_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specs_json` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `needs_variant_stock_review` TINYINT(1) NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_products_v2_slug` (`slug`),
  KEY `idx_products_v2_status` (`status`),
  KEY `idx_products_v2_category` (`category_id`),
  KEY `idx_products_v2_subcategory` (`subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `sales` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `is_offline` TINYINT(1) NOT NULL DEFAULT '0',
  `tx_ref` VARCHAR(50) NOT NULL,
  `txid` BIGINT DEFAULT NULL,
  `Status` VARCHAR(10) NOT NULL,
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'paid',
  `customer_name` VARCHAR(150) DEFAULT NULL,
  `statement_share_token` CHAR(64) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(250) DEFAULT NULL,
  `coupon_id` INT DEFAULT NULL,
  `shipping_id` INT DEFAULT NULL,
  `address_1` TEXT DEFAULT NULL,
  `address_2` TEXT DEFAULT NULL,
  `sales_date` DATE NOT NULL,
  `due_date` DATE DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_statement_share_token` (`statement_share_token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `shippings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` DECIMAL(8,2) NOT NULL,
  `status` ENUM('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(200) NOT NULL,
  `password` VARCHAR(60) NOT NULL,
  `type` INT NOT NULL,
  `firstname` VARCHAR(50) NOT NULL,
  `lastname` VARCHAR(50) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(100) NOT NULL,
  `gender` VARCHAR(10) NOT NULL,
  `dob` TEXT NOT NULL,
  `photo` VARCHAR(200) NOT NULL,
  `status` INT NOT NULL,
  `activate_code` VARCHAR(255) DEFAULT NULL,
  `reset_code` VARCHAR(255) DEFAULT NULL,
  `created_on` DATE NOT NULL,
  `referral` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `web_details` (
  `site_name` VARCHAR(200) DEFAULT NULL,
  `site_address` VARCHAR(500) DEFAULT NULL,
  `site_email` VARCHAR(300) DEFAULT NULL,
  `site_number` VARCHAR(150) DEFAULT NULL,
  `id` INT NOT NULL AUTO_INCREMENT,
  `short_description` TEXT DEFAULT NULL,
  `description` LONGTEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `attribute_values` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attribute_id` BIGINT UNSIGNED NOT NULL,
  `value` VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_value` VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_attribute_value` (`attribute_id`,`normalized_value`),
  KEY `idx_attribute_values_attr` (`attribute_id`),
  CONSTRAINT `fk_attribute_values_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `offline_payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sales_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_date` DATE NOT NULL,
  `note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sales_id` (`sales_id`),
  CONSTRAINT `offline_payments_ibfk_1` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `product_variants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `sku` VARCHAR(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `stock_qty` INT NOT NULL DEFAULT '0',
  `image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` ENUM('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `option_signature` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_product_variants_sku` (`sku`),
  UNIQUE KEY `uniq_product_variant_signature` (`product_id`,`option_signature`),
  KEY `idx_product_variants_product` (`product_id`),
  KEY `idx_product_variants_status` (`status`),
  CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products_v2` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `product_legacy_map` (
  `legacy_product_id` INT NOT NULL,
  `product_v2_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`legacy_product_id`),
  UNIQUE KEY `uniq_product_legacy_map_v2` (`product_v2_id`),
  CONSTRAINT `fk_legacy_map_v2` FOREIGN KEY (`product_v2_id`) REFERENCES `products_v2` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE `variant_option_values` (
  `variant_id` BIGINT UNSIGNED NOT NULL,
  `attribute_id` BIGINT UNSIGNED NOT NULL,
  `attribute_value_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`variant_id`,`attribute_id`),
  KEY `idx_vov_value` (`attribute_value_id`),
  KEY `fk_vov_attribute` (`attribute_id`),
  CONSTRAINT `fk_vov_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vov_attribute_value` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vov_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            ! $this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('DROP TABLE `variant_option_values`');
        $this->addSql('DROP TABLE `product_legacy_map`');
        $this->addSql('DROP TABLE `product_variants`');
        $this->addSql('DROP TABLE `offline_payments`');
        $this->addSql('DROP TABLE `attribute_values`');
        $this->addSql('DROP TABLE `attributes`');
        $this->addSql('DROP TABLE `web_details`');
        $this->addSql('DROP TABLE `users`');
        $this->addSql('DROP TABLE `shippings`');
        $this->addSql('DROP TABLE `sales`');
        $this->addSql('DROP TABLE `products_v2`');
        $this->addSql('DROP TABLE `products`');
        $this->addSql('DROP TABLE `newsletter`');
        $this->addSql('DROP TABLE `item_rating`');
        $this->addSql('DROP TABLE `gallery_images`');
        $this->addSql('DROP TABLE `gallery`');
        $this->addSql('DROP TABLE `details`');
        $this->addSql('DROP TABLE `coupons`');
        $this->addSql('DROP TABLE `category`');
        $this->addSql('DROP TABLE `cart`');
        $this->addSql('DROP TABLE `banner`');
        $this->addSql('DROP TABLE `ads`');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
