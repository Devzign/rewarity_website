-- Improve product_purchase_price to support fractional values and notes
ALTER TABLE `product_purchase_price`
  MODIFY COLUMN `Price` DECIMAL(12,2) NOT NULL,
  MODIFY COLUMN `Qty` DECIMAL(12,2) NOT NULL,
  ADD COLUMN `Notes` TEXT NULL AFTER `Qty`;

