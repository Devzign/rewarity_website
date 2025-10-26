-- Add ImageUrl and CategoryId to product_master
ALTER TABLE `product_master`
  ADD COLUMN `ImageUrl` VARCHAR(255) NULL AFTER `Description`,
  ADD COLUMN `CategoryId` INT NULL AFTER `ImageUrl`;

-- Optional FK if category_master exists
-- ALTER TABLE `product_master`
--   ADD CONSTRAINT `FKProductCategory` FOREIGN KEY (`CategoryId`) REFERENCES `category_master`(`Id`);
