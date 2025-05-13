-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 01:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `boarding_house_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `admin_assign_apartment_owner` (IN `apartment_id_param` INT, IN `landlord_id_param` INT)   BEGIN
  DECLARE is_landlord BOOLEAN;
  
  -- Check if user is a landlord
  SELECT COUNT(*) > 0 INTO is_landlord 
  FROM users 
  WHERE id = landlord_id_param AND role = 'landlord';
  
  IF is_landlord THEN
    UPDATE apartments 
    SET owner_id = landlord_id_param 
    WHERE id = apartment_id_param;
    
    SELECT 'Apartment assigned successfully' AS message;
  ELSE
    SELECT 'Error: User is not a landlord' AS message;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `admin_cancel_reservation` (IN `reservation_id_param` INT)   BEGIN
  UPDATE reservations 
  SET status = 'canceled' 
  WHERE id = reservation_id_param;
  
  SELECT 'Reservation cancelled successfully' AS message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `admin_get_system_stats` ()   BEGIN
  SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'tenant') AS tenant_count,
    (SELECT COUNT(*) FROM users WHERE role = 'landlord') AS landlord_count,
    (SELECT COUNT(*) FROM apartments) AS apartment_count,
    (SELECT COUNT(*) FROM apartments WHERE available = 1) AS available_apartments,
    (SELECT COUNT(*) FROM reservations WHERE status = 'reserved') AS active_reservations,
    (SELECT COUNT(*) FROM reservations WHERE status = 'canceled') AS canceled_reservations;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `admin_toggle_apartment_availability` (IN `apartment_id_param` INT)   BEGIN
  UPDATE apartments 
  SET available = NOT available 
  WHERE id = apartment_id_param;
  
  SELECT available FROM apartments WHERE id = apartment_id_param;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_accept_reservation` (IN `landlord_id_param` INT, IN `reservation_id_param` INT)   BEGIN
  DECLARE is_owner BOOLEAN;
  DECLARE apartment_id_var INT;
  DECLARE tenant_id_var INT;
  
  -- Get the apartment_id and tenant_id for the reservation
  SELECT apartment_id, user_id INTO apartment_id_var, tenant_id_var 
  FROM reservations 
  WHERE id = reservation_id_param;
  
  -- Check if user is the owner of the apartment
  SELECT COUNT(*) > 0 INTO is_owner 
  FROM apartments 
  WHERE id = apartment_id_var AND owner_id = landlord_id_param;
  
  IF is_owner THEN
    UPDATE reservations 
    SET 
      status = 'accepted',
      updated_at = CURRENT_TIMESTAMP
    WHERE id = reservation_id_param;
    
    -- Make apartment unavailable when reservation is accepted
    -- and assign the tenant to the apartment
    UPDATE apartments
    SET 
      available = 0,
      tenant_id = tenant_id_var,
      updated_at = CURRENT_TIMESTAMP
    WHERE id = apartment_id_var;
    
    SELECT 'Reservation accepted successfully' AS message;
  ELSE
    SELECT 'Error: You are not the owner of this apartment' AS message;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_assign_tenant` (IN `landlord_id_param` INT, IN `apartment_id_param` INT, IN `tenant_id_param` INT)   BEGIN
  DECLARE is_owner BOOLEAN;
  DECLARE is_tenant BOOLEAN;
  
  -- Check if user is the owner of the apartment
  SELECT COUNT(*) > 0 INTO is_owner 
  FROM apartments 
  WHERE id = apartment_id_param AND owner_id = landlord_id_param;
  
  -- Check if user is a tenant
  SELECT COUNT(*) > 0 INTO is_tenant 
  FROM users 
  WHERE id = tenant_id_param AND role = 'tenant';
  
  IF is_owner THEN
    IF is_tenant THEN
      UPDATE apartments 
      SET 
        tenant_id = tenant_id_param,
        available = 0,
        updated_at = CURRENT_TIMESTAMP
      WHERE id = apartment_id_param;
      
      SELECT 'Tenant assigned successfully' AS message;
    ELSE
      SELECT 'Error: User is not a tenant' AS message;
    END IF;
  ELSE
    SELECT 'Error: You are not the owner of this apartment' AS message;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_get_apartments` (IN `landlord_id_param` INT)   BEGIN
  SELECT * FROM landlord_apartments_view 
  WHERE id IN (SELECT id FROM apartments WHERE owner_id = landlord_id_param);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_get_reservations` (IN `landlord_id_param` INT)   BEGIN
  SELECT * FROM landlord_reservations_view 
  WHERE apartment_id IN (SELECT id FROM apartments WHERE owner_id = landlord_id_param);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_get_tenant_details` (IN `landlord_id_param` INT, IN `reservation_id_param` INT)   BEGIN
  DECLARE is_owner BOOLEAN;
  DECLARE apartment_id_var INT;
  
  -- Get the apartment_id for the reservation
  SELECT apartment_id INTO apartment_id_var 
  FROM reservations 
  WHERE id = reservation_id_param;
  
  -- Check if user is the owner of the apartment
  SELECT COUNT(*) > 0 INTO is_owner 
  FROM apartments 
  WHERE id = apartment_id_var AND owner_id = landlord_id_param;
  
  IF is_owner THEN
    SELECT 
      u.id,
      u.name,
      u.lastname,
      u.email,
      u.phone,
      r.reservation_date,
      r.start_date,
      r.end_date,
      r.duration,
      r.status,
      (a.price * r.duration) AS total_price
    FROM 
      reservations r
    JOIN 
      users u ON r.user_id = u.id
    JOIN 
      apartments a ON r.apartment_id = a.id
    WHERE 
      r.id = reservation_id_param;
  ELSE
    SELECT 'Error: You are not the owner of this apartment' AS message;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_reject_reservation` (IN `landlord_id_param` INT, IN `reservation_id_param` INT)   BEGIN
  DECLARE is_owner BOOLEAN;
  DECLARE apartment_id_var INT;
  
  -- Get the apartment_id for the reservation
  SELECT apartment_id INTO apartment_id_var 
  FROM reservations 
  WHERE id = reservation_id_param;
  
  -- Check if user is the owner of the apartment
  SELECT COUNT(*) > 0 INTO is_owner 
  FROM apartments 
  WHERE id = apartment_id_var AND owner_id = landlord_id_param;
  
  IF is_owner THEN
    UPDATE reservations 
    SET 
      status = 'rejected',
      updated_at = CURRENT_TIMESTAMP
    WHERE id = reservation_id_param;
    
    SELECT 'Reservation rejected successfully' AS message;
  ELSE
    SELECT 'Error: You are not the owner of this apartment' AS message;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_remove_tenant` (IN `landlord_id_param` INT, IN `apartment_id_param` INT)   BEGIN
  DECLARE is_owner BOOLEAN;
  
  -- Check if user is the owner of the apartment
  SELECT COUNT(*) > 0 INTO is_owner 
  FROM apartments 
  WHERE id = apartment_id_param AND owner_id = landlord_id_param;
  
  IF is_owner THEN
    UPDATE apartments 
    SET 
      tenant_id = NULL,
      updated_at = CURRENT_TIMESTAMP
    WHERE id = apartment_id_param;
    
    SELECT 'Tenant removed successfully' AS message;
  ELSE
    SELECT 'Error: You are not the owner of this apartment' AS message;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_toggle_apartment_availability` (IN `landlord_id_param` INT, IN `apartment_id_param` INT)   BEGIN
  DECLARE is_owner BOOLEAN;
  
  -- Check if user is the owner of the apartment
  SELECT COUNT(*) > 0 INTO is_owner 
  FROM apartments 
  WHERE id = apartment_id_param AND owner_id = landlord_id_param;
  
  IF is_owner THEN
    UPDATE apartments 
    SET 
      available = NOT available,
      updated_at = CURRENT_TIMESTAMP
    WHERE id = apartment_id_param;
    
    SELECT 'Apartment availability updated successfully' AS message;
  ELSE
    SELECT 'Error: You are not the owner of this apartment' AS message;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `landlord_update_apartment` (IN `landlord_id_param` INT, IN `apartment_id_param` INT, IN `name_param` VARCHAR(255), IN `location_param` VARCHAR(255), IN `price_param` DECIMAL(10,2), IN `description_param` TEXT, IN `bedrooms_param` INT, IN `bathrooms_param` INT, IN `furnished_param` TINYINT(1), IN `pets_allowed_param` TINYINT(1), IN `parking_param` TINYINT(1), IN `internet_param` TINYINT(1))   BEGIN
  DECLARE is_owner BOOLEAN;
  
  -- Check if user is the owner of the apartment
  SELECT COUNT(*) > 0 INTO is_owner 
  FROM apartments 
  WHERE id = apartment_id_param AND owner_id = landlord_id_param;
  
  IF is_owner THEN
    UPDATE apartments 
    SET 
      name = name_param,
      location = location_param,
      price = price_param,
      description = description_param,
      bedrooms = bedrooms_param,
      bathrooms = bathrooms_param,
      furnished = furnished_param,
      pets_allowed = pets_allowed_param,
      parking = parking_param,
      internet = internet_param,
      updated_at = CURRENT_TIMESTAMP
    WHERE id = apartment_id_param;
    
    SELECT 'Apartment updated successfully' AS message;
  ELSE
    SELECT 'Error: You are not the owner of this apartment' AS message;
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'ADMIN', 'admin@gmail.com', '$2y$10$3GCftyoNsA6s02fTO6LybOx6uoRIoJt5lBMUBhmCOZaSpNM1a0rZm', '2024-12-08 07:15:33');

-- --------------------------------------------------------

--
-- Stand-in structure for view `admin_apartments_view`
-- (See below for the actual view)
--
CREATE TABLE `admin_apartments_view` (
`id` int(11)
,`name` varchar(255)
,`location` varchar(255)
,`price` decimal(10,2)
,`available` tinyint(1)
,`description` text
,`bedrooms` int(11)
,`bathrooms` int(11)
,`furnished` tinyint(1)
,`pets_allowed` tinyint(1)
,`parking` tinyint(1)
,`internet` tinyint(1)
,`created_at` timestamp
,`owner_name` varchar(50)
,`owner_lastname` varchar(50)
,`owner_email` varchar(100)
,`tenant_name` varchar(50)
,`tenant_lastname` varchar(50)
,`tenant_email` varchar(100)
,`reservation_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `admin_reservations_view`
-- (See below for the actual view)
--
CREATE TABLE `admin_reservations_view` (
`reservation_id` int(11)
,`reservation_date` timestamp
,`start_date` date
,`end_date` date
,`duration` int(11)
,`status` enum('reserved','accepted','rejected','canceled')
,`user_id` int(11)
,`user_name` varchar(50)
,`user_lastname` varchar(50)
,`user_email` varchar(100)
,`user_phone` varchar(20)
,`apartment_id` int(11)
,`apartment_name` varchar(255)
,`apartment_location` varchar(255)
,`apartment_price` decimal(10,2)
,`owner_id` int(11)
,`owner_name` varchar(50)
,`owner_lastname` varchar(50)
,`owner_email` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `admin_users_view`
-- (See below for the actual view)
--
CREATE TABLE `admin_users_view` (
`id` int(11)
,`name` varchar(50)
,`lastname` varchar(50)
,`email` varchar(100)
,`role` enum('tenant','landlord')
,`phone` varchar(20)
,`created_at` timestamp
,`reservation_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `apartments`
--

CREATE TABLE `apartments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available` tinyint(1) DEFAULT 1,
  `owner_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `bedrooms` int(11) DEFAULT 1,
  `bathrooms` int(11) DEFAULT 1,
  `furnished` tinyint(1) DEFAULT 0,
  `pets_allowed` tinyint(1) DEFAULT 0,
  `parking` tinyint(1) DEFAULT 0,
  `internet` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `apartments`
--

INSERT INTO `apartments` (`id`, `name`, `location`, `price`, `available`, `owner_id`, `tenant_id`, `description`, `bedrooms`, `bathrooms`, `furnished`, `pets_allowed`, `parking`, `internet`, `created_at`, `updated_at`) VALUES
(1, 'Sunset Apartments', 'Los Angeles', 2500.00, 0, 5, 6, 'Beautiful sunset view apartments', 2, 1, 1, 0, 1, 1, '2024-12-08 00:00:00', '2025-05-13 09:54:07'),
(2, 'Ocean View Residences', 'Miami', 3000.00, 0, 5, NULL, 'Luxury apartments with ocean view', 3, 2, 1, 1, 1, 1, '2024-12-08 00:15:00', '2025-05-13 09:55:53'),
(3, 'Mountain Retreat', 'Denver', 1800.00, 0, 5, 6, 'Cozy apartments near the mountains', 1, 1, 0, 1, 0, 1, '2024-12-08 00:30:00', '2025-05-13 09:58:25'),
(4, 'City Center Loft', 'New York', 3500.00, 1, 5, NULL, 'Modern lofts in the heart of the city', 2, 2, 1, 0, 0, 1, '2024-12-08 00:45:00', '2025-05-13 09:57:25'),
(5, 'Lakeside Villas', 'Chicago', 32100.00, 1, 5, 3, 'Luxurious villas by the lake', 4, 3, 1, 1, 1, 1, '2024-12-08 01:00:00', '2025-05-13 09:57:22');

-- --------------------------------------------------------

--
-- Stand-in structure for view `landlord_apartments_view`
-- (See below for the actual view)
--
CREATE TABLE `landlord_apartments_view` (
`id` int(11)
,`name` varchar(255)
,`location` varchar(255)
,`description` text
,`price` decimal(10,2)
,`available` tinyint(1)
,`bedrooms` int(11)
,`bathrooms` int(11)
,`furnished` tinyint(1)
,`pets_allowed` tinyint(1)
,`parking` tinyint(1)
,`internet` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`tenant_id` int(11)
,`tenant_name` varchar(50)
,`tenant_lastname` varchar(50)
,`tenant_email` varchar(100)
,`tenant_phone` varchar(20)
,`reservation_count` bigint(21)
,`active_reservations` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `landlord_reservations_view`
-- (See below for the actual view)
--
CREATE TABLE `landlord_reservations_view` (
`reservation_id` int(11)
,`reservation_date` timestamp
,`start_date` date
,`end_date` date
,`duration` int(11)
,`status` enum('reserved','accepted','rejected','canceled')
,`tenant_id` int(11)
,`tenant_name` varchar(50)
,`tenant_lastname` varchar(50)
,`tenant_email` varchar(100)
,`tenant_phone` varchar(20)
,`apartment_id` int(11)
,`apartment_name` varchar(255)
,`apartment_location` varchar(255)
,`apartment_price` decimal(10,2)
,`total_price` decimal(20,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `apartment_id` int(11) NOT NULL,
  `reservation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration` int(11) NOT NULL DEFAULT 30 COMMENT 'Duration in months',
  `status` enum('reserved','accepted','rejected','canceled') DEFAULT 'reserved',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `apartment_id`, `reservation_date`, `duration`, `status`, `start_date`, `end_date`, `updated_at`) VALUES
(8, 3, 2, '2024-12-19 07:00:00', 30, 'reserved', '2025-01-01', '2027-07-01', NULL),
(10, 3, 1, '2024-12-08 07:03:00', 15, 'reserved', '2025-01-15', '2026-04-15', NULL),
(11, 6, 1, '2025-05-13 09:52:00', 30, 'accepted', NULL, NULL, '2025-05-13 09:53:03'),
(12, 6, 2, '2025-05-20 09:55:00', 90, 'accepted', NULL, NULL, '2025-05-13 09:55:53'),
(13, 6, 3, '2025-05-14 09:57:00', 60, 'accepted', NULL, NULL, '2025-05-13 09:58:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('tenant','landlord') NOT NULL DEFAULT 'tenant',
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `lastname`, `email`, `password`, `role`, `phone`, `created_at`) VALUES
(5, 'John', 'Doe', 'johndoe@gmail.com', '$2y$10$QZrm0ZRElq.v0q5MIxhz2.61QaPz/oPQgWli9W7NaHqEhAyXE8d7i', 'landlord', NULL, '2025-05-13 09:50:29'),
(6, 'Tenant', 'Sting', 'sting@gmail.com', '$2y$10$iEmZabGwuLT2qO9KVdCL2uRj62pMNZCsttkuiJDbKHCPH/07TzEgS', 'tenant', NULL, '2025-05-13 09:51:44');

-- --------------------------------------------------------

--
-- Structure for view `admin_apartments_view`
--
DROP TABLE IF EXISTS `admin_apartments_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `admin_apartments_view`  AS SELECT `a`.`id` AS `id`, `a`.`name` AS `name`, `a`.`location` AS `location`, `a`.`price` AS `price`, `a`.`available` AS `available`, `a`.`description` AS `description`, `a`.`bedrooms` AS `bedrooms`, `a`.`bathrooms` AS `bathrooms`, `a`.`furnished` AS `furnished`, `a`.`pets_allowed` AS `pets_allowed`, `a`.`parking` AS `parking`, `a`.`internet` AS `internet`, `a`.`created_at` AS `created_at`, `u`.`name` AS `owner_name`, `u`.`lastname` AS `owner_lastname`, `u`.`email` AS `owner_email`, `t`.`name` AS `tenant_name`, `t`.`lastname` AS `tenant_lastname`, `t`.`email` AS `tenant_email`, count(distinct `r`.`id`) AS `reservation_count` FROM (((`apartments` `a` left join `users` `u` on(`a`.`owner_id` = `u`.`id`)) left join `users` `t` on(`a`.`tenant_id` = `t`.`id`)) left join `reservations` `r` on(`a`.`id` = `r`.`apartment_id`)) GROUP BY `a`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `admin_reservations_view`
--
DROP TABLE IF EXISTS `admin_reservations_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `admin_reservations_view`  AS SELECT `r`.`id` AS `reservation_id`, `r`.`reservation_date` AS `reservation_date`, `r`.`start_date` AS `start_date`, `r`.`end_date` AS `end_date`, `r`.`duration` AS `duration`, `r`.`status` AS `status`, `u`.`id` AS `user_id`, `u`.`name` AS `user_name`, `u`.`lastname` AS `user_lastname`, `u`.`email` AS `user_email`, `u`.`phone` AS `user_phone`, `a`.`id` AS `apartment_id`, `a`.`name` AS `apartment_name`, `a`.`location` AS `apartment_location`, `a`.`price` AS `apartment_price`, `o`.`id` AS `owner_id`, `o`.`name` AS `owner_name`, `o`.`lastname` AS `owner_lastname`, `o`.`email` AS `owner_email` FROM (((`reservations` `r` join `users` `u` on(`r`.`user_id` = `u`.`id`)) join `apartments` `a` on(`r`.`apartment_id` = `a`.`id`)) left join `users` `o` on(`a`.`owner_id` = `o`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `admin_users_view`
--
DROP TABLE IF EXISTS `admin_users_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `admin_users_view`  AS SELECT `u`.`id` AS `id`, `u`.`name` AS `name`, `u`.`lastname` AS `lastname`, `u`.`email` AS `email`, `u`.`role` AS `role`, `u`.`phone` AS `phone`, `u`.`created_at` AS `created_at`, count(distinct `r`.`id`) AS `reservation_count` FROM (`users` `u` left join `reservations` `r` on(`u`.`id` = `r`.`user_id`)) GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `landlord_apartments_view`
--
DROP TABLE IF EXISTS `landlord_apartments_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `landlord_apartments_view`  AS SELECT `a`.`id` AS `id`, `a`.`name` AS `name`, `a`.`location` AS `location`, `a`.`description` AS `description`, `a`.`price` AS `price`, `a`.`available` AS `available`, `a`.`bedrooms` AS `bedrooms`, `a`.`bathrooms` AS `bathrooms`, `a`.`furnished` AS `furnished`, `a`.`pets_allowed` AS `pets_allowed`, `a`.`parking` AS `parking`, `a`.`internet` AS `internet`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `t`.`id` AS `tenant_id`, `t`.`name` AS `tenant_name`, `t`.`lastname` AS `tenant_lastname`, `t`.`email` AS `tenant_email`, `t`.`phone` AS `tenant_phone`, count(distinct `r`.`id`) AS `reservation_count`, sum(case when `r`.`status` = 'reserved' or `r`.`status` = 'accepted' then 1 else 0 end) AS `active_reservations` FROM ((`apartments` `a` left join `users` `t` on(`a`.`tenant_id` = `t`.`id`)) left join `reservations` `r` on(`a`.`id` = `r`.`apartment_id`)) WHERE `a`.`owner_id` is not null GROUP BY `a`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `landlord_reservations_view`
--
DROP TABLE IF EXISTS `landlord_reservations_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `landlord_reservations_view`  AS SELECT `r`.`id` AS `reservation_id`, `r`.`reservation_date` AS `reservation_date`, `r`.`start_date` AS `start_date`, `r`.`end_date` AS `end_date`, `r`.`duration` AS `duration`, `r`.`status` AS `status`, `u`.`id` AS `tenant_id`, `u`.`name` AS `tenant_name`, `u`.`lastname` AS `tenant_lastname`, `u`.`email` AS `tenant_email`, `u`.`phone` AS `tenant_phone`, `a`.`id` AS `apartment_id`, `a`.`name` AS `apartment_name`, `a`.`location` AS `apartment_location`, `a`.`price` AS `apartment_price`, `a`.`price`* `r`.`duration` AS `total_price` FROM ((`reservations` `r` join `users` `u` on(`r`.`user_id` = `u`.`id`)) join `apartments` `a` on(`r`.`apartment_id` = `a`.`id`)) WHERE `a`.`owner_id` is not null ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `apartments`
--
ALTER TABLE `apartments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `apartment_id` (`apartment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `apartments`
--
ALTER TABLE `apartments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
