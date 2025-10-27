-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: rewards
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `address_master`
--

DROP TABLE IF EXISTS `address_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_master` (
  `Id` int NOT NULL,
  `Address1` varchar(75) DEFAULT NULL,
  `Address2` varchar(75) DEFAULT NULL,
  `CityId` int DEFAULT NULL,
  `StateId` int DEFAULT NULL,
  `CountryId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKAddressCity_idx` (`CityId`),
  KEY `FKAddressState_idx` (`StateId`),
  KEY `FKAddressCountry_idx` (`CountryId`),
  CONSTRAINT `FKAddressCity` FOREIGN KEY (`CityId`) REFERENCES `city_master` (`Id`),
  CONSTRAINT `FKAddressCountry` FOREIGN KEY (`CountryId`) REFERENCES `country_master` (`Id`),
  CONSTRAINT `FKAddressState` FOREIGN KEY (`StateId`) REFERENCES `state_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address_master`
--

LOCK TABLES `address_master` WRITE;
/*!40000 ALTER TABLE `address_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `address_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `city_master`
--

DROP TABLE IF EXISTS `city_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `city_master` (
  `Id` int NOT NULL,
  `CityName` varchar(45) DEFAULT NULL,
  `StateId` int DEFAULT NULL,
  `IsActive` binary(1) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKCityState_idx` (`StateId`),
  CONSTRAINT `FKCityState` FOREIGN KEY (`StateId`) REFERENCES `state_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `city_master`
--

LOCK TABLES `city_master` WRITE;
/*!40000 ALTER TABLE `city_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `city_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `country_master`
--

DROP TABLE IF EXISTS `country_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `country_master` (
  `Id` int NOT NULL,
  `CountryName` varchar(45) DEFAULT NULL,
  `IsActive` binary(1) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `country_master`
--

LOCK TABLES `country_master` WRITE;
/*!40000 ALTER TABLE `country_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `country_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dealer_distributor_salesman_master`
--

DROP TABLE IF EXISTS `dealer_distributor_salesman_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dealer_distributor_salesman_master` (
  `Id` int NOT NULL,
  `DistributorId` int DEFAULT NULL,
  `DealerId` int DEFAULT NULL,
  `SalesmanId` int DEFAULT NULL,
  `CreatedOn` datetime DEFAULT NULL,
  `IsActive` bit(1) DEFAULT NULL,
  `IsApproved` bit(1) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKMappingDistributor_idx` (`DistributorId`),
  KEY `FKMappingDealer_idx` (`DealerId`),
  KEY `FKMappingSalesman_idx` (`SalesmanId`),
  CONSTRAINT `FKMappingDealer` FOREIGN KEY (`DealerId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKMappingDistributor` FOREIGN KEY (`DistributorId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKMappingSalesman` FOREIGN KEY (`SalesmanId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dealer_distributor_salesman_master`
--

LOCK TABLES `dealer_distributor_salesman_master` WRITE;
/*!40000 ALTER TABLE `dealer_distributor_salesman_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `dealer_distributor_salesman_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `distributor_dealer_master`
--

DROP TABLE IF EXISTS `distributor_dealer_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `distributor_dealer_master` (
  `Id` int NOT NULL,
  `DistributorId` int DEFAULT NULL,
  `DealerId` int DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `IsActive` binary(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`Id`),
  KEY `FKDistributorUser_idx` (`DistributorId`),
  KEY `FKDealerUser_idx` (`DealerId`),
  CONSTRAINT `FKDealerUser` FOREIGN KEY (`DealerId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKDistributorUser` FOREIGN KEY (`DistributorId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `distributor_dealer_master`
--

LOCK TABLES `distributor_dealer_master` WRITE;
/*!40000 ALTER TABLE `distributor_dealer_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `distributor_dealer_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_master`
--

DROP TABLE IF EXISTS `expense_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_master` (
  `Id` bigint NOT NULL AUTO_INCREMENT,
  `ExpenseType` varchar(45) DEFAULT NULL COMMENT 'ProductPurchase, Travel, Salary,  Gift etc',
  `Amount` decimal(9,2) DEFAULT NULL,
  `ExpenseDate` datetime DEFAULT NULL,
  `ExpenseByUserId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKExpenseUser_idx` (`ExpenseByUserId`),
  CONSTRAINT `FKExpenseUser` FOREIGN KEY (`ExpenseByUserId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_master`
--

LOCK TABLES `expense_master` WRITE;
/*!40000 ALTER TABLE `expense_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `expense_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mobile_master`
--

DROP TABLE IF EXISTS `mobile_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_master` (
  `Id` int NOT NULL,
  `MobileNumber` varchar(45) DEFAULT NULL,
  `IsPrimary` binary(1) NOT NULL DEFAULT '0',
  `IsActive` binary(1) NOT NULL DEFAULT '1',
  `UserId` int NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKMobileUser_idx` (`UserId`),
  CONSTRAINT `FKMobileUser` FOREIGN KEY (`UserId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mobile_master`
--

LOCK TABLES `mobile_master` WRITE;
/*!40000 ALTER TABLE `mobile_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `mobile_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_master`
--

DROP TABLE IF EXISTS `payment_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_master` (
  `Id` bigint NOT NULL AUTO_INCREMENT,
  `BuyerId` int DEFAULT NULL,
  `PaymentDate` datetime DEFAULT NULL,
  `PaymentType` varchar(45) DEFAULT NULL COMMENT 'Cash, GST Bill',
  `Value` int DEFAULT NULL COMMENT 'How much payment is done',
  `DistributorId` int DEFAULT NULL,
  `SalesPersonId` int DEFAULT NULL,
  `ExpectedDate` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKPaymentUser_idx` (`BuyerId`),
  KEY `FKPaymentDistributor_idx` (`DistributorId`),
  KEY `FKPaymentSalesPerson_idx` (`SalesPersonId`),
  CONSTRAINT `FKPaymentDistributor` FOREIGN KEY (`DistributorId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKPaymentSalesPerson` FOREIGN KEY (`SalesPersonId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKPaymentUser` FOREIGN KEY (`BuyerId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_master`
--

LOCK TABLES `payment_master` WRITE;
/*!40000 ALTER TABLE `payment_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pincode_master`
--

DROP TABLE IF EXISTS `pincode_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pincode_master` (
  `Id` int NOT NULL,
  `Pincode` varchar(10) NOT NULL,
  `CityId` int NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Pincode_UNIQUE` (`Pincode`),
  KEY `FKPincodeCity_idx` (`CityId`),
  CONSTRAINT `FKPincodeCity` FOREIGN KEY (`CityId`) REFERENCES `city_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pincode_master`
--

LOCK TABLES `pincode_master` WRITE;
/*!40000 ALTER TABLE `pincode_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `pincode_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_transaction_details`
--

DROP TABLE IF EXISTS `points_transaction_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `points_transaction_details` (
  `Id` int NOT NULL,
  `UserId` int DEFAULT NULL,
  `SaleId` int DEFAULT NULL,
  `ProductId` int DEFAULT NULL,
  `Points` int unsigned DEFAULT NULL,
  `TransactionType` bit(1) DEFAULT NULL COMMENT '1:Debit\n2:Credit',
  `Remarks` varchar(100) DEFAULT NULL,
  `RewardId` int DEFAULT NULL,
  `UserPointId` int DEFAULT NULL,
  `RedumptionType` varchar(45) DEFAULT NULL COMMENT 'Cash(Points equal to cash)|Product(RewardId)',
  `CreatedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKTransSale_idx` (`SaleId`),
  KEY `FKTransProduct_idx` (`ProductId`),
  KEY `FKTransReward_idx` (`RewardId`),
  KEY `FKTransPoint_idx` (`UserPointId`),
  KEY `FKTransUser_idx` (`UserId`),
  CONSTRAINT `FKTransPoint` FOREIGN KEY (`UserPointId`) REFERENCES `user_points` (`Id`),
  CONSTRAINT `FKTransProduct` FOREIGN KEY (`ProductId`) REFERENCES `product_master` (`Id`),
  CONSTRAINT `FKTransReward` FOREIGN KEY (`RewardId`) REFERENCES `reward_master` (`Id`),
  CONSTRAINT `FKTransSale` FOREIGN KEY (`SaleId`) REFERENCES `sales_master` (`Id`),
  CONSTRAINT `FKTransUser` FOREIGN KEY (`UserId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_transaction_details`
--

LOCK TABLES `points_transaction_details` WRITE;
/*!40000 ALTER TABLE `points_transaction_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `points_transaction_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_dealer_price`
--

DROP TABLE IF EXISTS `product_dealer_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_dealer_price` (
  `Id` int NOT NULL,
  `ProductSalePriceId` int DEFAULT NULL,
  `DealerId` int DEFAULT NULL,
  `DistributorId` int DEFAULT NULL,
  `Price` decimal(6,2) DEFAULT NULL,
  `ProductId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKDealerPrice_idx` (`ProductSalePriceId`),
  KEY `FKDealerUser_idx` (`DealerId`),
  KEY `FKDistributorUser_idx` (`DistributorId`),
  KEY `FKDealerProduct_idx` (`ProductId`),
  KEY `FKDealerPriceMaster_idx` (`ProductSalePriceId`),
  KEY `FKDealerUserMaster_idx` (`DealerId`),
  KEY `FKDistributorUserMaster_idx` (`DistributorId`),
  KEY `FKDealerProductMaster_idx` (`ProductId`),
  CONSTRAINT `FKDealerProductMaster` FOREIGN KEY (`ProductId`) REFERENCES `product_master` (`Id`),
  CONSTRAINT `FKDealerUserMaster` FOREIGN KEY (`DealerId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKDistributorUserMaster` FOREIGN KEY (`DistributorId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_dealer_price`
--

LOCK TABLES `product_dealer_price` WRITE;
/*!40000 ALTER TABLE `product_dealer_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_dealer_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_master`
--

DROP TABLE IF EXISTS `product_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_master` (
  `Id` int NOT NULL,
  `ProductName` varchar(45) NOT NULL,
  `ProductCode` varchar(45) NOT NULL,
  `StartDate` datetime DEFAULT NULL,
  `IsActive` binary(1) DEFAULT '1',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_master`
--

LOCK TABLES `product_master` WRITE;
/*!40000 ALTER TABLE `product_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_points_config`
--

DROP TABLE IF EXISTS `product_points_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_points_config` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `ProductId` int DEFAULT NULL,
  `UserId` int DEFAULT NULL,
  `CityId` int DEFAULT NULL,
  `Points` int DEFAULT NULL,
  `IsUserSpecific` bit(1) DEFAULT b'0',
  `CreatedOn` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `FKPointsProduct_idx` (`ProductId`),
  KEY `FKPointsUserId_idx` (`UserId`),
  KEY `FKPointsCity_idx` (`CityId`),
  CONSTRAINT `FKPointsCity` FOREIGN KEY (`CityId`) REFERENCES `city_master` (`Id`),
  CONSTRAINT `FKPointsProduct` FOREIGN KEY (`ProductId`) REFERENCES `product_master` (`Id`),
  CONSTRAINT `FKPointsUserId` FOREIGN KEY (`UserId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='In this table we configure points to be given if dealer buy and pay these products. These points can be configured at city level or a specific user level (isUserspecific flag is true, then it is at user level) otherwise it is at city level';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_points_config`
--

LOCK TABLES `product_points_config` WRITE;
/*!40000 ALTER TABLE `product_points_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_points_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_purchase_price`
--

DROP TABLE IF EXISTS `product_purchase_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_purchase_price` (
  `Id` int NOT NULL,
  `ProductId` int NOT NULL,
  `Price` decimal(2,0) NOT NULL,
  `SellerId` int NOT NULL,
  `PurchaseDate` datetime DEFAULT NULL,
  `Qty` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKPurchaseUser_idx` (`SellerId`),
  KEY `FKPurchaseProduct_idx` (`ProductId`),
  CONSTRAINT `FKPurchaseProduct` FOREIGN KEY (`ProductId`) REFERENCES `product_master` (`Id`),
  CONSTRAINT `FKPurchaseUser` FOREIGN KEY (`SellerId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_purchase_price`
--

LOCK TABLES `product_purchase_price` WRITE;
/*!40000 ALTER TABLE `product_purchase_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_purchase_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_sale_price`
--

DROP TABLE IF EXISTS `product_sale_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_sale_price` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `ProductId` int DEFAULT NULL,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `IsActive` binary(1) DEFAULT '1',
  `MRP` decimal(6,2) DEFAULT NULL,
`DefaultPrice` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKSaleProduct_idx` (`ProductId`),
  CONSTRAINT `FKSaleProduct` FOREIGN KEY (`ProductId`) REFERENCES `product_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_sale_price`
--

LOCK TABLES `product_sale_price` WRITE;
/*!40000 ALTER TABLE `product_sale_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_sale_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_master`
--

DROP TABLE IF EXISTS `reward_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_master` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Reward` varchar(45) DEFAULT NULL COMMENT 'Cash | Product | Points',
  `Value` int DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_master`
--

LOCK TABLES `reward_master` WRITE;
/*!40000 ALTER TABLE `reward_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `reward_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_details`
--

DROP TABLE IF EXISTS `sales_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_details` (
  `Id` bigint NOT NULL,
  `ProductId` int DEFAULT NULL,
  `Qty` tinyint DEFAULT NULL, 
  `Price` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKSalesProduct_idx` (`ProductId`),
  CONSTRAINT `FKSalesProduct` FOREIGN KEY (`ProductId`) REFERENCES `product_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_details`
--

LOCK TABLES `sales_details` WRITE;
/*!40000 ALTER TABLE `sales_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_master`
--

DROP TABLE IF EXISTS `sales_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_master` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SalesTypeId` int DEFAULT NULL,
  `Amount` decimal(9,2) DEFAULT NULL,
  `BuyerId` int DEFAULT NULL,
  `SalesDate` datetime DEFAULT NULL,
  `DistributorId` int DEFAULT NULL,
  `SalesPersonId` int DEFAULT NULL,
  `SalesStatus` varchar(45) DEFAULT NULL COMMENT 'Received|Delivered|Cancelled',
  `DeliveredDate` datetime DEFAULT NULL,
  `SalesDetailImage` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKSalesType_idx` (`SalesTypeId`),
  KEY `FKSalesBuyer_idx` (`BuyerId`),
  KEY `FKSalesDistributor_idx` (`DistributorId`),
  KEY `FKSalesPerson_idx` (`SalesPersonId`),
  CONSTRAINT `FKSalesBuyer` FOREIGN KEY (`BuyerId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKSalesDistributor` FOREIGN KEY (`DistributorId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKSalesPerson` FOREIGN KEY (`SalesPersonId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKSalesType` FOREIGN KEY (`SalesTypeId`) REFERENCES `sales_type` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_master`
--

LOCK TABLES `sales_master` WRITE;
/*!40000 ALTER TABLE `sales_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_type`
--

DROP TABLE IF EXISTS `sales_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_type` (
  `Id` int NOT NULL,
  `SalesType` varchar(45) DEFAULT NULL,
  `Description` varchar(100) DEFAULT NULL,
  `ExpectedDaysOfPayment` tinyint DEFAULT NULL,
  `Amount` int DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_type`
--

LOCK TABLES `sales_type` WRITE;
/*!40000 ALTER TABLE `sales_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salesman_visit_master`
--

DROP TABLE IF EXISTS `salesman_visit_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salesman_visit_master` (
  `Id` int NOT NULL,
  `SalesManId` int DEFAULT NULL,
  `DealerDistributorId` int DEFAULT NULL,
  `VisitingDate` datetime DEFAULT NULL,
  `Latitude` decimal(10,8) DEFAULT NULL,
  `Longitude` decimal(10,8) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKVisitSalesman_idx` (`SalesManId`),
  KEY `FKVisitDealer_idx` (`DealerDistributorId`),
  CONSTRAINT `FKVisitDealer` FOREIGN KEY (`DealerDistributorId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKVisitSalesman` FOREIGN KEY (`SalesManId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesman_visit_master`
--

LOCK TABLES `salesman_visit_master` WRITE;
/*!40000 ALTER TABLE `salesman_visit_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `salesman_visit_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheme_detail`
--

DROP TABLE IF EXISTS `scheme_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheme_detail` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SchemeId` int NOT NULL,
  `BuyerId` int DEFAULT NULL COMMENT 'If scheme is for specific buyers',
  `LocationId` int DEFAULT NULL COMMENT 'Is scheme is for specific location',
  `SchemProductId` int DEFAULT NULL COMMENT 'If scheme is only for selling of a particuler Product',
  `Value` int DEFAULT NULL COMMENT 'In case of product based scheme it is Qty, for other type of scheme it is amount.',
  `RewardId` int DEFAULT NULL COMMENT 'Reward that will be given to User or particular location or under that scheme',
  PRIMARY KEY (`Id`),
  KEY `FKScheme_idx` (`SchemeId`),
  KEY `FKSchemeUser_idx` (`BuyerId`),
  KEY `FKSchemeLocation_idx` (`LocationId`),
  KEY `FKSchemeProduct_idx` (`SchemProductId`),
  KEY `FKSchemeReward_idx` (`RewardId`),
  CONSTRAINT `FKScheme` FOREIGN KEY (`SchemeId`) REFERENCES `scheme_master` (`Id`),
  CONSTRAINT `FKSchemeLocation` FOREIGN KEY (`LocationId`) REFERENCES `city_master` (`Id`),
  CONSTRAINT `FKSchemeProduct` FOREIGN KEY (`SchemProductId`) REFERENCES `product_master` (`Id`),
  CONSTRAINT `FKSchemeReward` FOREIGN KEY (`RewardId`) REFERENCES `reward_master` (`Id`),
  CONSTRAINT `FKSchemeUser` FOREIGN KEY (`BuyerId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheme_detail`
--

LOCK TABLES `scheme_detail` WRITE;
/*!40000 ALTER TABLE `scheme_detail` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheme_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheme_master`
--

DROP TABLE IF EXISTS `scheme_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheme_master` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SchemName` varchar(45) DEFAULT NULL,
  `IsActive` binary(1) DEFAULT NULL,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `Description` varchar(100) DEFAULT NULL,
  `SchemeType` varchar(45) DEFAULT NULL COMMENT 'Revenue, Payment(in a period), Product, Points',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheme_master`
--

LOCK TABLES `scheme_master` WRITE;
/*!40000 ALTER TABLE `scheme_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheme_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheme_master_history`
--

DROP TABLE IF EXISTS `scheme_master_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheme_master_history` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SchemMasterId` int DEFAULT NULL,
  `SchemName` varchar(45) DEFAULT NULL,
  `StartDate` datetime DEFAULT NULL,
  `IsActive` binary(1) DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `Description` varchar(100) DEFAULT NULL,
  `SchemeType` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKHistoryScheme_idx` (`SchemMasterId`),
  CONSTRAINT `FKHistoryScheme` FOREIGN KEY (`SchemMasterId`) REFERENCES `scheme_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheme_master_history`
--

LOCK TABLES `scheme_master_history` WRITE;
/*!40000 ALTER TABLE `scheme_master_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheme_master_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheme_messages`
--

DROP TABLE IF EXISTS `scheme_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheme_messages` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SchemId` int DEFAULT NULL,
  `ScreenName` varchar(45) DEFAULT NULL,
  `Message` varchar(100) DEFAULT NULL,
  `URL` varchar(100) DEFAULT NULL,
  `IsActive` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheme_messages`
--

LOCK TABLES `scheme_messages` WRITE;
/*!40000 ALTER TABLE `scheme_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheme_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheme_steps`
--

DROP TABLE IF EXISTS `scheme_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheme_steps` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SchemId` int NOT NULL,
  `ProductId` int DEFAULT NULL,
  `ValueOrQty` int DEFAULT NULL,
  `Description` varchar(100) DEFAULT NULL,
  `RewardId` int DEFAULT NULL,
  `StepSequenceOrder` tinyint DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKStepsScheme_idx` (`SchemId`),
  KEY `FKSchemeProduct_idx` (`ProductId`),
  KEY `FKSchemeReward_idx` (`RewardId`),
  KEY `FKStepsSchemeMaster_idx` (`SchemId`),
  KEY `FKStepsSchemeProduct_idx` (`ProductId`),
  KEY `FKStepsSchemeReward_idx` (`RewardId`),
  CONSTRAINT `FKStepsSchemeMaster` FOREIGN KEY (`SchemId`) REFERENCES `scheme_master` (`Id`),
  CONSTRAINT `FKStepsSchemeProductMaster` FOREIGN KEY (`ProductId`) REFERENCES `product_master` (`Id`),
  CONSTRAINT `FKStepsSchemeRewardMaster` FOREIGN KEY (`RewardId`) REFERENCES `reward_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheme_steps`
--

LOCK TABLES `scheme_steps` WRITE;
/*!40000 ALTER TABLE `scheme_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheme_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheme_user_step_detail`
--

DROP TABLE IF EXISTS `scheme_user_step_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheme_user_step_detail` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `SchemeUserStepId` int NOT NULL,
  `Date` datetime DEFAULT NULL,
  `Value` decimal(8,2) DEFAULT NULL,
  `ProductId` int DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FKStepDetailUserStep_idx` (`SchemeUserStepId`),
  KEY `FKStepDetailProduct_idx` (`ProductId`),
  CONSTRAINT `FKStepDetailProduct` FOREIGN KEY (`ProductId`) REFERENCES `product_master` (`Id`),
  CONSTRAINT `FKStepDetailUserStep` FOREIGN KEY (`SchemeUserStepId`) REFERENCES `scheme_user_steps` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheme_user_step_detail`
--

LOCK TABLES `scheme_user_step_detail` WRITE;
/*!40000 ALTER TABLE `scheme_user_step_detail` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheme_user_step_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheme_user_steps`
--

DROP TABLE IF EXISTS `scheme_user_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheme_user_steps` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `StepId` int DEFAULT NULL,
  `SchemId` int DEFAULT NULL,
  `UserId` int DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `IsCompleted` binary(1) DEFAULT '0',
  PRIMARY KEY (`Id`),
  KEY `FKUserStepSchemeStep_idx` (`StepId`),
  KEY `FKUserStepScheme_idx` (`SchemId`),
  KEY `FKUserStepUser_idx` (`UserId`),
  CONSTRAINT `FKUserStepScheme` FOREIGN KEY (`SchemId`) REFERENCES `scheme_master` (`Id`),
  CONSTRAINT `FKUserStepSchemeStep` FOREIGN KEY (`StepId`) REFERENCES `scheme_steps` (`Id`),
  CONSTRAINT `FKUserStepUser` FOREIGN KEY (`UserId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheme_user_steps`
--

LOCK TABLES `scheme_user_steps` WRITE;
/*!40000 ALTER TABLE `scheme_user_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheme_user_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `state_master`
--

DROP TABLE IF EXISTS `state_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `state_master` (
  `Id` int NOT NULL,
  `StateName` varchar(45) DEFAULT NULL,
  `CountryId` int DEFAULT NULL,
  `IsActive` binary(1) DEFAULT '1',
  PRIMARY KEY (`Id`),
  KEY `FKStateCountry_idx` (`CountryId`),
  CONSTRAINT `FKStateCountry` FOREIGN KEY (`CountryId`) REFERENCES `country_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `state_master`
--

LOCK TABLES `state_master` WRITE;
/*!40000 ALTER TABLE `state_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `state_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_master`
--

DROP TABLE IF EXISTS `user_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_master` (
  `Id` int NOT NULL,
  `UserName` varchar(45) NOT NULL,
  `IsActive` binary(1) NOT NULL DEFAULT '0',
  `Email` varchar(45) DEFAULT NULL,
  `UserTypeId` int DEFAULT NULL,
  `AddressId` int DEFAULT NULL,
  `EmployeeId` int DEFAULT NULL,
  `CreatedOn` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedOn` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `FKUserUserType_idx` (`UserTypeId`),
  KEY `FKUserAddress_idx` (`AddressId`),
  KEY `FKUserEmployee_idx` (`EmployeeId`),
  CONSTRAINT `FKUserAddress` FOREIGN KEY (`AddressId`) REFERENCES `address_master` (`Id`),
  CONSTRAINT `FKUserEmployee` FOREIGN KEY (`EmployeeId`) REFERENCES `user_master` (`Id`),
  CONSTRAINT `FKUserUserType` FOREIGN KEY (`UserTypeId`) REFERENCES `user_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_master`
--

LOCK TABLES `user_master` WRITE;
/*!40000 ALTER TABLE `user_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_points`
--

DROP TABLE IF EXISTS `user_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_points` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `UserId` int DEFAULT NULL,
  `Points` int unsigned NOT NULL,
  `LastUpdated` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `FKPointsUser_idx` (`UserId`),
  CONSTRAINT `FKUserPointsUserMaster` FOREIGN KEY (`UserId`) REFERENCES `user_master` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_points`
--

LOCK TABLES `user_points` WRITE;
/*!40000 ALTER TABLE `user_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_type`
--

DROP TABLE IF EXISTS `user_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_type` (
  `id` int NOT NULL,
  `typename` varchar(45) NOT NULL,
  `description` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_type`
--

LOCK TABLES `user_type` WRITE;
/*!40000 ALTER TABLE `user_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_type` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-16 22:34:15

CREATE TABLE `notification_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `UserId` INT DEFAULT NULL COMMENT 'Target user for the notification. NULL means broadcast to all.',
  `UserTypeId` INT DEFAULT NULL COMMENT 'Optional: Target a specific user type (e.g., Dealer, Distributor)',
  `Title` VARCHAR(100) NOT NULL COMMENT 'Short title of the notification',
  `Message` TEXT NOT NULL COMMENT 'Full message content',
  `Screen` VARCHAR(50) DEFAULT NULL COMMENT 'Optional: Screen or module where this message is relevant',
  `URL` VARCHAR(255) DEFAULT NULL COMMENT 'Optional: Link to open when user taps the notification',
  `IsRead` BOOLEAN DEFAULT FALSE COMMENT 'Whether the user has read the notification',
  `IsActive` BOOLEAN DEFAULT TRUE COMMENT 'Whether the notification is active',
  `CreatedOn` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `ValidTill` DATETIME DEFAULT NULL COMMENT 'Optional: Expiry date for the notification',
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`UserId`) REFERENCES `user_master`(`Id`),
  FOREIGN KEY (`UserTypeId`) REFERENCES `user_type`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


ALTER TABLE `salesman_visit_master`
ADD COLUMN `VisitStatus` VARCHAR(20) DEFAULT 'Pending' COMMENT 'Valid, Invalid, Pending';

ALTER TABLE `user_master`
ADD COLUMN `Latitude` DECIMAL(10,8) DEFAULT NULL,
ADD COLUMN `Longitude` DECIMAL(11,8) DEFAULT NULL;

ALTER TABLE `salesman_visit_master`
ADD COLUMN `VisitStatus` VARCHAR(20) DEFAULT 'Pending',
ADD COLUMN `DistanceFromDealer` DECIMAL(6,2) DEFAULT NULL,
ADD COLUMN `ValidationRemarks` VARCHAR(255) DEFAULT NULL;


ALTER TABLE reward_master
ADD COLUMN RewardType VARCHAR(20) DEFAULT 'Product' COMMENT 'Product, Voucher, Service, Cash',
ADD COLUMN Stock INT DEFAULT 0,
ADD COLUMN ImageURL VARCHAR(255),
ADD COLUMN Description TEXT;

CREATE TABLE reward_redemption_requests (
  Id INT AUTO_INCREMENT PRIMARY KEY,
  UserId INT NOT NULL,
  RewardId INT NOT NULL,
  Quantity INT DEFAULT 1,
  PointsUsed INT NOT NULL,
  Status VARCHAR(20) DEFAULT 'Pending' COMMENT 'Pending, Approved, Rejected, Delivered',
  RequestedOn DATETIME DEFAULT CURRENT_TIMESTAMP,
  ApprovedOn DATETIME DEFAULT NULL,
  DeliveredOn DATETIME DEFAULT NULL,
  DeliveryAddressId INT DEFAULT NULL,
  FOREIGN KEY (UserId) REFERENCES user_master(Id),
  FOREIGN KEY (RewardId) REFERENCES reward_master(Id),
  FOREIGN KEY (DeliveryAddressId) REFERENCES address_master(Id)
);


CREATE TABLE `color_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `ColorName` VARCHAR(50) NOT NULL,
  `ColorCode` VARCHAR(10) DEFAULT NULL COMMENT 'Optional HEX or RGB code',
  `IsActive` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`Id`)
);


CREATE TABLE `product_color_inventory` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `ProductId` INT NOT NULL,
  `ColorId` INT NOT NULL,
  `QtyAvailable` INT DEFAULT 0,
  `QtySold` INT DEFAULT 0,
  `LastUpdated` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`ProductId`) REFERENCES `product_master`(`Id`),
  FOREIGN KEY (`ColorId`) REFERENCES `color_master`(`Id`)
);

CREATE TABLE `product_category_master` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `CategoryName` VARCHAR(100) NOT NULL,
  `Description` VARCHAR(255) DEFAULT NULL,
  `IsActive` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`Id`)
)

ALTER TABLE `product_master`
ADD COLUMN `CategoryId` INT DEFAULT NULL AFTER `ProductCode`,
ADD COLUMN `ImageURL` VARCHAR(255) DEFAULT NULL AFTER `IsActive`,
ADD CONSTRAINT `FKProductCategory`
  FOREIGN KEY (`CategoryId`) REFERENCES `product_category_master` (`Id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE product_color_mapping (
  Id INT AUTO_INCREMENT PRIMARY KEY,
  ProductId INT NOT NULL,
  ColorId INT NOT NULL,
  IsActive BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (ProductId) REFERENCES product_master(Id) ON DELETE CASCADE,
  FOREIGN KEY (ColorId) REFERENCES color_master(Id) ON DELETE CASCADE
);

-- Add ProductColorMappingId column
ALTER TABLE sales_details
ADD COLUMN ProductColorMappingId INT NULL AFTER ProductId;

-- Add foreign key relationship to product_color_mapping
ALTER TABLE sales_details
ADD CONSTRAINT fk_salesdetails_productcolormapping
FOREIGN KEY (ProductColorMappingId)
REFERENCES product_color_mapping(Id)
ON DELETE SET NULL
ON UPDATE CASCADE;

ALTER TABLE payment_master
ADD COLUMN InvoiceNumber VARCHAR(100) DEFAULT NULL AFTER PaymentType,
ADD COLUMN InvoiceDocument VARCHAR(255) DEFAULT NULL COMMENT 'File path or URL of uploaded GST bill document' AFTER InvoiceNumber;

ALTER TABLE reward_redemption_requests
ADD COLUMN Remark VARCHAR(255) DEFAULT NULL COMMENT 'Admin comments like courier details, rejection reason etc.' AFTER DeliveryAddressId;
