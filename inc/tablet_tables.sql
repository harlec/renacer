-- Ejecutar en phpMyAdmin sobre la base de datos admin_renacer

CREATE TABLE IF NOT EXISTS `tablet_tabs` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `tab_key`      VARCHAR(50)  NOT NULL,
  `label`        VARCHAR(100) NOT NULL,
  `icon`         VARCHAR(20)  DEFAULT '',
  `color_accent` VARCHAR(30)  DEFAULT '#f5a623',
  `color_bg`     VARCHAR(80)  DEFAULT 'rgba(245,166,35,.12)',
  `by_weight`    TINYINT(1)   DEFAULT 0,
  `sort_order`   INT          DEFAULT 0,
  UNIQUE KEY `tab_key` (`tab_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tablet_groups` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `tab_id`     INT NOT NULL,
  `label`      VARCHAR(100) NOT NULL,
  `sort_order` INT DEFAULT 0,
  INDEX `idx_tab` (`tab_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tablet_group_categories` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `group_id`    INT NOT NULL,
  `category_id` INT NOT NULL,
  UNIQUE KEY `uc_gc` (`group_id`,`category_id`),
  INDEX `idx_grp` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tablet_group_products` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `group_id`     INT NOT NULL,
  `product_id`   INT NOT NULL,
  `all_variants` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `uc_gp` (`group_id`,`product_id`),
  INDEX `idx_grp` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tablet_group_product_variants` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `group_product_id` INT NOT NULL,
  `variant_type_id`  INT NOT NULL,
  UNIQUE KEY `uc_gpv` (`group_product_id`,`variant_type_id`),
  INDEX `idx_gp` (`group_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
