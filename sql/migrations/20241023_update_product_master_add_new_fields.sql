-- Update product_master to support purchase/selling price, low stock alert, and description
ALTER TABLE `product_master`
  CHANGE COLUMN `UnitPrice` `SellingPrice` DECIMAL(12,2) NOT NULL DEFAULT 0,
  ADD COLUMN `Description` TEXT NULL AFTER `StartDate`,
  ADD COLUMN `PurchasePrice` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `IsActive`,
  ADD COLUMN `LowStockAlertLevel` DECIMAL(12,2) NULL DEFAULT NULL AFTER `CurrentStock`;
