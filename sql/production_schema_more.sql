-- Rewarity extended production tables (no CREATE DATABASE / USE)
-- Import into live DB u788248317_rewarity after running production_schema_min.sql

-- Geography masters
CREATE TABLE IF NOT EXISTS `country_master` (
  `Id` INT NOT NULL,
  `CountryName` VARCHAR(45) DEFAULT NULL,
  `IsActive` BINARY(1) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `state_master` (
  `Id` INT NOT NULL,
  `StateName` VARCHAR(45) DEFAULT NULL,
  `CountryId` INT DEFAULT NULL,
  `IsActive` BINARY(1) DEFAULT '1',
  PRIMARY KEY (`Id`),
  KEY `FKStateCountry_idx` (`CountryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `city_master` (
  `Id` INT NOT NULL,
  `CityName` VARCHAR(45) DEFAULT NULL,
  `StateId` INT DEFAULT NULL,
  `IsActive` BINARY(1) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKCityState_idx` (`StateId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pincode_master` (
  `Id` INT NOT NULL,
  `Pincode` VARCHAR(10) NOT NULL,
  `CityId` INT NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Pincode_UNIQUE` (`Pincode`),
  KEY `FKPincodeCity_idx` (`CityId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reference masters
CREATE TABLE IF NOT EXISTS `sales_type` (
  `Id` INT NOT NULL,
  `SalesType` VARCHAR(45) DEFAULT NULL,
  `Description` VARCHAR(100) DEFAULT NULL,
  `ExpectedDaysOfPayment` TINYINT DEFAULT NULL,
  `Amount` INT DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reward_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `Reward` VARCHAR(45) DEFAULT NULL COMMENT 'Cash | Product | Points',
  `Value` INT DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories & Colors used in admin UI
CREATE TABLE IF NOT EXISTS `category_master` (
  `Id` INT NOT NULL,
  `CategoryName` VARCHAR(80) NOT NULL,
  `Description` VARCHAR(255) NULL,
  `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
  `CreatedOn` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedOn` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `color_master` (
  `Id` INT NOT NULL,
  `ColorName` VARCHAR(60) NOT NULL,
  `HexCode` VARCHAR(7) NULL,
  `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
  `CreatedOn` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pricing and product-related tables
CREATE TABLE IF NOT EXISTS `product_sale_price` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `ProductId` INT DEFAULT NULL,
  `StartDate` DATETIME DEFAULT NULL,
  `EndDate` DATETIME DEFAULT NULL,
  `IsActive` BINARY(1) DEFAULT '1',
  `MRP` DECIMAL(6,2) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKSaleProduct_idx` (`ProductId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_purchase_price` (
  `Id` INT NOT NULL,
  `ProductId` INT NOT NULL,
  `Price` DECIMAL(12,2) NOT NULL,
  `SellerId` INT NOT NULL,
  `PurchaseDate` DATETIME DEFAULT NULL,
  `Qty` DECIMAL(12,2) NOT NULL,
  `Notes` TEXT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKPurchaseUser_idx` (`SellerId`),
  KEY `FKPurchaseProduct_idx` (`ProductId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_points_config` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `ProductId` INT DEFAULT NULL,
  `UserId` INT DEFAULT NULL,
  `CityId` INT DEFAULT NULL,
  `Points` INT DEFAULT NULL,
  `IsUserSpecific` BIT(1) DEFAULT b'0',
  `CreatedOn` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `FKPointsProduct_idx` (`ProductId`),
  KEY `FKPointsUserId_idx` (`UserId`),
  KEY `FKPointsCity_idx` (`CityId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_dealer_price` (
  `Id` INT NOT NULL,
  `ProductSalePriceId` INT DEFAULT NULL,
  `DealerId` INT DEFAULT NULL,
  `DistributorId` INT DEFAULT NULL,
  `Price` DECIMAL(6,2) DEFAULT NULL,
  `ProductId` INT DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKDealerPrice_idx` (`ProductSalePriceId`),
  KEY `FKDealerUser_idx` (`DealerId`),
  KEY `FKDistributorUser_idx` (`DistributorId`),
  KEY `FKDealerProduct_idx` (`ProductId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sales & payments
CREATE TABLE IF NOT EXISTS `sales_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `SalesTypeId` INT DEFAULT NULL,
  `Amount` DECIMAL(9,2) DEFAULT NULL,
  `BuyerId` INT DEFAULT NULL,
  `SalesDate` DATETIME DEFAULT NULL,
  `DistributorId` INT DEFAULT NULL,
  `SalesPersonId` INT DEFAULT NULL,
  `SalesStatus` VARCHAR(45) DEFAULT NULL COMMENT 'Received|Delivered|Cancelled',
  `DeliveredDate` DATETIME DEFAULT NULL,
  `SalesDetailImage` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKSalesType_idx` (`SalesTypeId`),
  KEY `FKSalesBuyer_idx` (`BuyerId`),
  KEY `FKSalesDistributor_idx` (`DistributorId`),
  KEY `FKSalesPerson_idx` (`SalesPersonId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sales_details` (
  `Id` BIGINT NOT NULL,
  `ProductId` INT DEFAULT NULL,
  `Qty` TINYINT DEFAULT NULL,
  `Price` DECIMAL(5,2) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKSalesProduct_idx` (`ProductId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payment_master` (
  `Id` BIGINT NOT NULL AUTO_INCREMENT,
  `BuyerId` INT DEFAULT NULL,
  `PaymentDate` DATETIME DEFAULT NULL,
  `PaymentType` VARCHAR(45) DEFAULT NULL COMMENT 'Cash, GST Bill',
  `Value` INT DEFAULT NULL COMMENT 'How much payment is done',
  `DistributorId` INT DEFAULT NULL,
  `SalesPersonId` INT DEFAULT NULL,
  `ExpectedDate` DATETIME DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKPaymentUser_idx` (`BuyerId`),
  KEY `FKPaymentDistributor_idx` (`DistributorId`),
  KEY `FKPaymentSalesPerson_idx` (`SalesPersonId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Points & transactions
CREATE TABLE IF NOT EXISTS `user_points` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `UserId` INT DEFAULT NULL,
  `Points` INT UNSIGNED NOT NULL,
  `LastUpdated` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `FKPointsUser_idx` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `points_transaction_details` (
  `Id` INT NOT NULL,
  `UserId` INT DEFAULT NULL,
  `SaleId` INT DEFAULT NULL,
  `ProductId` INT DEFAULT NULL,
  `Points` INT UNSIGNED DEFAULT NULL,
  `TransactionType` BIT(1) DEFAULT NULL COMMENT '1:Debit 2:Credit',
  `Remarks` VARCHAR(100) DEFAULT NULL,
  `RewardId` INT DEFAULT NULL,
  `UserPointId` INT DEFAULT NULL,
  `RedumptionType` VARCHAR(45) DEFAULT NULL COMMENT 'Cash|Product',
  `CreatedOn` DATETIME DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKTransSale_idx` (`SaleId`),
  KEY `FKTransProduct_idx` (`ProductId`),
  KEY `FKTransReward_idx` (`RewardId`),
  KEY `FKTransPoint_idx` (`UserPointId`),
  KEY `FKTransUser_idx` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dealer/Distributor/Salesman relations
CREATE TABLE IF NOT EXISTS `distributor_dealer_master` (
  `Id` INT NOT NULL,
  `DistributorId` INT DEFAULT NULL,
  `DealerId` INT DEFAULT NULL,
  `Date` DATETIME DEFAULT NULL,
  `IsActive` BINARY(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`Id`),
  KEY `FKDistributorUser_idx` (`DistributorId`),
  KEY `FKDealerUser_idx` (`DealerId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dealer_distributor_salesman_master` (
  `Id` INT NOT NULL,
  `DistributorId` INT DEFAULT NULL,
  `DealerId` INT DEFAULT NULL,
  `SalesmanId` INT DEFAULT NULL,
  `CreatedOn` DATETIME DEFAULT NULL,
  `IsActive` BIT(1) DEFAULT NULL,
  `IsApproved` BIT(1) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKMappingDistributor_idx` (`DistributorId`),
  KEY `FKMappingDealer_idx` (`DealerId`),
  KEY `FKMappingSalesman_idx` (`SalesmanId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Schemes
CREATE TABLE IF NOT EXISTS `scheme_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `SchemName` VARCHAR(45) DEFAULT NULL,
  `IsActive` BINARY(1) DEFAULT NULL,
  `StartDate` DATETIME DEFAULT NULL,
  `EndDate` DATETIME DEFAULT NULL,
  `Description` VARCHAR(100) DEFAULT NULL,
  `SchemeType` VARCHAR(45) DEFAULT NULL COMMENT 'Revenue, Payment, Product, Points',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `scheme_detail` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `SchemeId` INT NOT NULL,
  `BuyerId` INT DEFAULT NULL,
  `LocationId` INT DEFAULT NULL,
  `SchemProductId` INT DEFAULT NULL,
  `Value` INT DEFAULT NULL,
  `RewardId` INT DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKScheme_idx` (`SchemeId`),
  KEY `FKSchemeUser_idx` (`BuyerId`),
  KEY `FKSchemeLocation_idx` (`LocationId`),
  KEY `FKSchemeProduct_idx` (`SchemProductId`),
  KEY `FKSchemeReward_idx` (`RewardId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `scheme_master_history` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `SchemMasterId` INT DEFAULT NULL,
  `SchemName` VARCHAR(45) DEFAULT NULL,
  `StartDate` DATETIME DEFAULT NULL,
  `IsActive` BINARY(1) DEFAULT NULL,
  `EndDate` DATETIME DEFAULT NULL,
  `Description` VARCHAR(100) DEFAULT NULL,
  `SchemeType` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKHistoryScheme_idx` (`SchemMasterId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `scheme_messages` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `SchemId` INT DEFAULT NULL,
  `ScreenName` VARCHAR(45) DEFAULT NULL,
  `Message` VARCHAR(100) DEFAULT NULL,
  `URL` VARCHAR(100) DEFAULT NULL,
  `IsActive` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `scheme_steps` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `SchemId` INT NOT NULL,
  `ProductId` INT DEFAULT NULL,
  `ValueOrQty` INT DEFAULT NULL,
  `Description` VARCHAR(100) DEFAULT NULL,
  `RewardId` INT DEFAULT NULL,
  `StepSequenceOrder` TINYINT DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKStepsScheme_idx` (`SchemId`),
  KEY `FKSchemeProduct_idx` (`ProductId`),
  KEY `FKSchemeReward_idx` (`RewardId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `scheme_user_steps` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `StepId` INT DEFAULT NULL,
  `SchemId` INT DEFAULT NULL,
  `UserId` INT DEFAULT NULL,
  `Date` DATETIME DEFAULT NULL,
  `IsCompleted` BINARY(1) DEFAULT '0',
  PRIMARY KEY (`Id`),
  KEY `FKUserStepSchemeStep_idx` (`StepId`),
  KEY `FKUserStepScheme_idx` (`SchemId`),
  KEY `FKUserStepUser_idx` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `scheme_user_step_detail` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `SchemeUserStepId` INT NOT NULL,
  `Date` DATETIME DEFAULT NULL,
  `Value` DECIMAL(8,2) DEFAULT NULL,
  `ProductId` INT DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKStepDetailUserStep_idx` (`SchemeUserStepId`),
  KEY `FKStepDetailProduct_idx` (`ProductId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Visits
CREATE TABLE IF NOT EXISTS `salesman_visit_master` (
  `Id` INT NOT NULL,
  `SalesManId` INT DEFAULT NULL,
  `DealerDistributorId` INT DEFAULT NULL,
  `VisitingDate` DATETIME DEFAULT NULL,
  `Latitude` DECIMAL(10,8) DEFAULT NULL,
  `Longitude` DECIMAL(10,8) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKVisitSalesman_idx` (`SalesManId`),
  KEY `FKVisitDealer_idx` (`DealerDistributorId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

