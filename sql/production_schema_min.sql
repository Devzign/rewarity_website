-- Rewarity minimal production schema (no CREATE DATABASE / USE)
-- Import this into your live DB: u788248317_rewarity
-- Safe to run multiple times; uses IF NOT EXISTS and idempotent seeds.

-- Core auth table for Admin login
CREATE TABLE IF NOT EXISTS `admin_users` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `Email` VARCHAR(150) NOT NULL,
  `PasswordHash` VARCHAR(255) NOT NULL,
  `DisplayName` VARCHAR(150) NOT NULL,
  `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
  `CreatedOn` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UX_AdminUsers_Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed super admin (password: Admin@123)
INSERT INTO `admin_users` (`Email`, `PasswordHash`, `DisplayName`)
VALUES ('admin@rewarity.com', '$2y$12$Z9s7k3sflvmzKMiqg/JNZOicFfo18ThyIoZVGNmkPDOUSAG.TxqzG', 'Super Admin')
ON DUPLICATE KEY UPDATE
  `PasswordHash` = VALUES(`PasswordHash`),
  `DisplayName` = VALUES(`DisplayName`),
  `IsActive` = 1;

-- Basic user types used in admin UI
CREATE TABLE IF NOT EXISTS `user_type` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `typename` VARCHAR(45) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UX_UserType_Typename` (`typename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `user_type` (`typename`, `description`) VALUES
  ('DEALER','Dealer role'),
  ('DISTRIBUTOR','Distributor role'),
  ('SALESPERSON','Salesperson role'),
  ('EMPLOYEE','Employee role')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- Minimal address table (columns probed by admin/users.php)
CREATE TABLE IF NOT EXISTS `address_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `Address1` VARCHAR(75) DEFAULT NULL,
  `Address2` VARCHAR(75) DEFAULT NULL,
  `CityId` INT DEFAULT NULL,
  `StateId` INT DEFAULT NULL,
  `CountryId` INT DEFAULT NULL,
  `Latitude` DECIMAL(10,7) DEFAULT NULL,
  `Longitude` DECIMAL(10,7) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Core user table used throughout admin pages
CREATE TABLE IF NOT EXISTS `user_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `UserName` VARCHAR(100) NOT NULL,
  `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
  `Email` VARCHAR(100) DEFAULT NULL,
  `PasswordHash` VARCHAR(255) DEFAULT NULL,
  `UserTypeId` INT DEFAULT NULL,
  `AddressId` INT DEFAULT NULL,
  `EmployeeId` INT DEFAULT NULL,
  `CreatedOn` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `UpdatedOn` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `IX_UserMaster_UserTypeId` (`UserTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: primary mobile number per user
CREATE TABLE IF NOT EXISTS `mobile_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `MobileNumber` VARCHAR(45) DEFAULT NULL,
  `IsPrimary` TINYINT(1) NOT NULL DEFAULT 0,
  `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
  `UserId` INT NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_Mobile_UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Minimal product and orders tables to support admin stats
CREATE TABLE IF NOT EXISTS `product_master` (
  `Id` INT NOT NULL,
  `ProductName` VARCHAR(100) NOT NULL,
  `ProductCode` VARCHAR(45) NOT NULL,
  `Description` TEXT NULL,
  `IsActive` TINYINT(1) DEFAULT 1,
  `PurchasePrice` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `SellingPrice` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `CurrentStock` DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `OrderNumber` VARCHAR(50) NOT NULL,
  `DealerId` INT NOT NULL,
  `DistributorId` INT NOT NULL,
  `SalesPersonId` INT NOT NULL,
  `CreatedByUserId` INT NOT NULL,
  `TotalAmount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `Notes` VARCHAR(255) DEFAULT NULL,
  `AttachmentPath` VARCHAR(255) DEFAULT NULL,
  `OrderDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` ENUM('PENDING','APPROVED','REJECTED','FULFILLED') DEFAULT 'PENDING',
  `CreatedOn` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `UpdatedOn` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UX_OrderMaster_OrderNumber` (`OrderNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `OrderId` INT NOT NULL,
  `ProductId` INT NOT NULL,
  `Quantity` DECIMAL(12,2) NOT NULL,
  `UnitPrice` DECIMAL(12,2) NOT NULL,
  `TotalAmount` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `IX_OrderItems_OrderId` (`OrderId`),
  KEY `IX_OrderItems_ProductId` (`ProductId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional notifications table referenced by UI
CREATE TABLE IF NOT EXISTS `notification_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `UserId` INT DEFAULT NULL,
  `UserTypeId` INT DEFAULT NULL,
  `Title` VARCHAR(100) NOT NULL,
  `Message` TEXT NOT NULL,
  `Screen` VARCHAR(50) DEFAULT NULL,
  `URL` VARCHAR(255) DEFAULT NULL,
  `IsRead` TINYINT(1) DEFAULT 0,
  `IsActive` TINYINT(1) DEFAULT 1,
  `CreatedOn` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

