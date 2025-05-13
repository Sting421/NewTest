SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- Add primary keys and indexes
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `apartments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `tenant_id` (`tenant_id`);

ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `apartment_id` (`apartment_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

-- Auto increment
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `apartments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Foreign key constraints
ALTER TABLE `apartments`
  ADD CONSTRAINT `apartments_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `apartments_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE CASCADE;

-- Create views
CREATE VIEW `admin_users_view` AS
SELECT
  u.id,
  u.name,
  u.lastname,
  u.email,
  u.role,
  u.phone,
  u.created_at,
  COUNT(DISTINCT r.id) AS reservation_count
FROM
  users u
LEFT JOIN
  reservations r ON u.id = r.user_id
GROUP BY
  u.id;

CREATE VIEW `admin_apartments_view` AS
SELECT
  a.id,
  a.name,
  a.location,
  a.price,
  a.available,
  a.description,
  a.bedrooms,
  a.bathrooms,
  a.furnished,
  a.pets_allowed,
  a.parking,
  a.internet,
  a.created_at,
  u.name AS owner_name,
  u.lastname AS owner_lastname,
  u.email AS owner_email,
  t.name AS tenant_name,
  t.lastname AS tenant_lastname,
  t.email AS tenant_email,
  COUNT(DISTINCT r.id) AS reservation_count
FROM
  apartments a
LEFT JOIN
  users u ON a.owner_id = u.id
LEFT JOIN
  users t ON a.tenant_id = t.id
LEFT JOIN
  reservations r ON a.id = r.apartment_id
GROUP BY
  a.id;

CREATE VIEW `admin_reservations_view` AS
SELECT
  r.id AS reservation_id,
  r.reservation_date,
  r.start_date,
  r.end_date,
  r.duration,
  r.status,
  u.id AS user_id,
  u.name AS user_name,
  u.lastname AS user_lastname,
  u.email AS user_email,
  u.phone AS user_phone,
  a.id AS apartment_id,
  a.name AS apartment_name,
  a.location AS apartment_location,
  a.price AS apartment_price,
  o.id AS owner_id,
  o.name AS owner_name,
  o.lastname AS owner_lastname,
  o.email AS owner_email
FROM
  reservations r
JOIN
  users u ON r.user_id = u.id
JOIN
  apartments a ON r.apartment_id = a.id
LEFT JOIN
  users o ON a.owner_id = o.id;

CREATE VIEW `landlord_apartments_view` AS
SELECT
  a.id,
  a.name,
  a.location,
  a.description,
  a.price,
  a.available,
  a.bedrooms,
  a.bathrooms,
  a.furnished,
  a.pets_allowed,
  a.parking,
  a.internet,
  a.created_at,
  a.updated_at,
  t.id AS tenant_id,
  t.name AS tenant_name,
  t.lastname AS tenant_lastname,
  t.email AS tenant_email,
  t.phone AS tenant_phone
FROM
  apartments a
LEFT JOIN
  users t ON a.tenant_id = t.id;

CREATE VIEW `landlord_reservations_view` AS
SELECT
  r.id AS reservation_id,
  r.reservation_date,
  r.start_date,
  r.end_date,
  r.duration,
  r.status,
  u.id AS user_id,
  u.name AS user_name,
  u.lastname AS user_lastname,
  u.email AS user_email,
  u.phone AS user_phone,
  a.id AS apartment_id,
  a.name AS apartment_name,
  a.location AS apartment_location,
  a.price AS apartment_price
FROM
  reservations r
JOIN
  users u ON r.user_id = u.id
JOIN
  apartments a ON r.apartment_id = a.id; 