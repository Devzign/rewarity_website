-- Adds optional coordinates to address_master for map-based location
ALTER TABLE `address_master`
  ADD COLUMN `Latitude` DECIMAL(10,8) NULL AFTER `CountryId`,
  ADD COLUMN `Longitude` DECIMAL(11,8) NULL AFTER `Latitude`;

