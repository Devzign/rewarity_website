USE rewarity_web_db;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `Email` varchar(150) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `DisplayName` varchar(150) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  `CreatedOn` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UX_AdminUsers_Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `admin_users` (`Email`, `PasswordHash`, `DisplayName`)
VALUES ('admin@rewarity.com', '$2y$12$Z9s7k3sflvmzKMiqg/JNZOicFfo18ThyIoZVGNmkPDOUSAG.TxqzG', 'Super Admin')
ON DUPLICATE KEY UPDATE
  `PasswordHash` = VALUES(`PasswordHash`),
  `DisplayName` = VALUES(`DisplayName`),
  `IsActive` = 1;
