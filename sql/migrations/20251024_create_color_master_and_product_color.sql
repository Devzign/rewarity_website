-- Colors master for product variants
CREATE TABLE IF NOT EXISTS `color_master` (
  `Id` INT NOT NULL,
  `ColorName` VARCHAR(60) NOT NULL,
  `HexCode` VARCHAR(7) NULL,
  `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
  `CreatedOn` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Optional: store selected color on product
ALTER TABLE `product_master`
  ADD COLUMN `ColorId` INT NULL AFTER `CategoryId`;

