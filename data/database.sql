-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 10 Ağu 2025, 15:13:45
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `clanbossv3`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `clan_bank`
--

CREATE TABLE `clan_bank` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `drop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tax_amount` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `clan_bank`
--

INSERT INTO `clan_bank` (`id`, `event_id`, `drop_id`, `user_id`, `tax_amount`, `created_at`) VALUES
(26, 5, 32, 16, 272727, '2025-08-10 11:20:37'),
(27, 5, 32, 18, 272727, '2025-08-10 11:20:37'),
(28, 5, 32, 24, 272727, '2025-08-10 11:20:37'),
(29, 5, 32, 25, 272727, '2025-08-10 11:20:37'),
(30, 5, 32, 27, 272727, '2025-08-10 11:20:37'),
(31, 5, 32, 29, 272727, '2025-08-10 11:20:37'),
(32, 5, 32, 30, 272727, '2025-08-10 11:20:37'),
(33, 5, 33, 1, 375000, '2025-08-10 11:20:44'),
(34, 5, 33, 5, 375000, '2025-08-10 11:20:44'),
(35, 5, 33, 8, 375000, '2025-08-10 11:20:44'),
(36, 5, 33, 16, 375000, '2025-08-10 11:20:44'),
(37, 5, 33, 18, 375000, '2025-08-10 11:20:44'),
(38, 5, 33, 25, 375000, '2025-08-10 11:20:44'),
(39, 5, 33, 27, 375000, '2025-08-10 11:20:44'),
(40, 5, 33, 29, 375000, '2025-08-10 11:20:44');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `drops`
--

CREATE TABLE `drops` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `item_name` varchar(120) NOT NULL,
  `status` enum('Satıldı','Bekliyor') NOT NULL DEFAULT 'Bekliyor',
  `price` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `status` enum('Kesildi','Kesilmedi') NOT NULL DEFAULT 'Kesilmedi',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `event_participants`
--

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` bigint(20) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payouts`
--

CREATE TABLE `payouts` (
  `id` int(11) NOT NULL,
  `drop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` bigint(20) NOT NULL,
  `gross_amount` bigint(20) DEFAULT NULL,
  `tax_amount` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` tinyint(4) NOT NULL DEFAULT 1,
  `tax_active` tinyint(1) NOT NULL DEFAULT 0,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_start_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `tax_active`, `tax_percent`, `tax_start_date`, `updated_at`) VALUES
(1, 1, 3.00, '2025-08-10', '2025-08-10 10:47:24');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `job` enum('Rogue','Priest','Mage','Warrior') NOT NULL DEFAULT 'Warrior',
  `role` enum('Admin','Paydaş','Üye') NOT NULL DEFAULT 'Üye',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `job`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Rogue', 'Admin', 1, '2025-08-09 16:37:28'),
(5, 'EROM', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Priest', 'Üye', 1, '2025-08-09 20:09:41'),
(6, 'Glorfindel', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(7, 'VOLTA', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Rogue', 'Üye', 1, '2025-08-09 20:09:41'),
(8, 'sSuwaRRi', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Priest', 'Paydaş', 1, '2025-08-09 20:09:41'),
(9, 'MeLancholy', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(10, 'VagonNecmi', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Mage', 'Üye', 1, '2025-08-09 20:09:41'),
(11, 'ThcDesigner', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(12, 'PARS21', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 0, '2025-08-09 20:09:41'),
(13, 'XouS', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Rogue', 'Üye', 1, '2025-08-09 20:09:41'),
(14, 'SouX', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Mage', 'Üye', 1, '2025-08-09 20:09:41'),
(15, 'Zniper', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Mage', 'Üye', 1, '2025-08-09 20:09:41'),
(16, 'CKO1O', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(17, 'TwitchDoktorBrokoli', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(18, 'HastalavistaA', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Priest', 'Üye', 1, '2025-08-09 20:09:41'),
(19, 'TeeeRSoooo', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Mage', 'Üye', 1, '2025-08-09 20:09:41'),
(20, 'wee1za', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Priest', 'Üye', 1, '2025-08-09 20:09:41'),
(21, '__x___BALTACI___x__', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Rogue', 'Üye', 1, '2025-08-09 20:09:41'),
(22, '_GASTRONOM_', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Mage', 'Üye', 1, '2025-08-09 20:09:41'),
(23, 'SuvaRi_ParSs', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Priest', 'Üye', 1, '2025-08-09 20:09:41'),
(24, 'NebyS', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Mage', 'Üye', 1, '2025-08-09 20:09:41'),
(25, 'IGojiral', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Mage', 'Üye', 1, '2025-08-09 20:09:41'),
(26, 'fcdkami', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(27, 'LaSTBucali', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Rogue', 'Üye', 1, '2025-08-09 20:09:41'),
(28, 'Xniz', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(29, 'ImdDozer', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(30, 'ParSsBey', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Warrior', 'Üye', 1, '2025-08-09 20:09:41'),
(31, 'BurryBerry', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Priest', 'Üye', 1, '2025-08-09 20:09:41'),
(32, 'patakute', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Priest', 'Üye', 1, '2025-08-09 20:09:41'),
(33, 'FastHands', '$2y$10$pPsH9Sj6f6Zygi6DVxWO3u2vjU3o.foH2f4UhSlfLSs2LtCH7Gu6S', 'Rogue', 'Üye', 1, '2025-08-09 20:09:41');

--
-- Tetikleyiciler `users`
--
DELIMITER $$
CREATE TRIGGER `prevent_admin_delete` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
  IF OLD.role = 'Admin' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Admin kullanıcı silinemez';
  END IF;
END
$$
DELIMITER ;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `clan_bank`
--
ALTER TABLE `clan_bank`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `drops`
--
ALTER TABLE `drops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_drop_event` (`event_id`);

--
-- Tablo için indeksler `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_date` (`event_date`);

--
-- Tablo için indeksler `event_participants`
--
ALTER TABLE `event_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_event_user` (`event_id`,`user_id`),
  ADD KEY `fk_ep_user` (`user_id`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pay_user` (`user_id`),
  ADD KEY `fk_pay_creator` (`created_by`);

--
-- Tablo için indeksler `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payout_drop` (`drop_id`),
  ADD KEY `fk_payout_user` (`user_id`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `clan_bank`
--
ALTER TABLE `clan_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Tablo için AUTO_INCREMENT değeri `drops`
--
ALTER TABLE `drops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Tablo için AUTO_INCREMENT değeri `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `payouts`
--
ALTER TABLE `payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `drops`
--
ALTER TABLE `drops`
  ADD CONSTRAINT `fk_drop_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `event_participants`
--
ALTER TABLE `event_participants`
  ADD CONSTRAINT `fk_ep_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `payouts`
--
ALTER TABLE `payouts`
  ADD CONSTRAINT `fk_payout_drop` FOREIGN KEY (`drop_id`) REFERENCES `drops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payout_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
