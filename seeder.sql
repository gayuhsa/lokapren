-- Lokapren demo database — seeded baseline (DemoSeeder output)
-- Import this whole file in phpMyAdmin; the two lines below target `lokapren`.

CREATE DATABASE IF NOT EXISTS `lokapren` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lokapren`;

-- MariaDB dump 10.19-11.8.8-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: lokapren
-- ------------------------------------------------------
-- Server version	11.8.8-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `recipient_name` varchar(150) NOT NULL,
  `recipient_phone` varchar(25) DEFAULT NULL,
  `address_line` varchar(255) NOT NULL,
  `village` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `regency` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `landmark` varchar(150) DEFAULT NULL,
  `delivery_notes` varchar(255) DEFAULT NULL,
  `is_hotel` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_is_default` (`user_id`,`is_default`),
  KEY `district` (`district`),
  KEY `regency` (`regency`),
  KEY `province` (`province`),
  CONSTRAINT `addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES
(1,4,'Rumah','Sari Wulandari','081223344556','Jl. Kaliurang KM 6 No. 12, Condongcatur','Condongcatur','Depok','Sleman','DI Yogyakarta','55283',-7.7467000,110.4051000,'UGM Gate 3','Rumah pagar hijau, titip ke satpam bila kosong.',0,1,'2026-10-07 20:09:41','2026-10-07 20:09:41',NULL);
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `auth_groups_users`
--

DROP TABLE IF EXISTS `auth_groups_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_groups_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `group` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `auth_groups_users_user_id_foreign` (`user_id`),
  CONSTRAINT `auth_groups_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_groups_users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth_groups_users` WRITE;
/*!40000 ALTER TABLE `auth_groups_users` DISABLE KEYS */;
INSERT INTO `auth_groups_users` VALUES
(1,1,'seller','2026-10-07 20:09:29'),
(2,2,'seller','2026-10-07 20:09:32'),
(3,3,'seller','2026-10-07 20:09:35'),
(4,4,'customer','2026-10-07 20:09:37');
/*!40000 ALTER TABLE `auth_groups_users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `auth_identities`
--

DROP TABLE IF EXISTS `auth_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_identities` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `secret` varchar(255) NOT NULL,
  `secret2` varchar(255) DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `extra` text DEFAULT NULL,
  `force_reset` tinyint(1) NOT NULL DEFAULT 0,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_secret` (`type`,`secret`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `auth_identities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_identities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth_identities` WRITE;
/*!40000 ALTER TABLE `auth_identities` DISABLE KEYS */;
INSERT INTO `auth_identities` VALUES
(1,1,'email_password',NULL,'gebyok@lokapren.test','$2y$12$gcU4Jz5AffOGxbzKQOwkiOL.vy2KDsKU1hDpIfLeEkSlo.dydHlqG',NULL,NULL,0,NULL,'2026-10-07 20:09:28','2026-10-07 20:09:29'),
(2,2,'email_password',NULL,'tenun@lokapren.test','$2y$12$UTxFF8QObjXhNsR8WvT28eHXhtnDliCIKyZ5DbMKT2ajx4t0icZ4.',NULL,NULL,0,NULL,'2026-10-07 20:09:30','2026-10-07 20:09:32'),
(3,3,'email_password',NULL,'keramik@lokapren.test','$2y$12$BrJFfR6fobXKr/RgGGoqJOltKbDObU2cWr0cMJ/EPxQrFpp2d8rf2',NULL,NULL,0,NULL,'2026-10-07 20:09:33','2026-10-07 20:09:35'),
(4,4,'email_password',NULL,'sari@lokapren.test','$2y$12$L4JdiSlBPyH2FiRpTM2JNeIsUx4EXHoUeOoCAiQSntc9XXHfguoje',NULL,NULL,0,NULL,'2026-10-07 20:09:36','2026-10-07 20:09:37');
/*!40000 ALTER TABLE `auth_identities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `auth_logins`
--

DROP TABLE IF EXISTS `auth_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_logins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `id_type` varchar(255) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `date` datetime NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_type_identifier` (`id_type`,`identifier`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_logins`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth_logins` WRITE;
/*!40000 ALTER TABLE `auth_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_logins` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `auth_permissions_users`
--

DROP TABLE IF EXISTS `auth_permissions_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_permissions_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `permission` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `auth_permissions_users_user_id_foreign` (`user_id`),
  CONSTRAINT `auth_permissions_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_permissions_users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth_permissions_users` WRITE;
/*!40000 ALTER TABLE `auth_permissions_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_permissions_users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `auth_remember_tokens`
--

DROP TABLE IF EXISTS `auth_remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_remember_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `selector` varchar(255) NOT NULL,
  `hashedValidator` varchar(255) NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `auth_remember_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `auth_remember_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_remember_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth_remember_tokens` WRITE;
/*!40000 ALTER TABLE `auth_remember_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `auth_token_logins`
--

DROP TABLE IF EXISTS `auth_token_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_token_logins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `id_type` varchar(255) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `date` datetime NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_type_identifier` (`id_type`,`identifier`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_token_logins`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth_token_logins` WRITE;
/*!40000 ALTER TABLE `auth_token_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_token_logins` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `blog_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_categories` WRITE;
/*!40000 ALTER TABLE `blog_categories` DISABLE KEYS */;
INSERT INTO `blog_categories` VALUES
(1,NULL,'Proses Produksi','proses'),
(2,NULL,'Tips & Perawatan','tips'),
(3,NULL,'Berita Sanggar','berita');
/*!40000 ALTER TABLE `blog_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) unsigned NOT NULL,
  `category_id` int(11) unsigned DEFAULT NULL,
  `slug` varchar(150) NOT NULL,
  `title` varchar(200) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `cover_path` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) unsigned NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `seller_id_status` (`seller_id`,`status`),
  KEY `category_id` (`category_id`),
  KEY `published_at` (`published_at`),
  CONSTRAINT `blog_posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `blog_posts_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
INSERT INTO `blog_posts` VALUES
(1,1,1,'dari-balik-pahatan-satu-minggu-gebyok','Dari Balik Pahatan: Satu Minggu Mengerjakan Gebyok','Catatan harian sanggar selama mengerjakan satu gebyok enam daun, dari menggaris hingga perakitan.','Hari pertama selalu dipakai untuk menggaris. Garis tidak boleh salah karena pahat akan mengikuti.\n\nSelama enam hari berikutnya motif naga dibentuk pelan-pelan. Yang paling lama adalah bagian sisik, karena setiap sisik harus dalam dengan kedalaman yang sama supaya bayangan cahayanya rata.\n\nHari terakhir dipakai untuk amplas dan melamik lapis pertama.','demo/blog/dari-balik-pahatan.png','published',1,512,'2026-09-25 20:09:41','2026-09-23 20:09:41','2026-10-07 20:09:41',NULL),
(2,1,2,'merawat-kriya-kayu-di-musim-hujan','Merawat Kriya Kayu Agar Tidak Retak di Musim Hujan','Tiga kebiasaan sederhana yang menjaga kayu tetap stabil saat kelembapan naik.','Jangan menempelkan kriya kayu langsung ke dinding lembap; sisakan jarak minimal dua sentimeter supaya sirkulasi udara tetap jalan.\n\nLap debu dengan kain lembap, jangan basah. Air yang meresap akan membuat serat kayu membengkak dan kering tidak rata.\n\nSetiap enam bulan, oleskan wax tipis-tipis pada permukaan yang sering disentuh.','demo/blog/merawat-kriya-kayu.png','published',1,344,'2026-09-10 20:09:41','2026-09-08 20:09:41','2026-10-07 20:09:41',NULL),
(3,2,3,'tenun-magelang-pameran-kriya-nusantara','Tenun Magelang Masuk Pameran Kriya Nusantara','Tujuh kain pilihan kami dibawa ke pameran tahunan di Jakarta pada bulan depan.','Pameran Kriya Nusantara tahun ini memilih dua puluh sanggar dari luar Jawa dan Jawa Tengah, dan Tenun Magelang termasuk di dalamnya.\n\nKami akan membawa tujuh kain, dua di antaranya masih dalam proses penyelesaian pinggiran. Pengunjung bisa melihat alat tenun kami bekerja langsung di stan.','demo/blog/pameran-kriya-nusantara.png','published',1,189,'2026-10-02 20:09:41','2026-09-30 20:09:41','2026-10-07 20:09:41',NULL),
(4,2,1,'catatan-pewarnaan-alami','Catatan Pewarnaan Alami (draf)','Masih menunggu hasil uji cuci ketiga.','Draf: hasil uji cuci ketiga belum selesai.',NULL,'draft',0,0,NULL,'2026-10-03 20:09:41','2026-10-07 20:09:41',NULL);
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint(20) unsigned NOT NULL,
  `seller_id` int(11) unsigned NOT NULL,
  `product_id` int(11) unsigned NOT NULL,
  `variant_id` int(11) unsigned DEFAULT NULL,
  `quantity` int(11) unsigned NOT NULL,
  `unit_price` bigint(15) unsigned NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_id_product_id_variant_id` (`cart_id`,`product_id`,`variant_id`),
  KEY `seller_id` (`seller_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES
(1,1,3,7,NULL,1,475000,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41');
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `currency` char(3) NOT NULL DEFAULT 'IDR',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  CONSTRAINT `carts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES
(1,4,'active','IDR','2026-10-07 20:09:41');
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) unsigned NOT NULL,
  `seller_id` int(11) unsigned NOT NULL,
  `product_id` int(11) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `last_message_at` datetime DEFAULT NULL,
  `last_message_preview` varchar(255) DEFAULT NULL,
  `customer_unread_count` int(11) unsigned NOT NULL DEFAULT 0,
  `seller_unread_count` int(11) unsigned NOT NULL DEFAULT 0,
  `customer_archived_at` datetime DEFAULT NULL,
  `seller_archived_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id_seller_id_product_id` (`customer_id`,`seller_id`,`product_id`),
  KEY `customer_id_last_message_at` (`customer_id`,`last_message_at`),
  KEY `seller_id_last_message_at` (`seller_id`,`last_message_at`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `conversations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `sender_id` int(11) unsigned NOT NULL,
  `message_type` varchar(20) NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(150) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL,
  `attachment_size` int(11) unsigned DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id_created_at` (`conversation_id`,`created_at`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'2020-12-28-223112','CodeIgniter\\Shield\\Database\\Migrations\\CreateAuthTables','default','CodeIgniter\\Shield',1791378565,1),
(2,'2021-07-04-041948','CodeIgniter\\Settings\\Database\\Migrations\\CreateSettingsTable','default','CodeIgniter\\Settings',1791378565,1),
(3,'2021-11-14-143905','CodeIgniter\\Settings\\Database\\Migrations\\AddContextColumn','default','CodeIgniter\\Settings',1791378565,1),
(4,'2026-07-20-181246','CodeIgniter\\Settings\\Database\\Migrations\\ConvertSqlsrvValueColumn','default','CodeIgniter\\Settings',1791378565,1),
(5,'2026-10-05-000001','App\\Database\\Migrations\\CreateMarketplaceIdentityAndSellerTables','default','App',1791378565,1),
(6,'2026-10-05-000002','App\\Database\\Migrations\\CreateMarketplaceCatalogTables','default','App',1791378565,1),
(7,'2026-10-05-000003','App\\Database\\Migrations\\CreateMarketplaceProfileAndContentTables','default','App',1791378565,1),
(8,'2026-10-05-000004','App\\Database\\Migrations\\CreateMarketplaceCommerceTables','default','App',1791378565,1),
(9,'2026-10-05-000005','App\\Database\\Migrations\\CreateMarketplaceChatTables','default','App',1791378565,1),
(10,'2026-10-05-000006','App\\Database\\Migrations\\CreateMarketplaceReviewAndAnalyticsTables','default','App',1791378565,1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` int(11) unsigned NOT NULL,
  `variant_id` int(11) unsigned DEFAULT NULL,
  `product_name` varchar(180) NOT NULL,
  `variant_label` varchar(100) DEFAULT NULL,
  `sku` varchar(64) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `unit_price` bigint(15) unsigned NOT NULL,
  `quantity` int(11) unsigned NOT NULL,
  `subtotal` bigint(15) unsigned NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id_product_id` (`order_id`,`product_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `order_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES
(1,1,1,NULL,'Gebyok Jati Ukir Naga',NULL,NULL,'demo/products/gebyok-jati-ukir-naga-0.png',4500000,1,4500000,'Mohon diplester dulu sebelum dikirim.','2026-09-21 20:09:41'),
(2,1,2,NULL,'Relif Ukir Motif Klasik',NULL,NULL,'demo/products/relif-ukir-motif-klasik-0.png',1250000,2,2500000,NULL,'2026-09-21 20:09:41'),
(3,2,4,NULL,'Tenun Ikat Sogan Magelang',NULL,NULL,'demo/products/tenun-ikat-sogan-magelang-0.png',850000,1,850000,NULL,'2026-09-26 20:09:41'),
(4,2,5,NULL,'Selendang Tenun Eksklusif',NULL,NULL,'demo/products/selendang-tenun-eksklusif-0.png',650000,1,650000,NULL,'2026-09-26 20:09:41'),
(5,3,6,NULL,'Teko Keramik Daur Ulang',NULL,NULL,'demo/products/teko-keramik-daur-ulang-0.png',320000,2,640000,'Bisa dibungkus kado?','2026-09-29 20:09:41'),
(6,4,6,NULL,'Teko Keramik Daur Ulang',NULL,NULL,'demo/products/teko-keramik-daur-ulang-0.png',320000,2,640000,NULL,'2026-10-04 20:09:41');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `order_shipments`
--

DROP TABLE IF EXISTS `order_shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_shipments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `courier_code` varchar(20) DEFAULT NULL,
  `courier_name` varchar(100) DEFAULT NULL,
  `service_level` varchar(30) DEFAULT NULL,
  `tracking_number` varchar(60) DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `tracking_number` (`tracking_number`),
  CONSTRAINT `order_shipments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_shipments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `order_shipments` WRITE;
/*!40000 ALTER TABLE `order_shipments` DISABLE KEYS */;
INSERT INTO `order_shipments` VALUES
(1,1,'jne','JNE Regular','REG','JNE001234567890','2026-09-27 20:09:41','2026-09-28 20:09:41','2026-09-27 20:09:41'),
(2,2,'jnt','J&T Express','EZ','JT881234567890','2026-10-02 20:09:41','2026-10-03 20:09:41','2026-10-02 20:09:41'),
(3,3,'sicepat','SiCepat Regular','REG','SC009876543210','2026-10-05 20:09:41','2026-10-06 20:09:41','2026-10-05 20:09:41');
/*!40000 ALTER TABLE `order_shipments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(20) DEFAULT NULL,
  `to_status` varchar(20) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `actor_id` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id_created_at` (`order_id`,`created_at`),
  KEY `actor_id` (`actor_id`),
  CONSTRAINT `order_status_history_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_status_history_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `order_status_history` WRITE;
/*!40000 ALTER TABLE `order_status_history` DISABLE KEYS */;
INSERT INTO `order_status_history` VALUES
(1,1,NULL,'pending_payment','Pesanan dibuat.',4,'2026-09-21 20:09:41'),
(2,1,'pending_payment','awaiting_artisan','Pembayaran diterima, pesanan diteruskan ke sanggar.',4,'2026-09-21 20:09:41'),
(3,1,'awaiting_artisan','in_production','Mulai dikerjakan di sanggar.',1,'2026-09-21 23:09:41'),
(4,1,'in_production','ready_to_ship','Selesai dan siap dikemas.',1,'2026-09-26 20:09:41'),
(5,1,'ready_to_ship','shipped','Diserahkan ke JNE Regular.',1,'2026-09-27 20:09:41'),
(6,1,'shipped','delivered','Diterima penerima di Sleman.',4,'2026-09-28 20:09:41'),
(7,1,'delivered','completed','Pesanan diselesaikan.',4,'2026-09-29 20:09:41'),
(8,2,NULL,'pending_payment','Pesanan dibuat.',4,'2026-09-26 20:09:41'),
(9,2,'pending_payment','awaiting_artisan','Pembayaran diterima, pesanan diteruskan ke sanggar.',4,'2026-09-26 20:09:41'),
(10,2,'awaiting_artisan','in_production','Mulai dikerjakan di sanggar.',2,'2026-09-26 23:09:41'),
(11,2,'in_production','ready_to_ship','Selesai dan siap dikemas.',2,'2026-10-01 20:09:41'),
(12,2,'ready_to_ship','shipped','Diserahkan ke J&T Express.',2,'2026-10-02 20:09:41'),
(13,2,'shipped','delivered','Diterima penerima di Sleman.',4,'2026-10-03 20:09:41'),
(14,2,'delivered','completed','Pesanan diselesaikan.',4,'2026-10-04 20:09:41'),
(15,3,NULL,'pending_payment','Pesanan dibuat.',4,'2026-09-29 20:09:41'),
(16,3,'pending_payment','awaiting_artisan','Pembayaran diterima, pesanan diteruskan ke sanggar.',4,'2026-09-29 20:09:41'),
(17,3,'awaiting_artisan','in_production','Mulai dikerjakan di sanggar.',3,'2026-09-29 23:09:41'),
(18,3,'in_production','ready_to_ship','Selesai dan siap dikemas.',3,'2026-10-04 20:09:41'),
(19,3,'ready_to_ship','shipped','Diserahkan ke SiCepat Regular.',3,'2026-10-05 20:09:41'),
(20,3,'shipped','delivered','Diterima penerima di Sleman.',4,'2026-10-06 20:09:41'),
(21,3,'delivered','completed','Pesanan diselesaikan.',4,'2026-10-07 20:09:41'),
(22,4,NULL,'pending_payment','Pesanan dibuat.',4,'2026-10-04 20:09:41'),
(23,4,'pending_payment','awaiting_artisan','Pembayaran diterima.',4,'2026-10-04 20:09:41'),
(24,4,'awaiting_artisan','in_production','Sedang dibakar pada tungku pertama.',3,'2026-10-05 20:09:41');
/*!40000 ALTER TABLE `order_status_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(32) NOT NULL,
  `customer_id` int(11) unsigned NOT NULL,
  `seller_id` int(11) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending_payment',
  `fulfillment_type` varchar(20) NOT NULL DEFAULT 'ship',
  `production_progress` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `payment_method` varchar(20) DEFAULT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `currency` char(3) NOT NULL DEFAULT 'IDR',
  `subtotal` bigint(15) unsigned NOT NULL DEFAULT 0,
  `shipping_total` bigint(15) unsigned NOT NULL DEFAULT 0,
  `service_total` bigint(15) unsigned NOT NULL DEFAULT 0,
  `tax_total` bigint(15) unsigned NOT NULL DEFAULT 0,
  `donation_total` bigint(15) unsigned NOT NULL DEFAULT 0,
  `grand_total` bigint(15) unsigned NOT NULL DEFAULT 0,
  `platform_fee` bigint(15) unsigned NOT NULL DEFAULT 0,
  `seller_earning` bigint(15) unsigned NOT NULL DEFAULT 0,
  `ship_recipient_name` varchar(150) DEFAULT NULL,
  `ship_recipient_phone` varchar(25) DEFAULT NULL,
  `ship_address_line` varchar(255) DEFAULT NULL,
  `ship_village` varchar(100) DEFAULT NULL,
  `ship_district` varchar(100) DEFAULT NULL,
  `ship_regency` varchar(100) DEFAULT NULL,
  `ship_province` varchar(100) DEFAULT NULL,
  `ship_postal_code` varchar(10) DEFAULT NULL,
  `ship_latitude` decimal(10,7) DEFAULT NULL,
  `ship_longitude` decimal(10,7) DEFAULT NULL,
  `ship_landmark` varchar(150) DEFAULT NULL,
  `ship_notes` varchar(255) DEFAULT NULL,
  `courier_code` varchar(20) DEFAULT NULL,
  `courier_name` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(60) DEFAULT NULL,
  `estimated_delivery_at` date DEFAULT NULL,
  `customer_note` varchar(500) DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `placed_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `customer_id_status` (`customer_id`,`status`),
  KEY `seller_id_status` (`seller_id`,`status`),
  KEY `status` (`status`),
  KEY `placed_at` (`placed_at`),
  KEY `ship_district` (`ship_district`),
  KEY `ship_regency` (`ship_regency`),
  KEY `ship_province` (`ship_province`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(1,'LKP-2026-000140',4,1,'completed','ship',100,'manual','paid','IDR',7000000,350000,0,0,0,7350000,350000,7000000,'Sari Wulandari','081223344556','Jl. Kaliurang KM 6 No. 12, Condongcatur','Condongcatur','Depok','Sleman','DI Yogyakarta','55283',-7.7467000,110.4051000,'UGM Gate 3','Rumah pagar hijau, titip ke satpam bila kosong.','jne','JNE Regular','JNE001234567890','2026-09-28','Mohon diplester dulu sebelum dikirim.',NULL,'2026-09-21 20:09:41','2026-09-21 20:09:41','2026-09-27 20:09:41','2026-09-28 20:09:41','2026-09-29 20:09:41',NULL,'2026-09-21 20:09:41','2026-10-07 20:09:41'),
(2,'LKP-2026-000141',4,2,'completed','ship',100,'manual','paid','IDR',1500000,60000,0,0,0,1560000,75000,1485000,'Sari Wulandari','081223344556','Jl. Kaliurang KM 6 No. 12, Condongcatur','Condongcatur','Depok','Sleman','DI Yogyakarta','55283',-7.7467000,110.4051000,'UGM Gate 3','Rumah pagar hijau, titip ke satpam bila kosong.','jnt','J&T Express','JT881234567890','2026-10-03',NULL,NULL,'2026-09-26 20:09:41','2026-09-26 20:09:41','2026-10-02 20:09:41','2026-10-03 20:09:41','2026-10-04 20:09:41',NULL,'2026-09-26 20:09:41','2026-10-07 20:09:41'),
(3,'LKP-2026-000142',4,3,'completed','ship',100,'manual','paid','IDR',640000,45000,0,0,0,685000,32000,653000,'Sari Wulandari','081223344556','Jl. Kaliurang KM 6 No. 12, Condongcatur','Condongcatur','Depok','Sleman','DI Yogyakarta','55283',-7.7467000,110.4051000,'UGM Gate 3','Rumah pagar hijau, titip ke satpam bila kosong.','sicepat','SiCepat Regular','SC009876543210','2026-10-06','Bisa dibungkus kado?',NULL,'2026-09-29 20:09:41','2026-09-29 20:09:41','2026-10-05 20:09:41','2026-10-06 20:09:41','2026-10-07 20:09:41',NULL,'2026-09-29 20:09:41','2026-10-07 20:09:41'),
(4,'LKP-2026-000143',4,3,'in_production','ship',40,'manual','paid','IDR',640000,45000,0,0,0,685000,32000,653000,'Sari Wulandari','081223344556','Jl. Kaliurang KM 6 No. 12, Condongcatur','Condongcatur','Depok','Sleman','DI Yogyakarta','55283',-7.7467000,110.4051000,NULL,NULL,NULL,NULL,NULL,NULL,'Bisa dikirim akhir pekan saja.',NULL,'2026-10-04 20:09:41','2026-10-04 20:09:41',NULL,NULL,NULL,NULL,'2026-10-04 20:09:41','2026-10-07 20:09:41');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `craft_type` varchar(30) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id_position` (`parent_id`,`position`),
  KEY `is_active_position` (`is_active`,`position`),
  CONSTRAINT `product_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES
(1,NULL,'Ukiran Kayu','ukiran-kayu',NULL,1,1),
(2,1,'Gebyok','gebyok',NULL,1,1),
(3,1,'Relief','relief',NULL,2,1),
(4,NULL,'Tenun','tenun',NULL,2,1),
(5,4,'Tenun Ikat','tenun-ikat',NULL,1,1),
(6,NULL,'Keramik','keramik',NULL,3,1),
(7,NULL,'Anyaman','anyaman',NULL,4,1);
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) unsigned NOT NULL,
  `variant_id` int(11) unsigned DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumb_path` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id_position` (`product_id`,`position`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_images_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES
(1,1,NULL,'demo/products/gebyok-jati-ukir-naga-0.png',NULL,'Gebyok Jati Ukir Naga','Pintu penyekat ruang tamu, ukir naga',1,0,'2026-10-07 20:09:39'),
(2,1,NULL,'demo/products/gebyok-jati-ukir-naga-1.png',NULL,'Gebyok Jati Ukir Naga',NULL,0,1,'2026-10-07 20:09:39'),
(3,2,NULL,'demo/products/relif-ukir-motif-klasik-0.png',NULL,'Relif Ukir Motif Klasik','Panel dinding 60x90 cm',1,0,'2026-10-07 20:09:39'),
(4,3,NULL,'demo/products/gebyok-pintu-ganda-minimalis-0.png',NULL,'Gebyok Pintu Ganda Minimalis','Dibuat sesuai pesanan',1,0,'2026-10-07 20:09:40'),
(5,4,NULL,'demo/products/tenun-ikat-sogan-magelang-0.png',NULL,'Tenun Ikat Sogan Magelang','Sutera, warna alami',1,0,'2026-10-07 20:09:40'),
(6,4,NULL,'demo/products/tenun-ikat-sogan-magelang-1.png',NULL,'Tenun Ikat Sogan Magelang',NULL,0,1,'2026-10-07 20:09:40'),
(7,5,NULL,'demo/products/selendang-tenun-eksklusif-0.png',NULL,'Selendang Tenun Eksklusif','200x60 cm',1,0,'2026-10-07 20:09:40'),
(8,6,NULL,'demo/products/teko-keramik-daur-ulang-0.png',NULL,'Teko Keramik Daur Ulang','Kapasitas 900 ml',1,0,'2026-10-07 20:09:40'),
(9,7,NULL,'demo/products/vas-keramik-tone-on-tone-0.png',NULL,'Vas Keramik Tone-on-Tone','Tinggi 24 cm',1,0,'2026-10-07 20:09:41');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) unsigned NOT NULL,
  `variant_code` varchar(32) NOT NULL,
  `sku` varchar(64) DEFAULT NULL,
  `label` varchar(100) NOT NULL,
  `price` bigint(15) unsigned DEFAULT NULL,
  `stock` int(11) unsigned NOT NULL DEFAULT 0,
  `weight_gram` int(11) unsigned DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `variant_code` (`variant_code`),
  UNIQUE KEY `sku` (`sku`),
  KEY `product_id_is_active_position` (`product_id`,`is_active`,`position`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES
(1,4,'V-10004-A','TENUN-SOGAN-200','Panjang 200 cm',NULL,8,450,0,1,0,'2026-10-07 20:09:40','2026-10-07 20:09:40'),
(2,4,'V-10004-B','TENUN-SOGAN-250','Panjang 250 cm',150000,6,680,1,1,1,'2026-10-07 20:09:40','2026-10-07 20:09:40');
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) unsigned NOT NULL,
  `category_id` int(11) unsigned DEFAULT NULL,
  `product_code` varchar(32) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `name` varchar(180) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `story` text DEFAULT NULL,
  `material` varchar(150) DEFAULT NULL,
  `finishing` varchar(150) DEFAULT NULL,
  `price` bigint(15) unsigned NOT NULL,
  `stock` int(11) unsigned NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) unsigned NOT NULL DEFAULT 0,
  `weight_gram` int(11) unsigned DEFAULT NULL,
  `made_to_order` tinyint(1) NOT NULL DEFAULT 0,
  `production_days` smallint(5) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `rating_average` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(11) unsigned NOT NULL DEFAULT 0,
  `sold_count` int(11) unsigned NOT NULL DEFAULT 0,
  `orders_count` int(11) unsigned NOT NULL DEFAULT 0,
  `view_count` int(11) unsigned NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `seller_id` (`seller_id`),
  KEY `category_id` (`category_id`),
  KEY `status_is_active` (`status`,`is_active`),
  KEY `is_featured_sold_count` (`is_featured`,`sold_count`),
  KEY `price` (`price`),
  KEY `rating_average` (`rating_average`),
  KEY `published_at` (`published_at`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,1,2,'P-10001','gebyok-jati-ukir-naga','Gebyok Jati Ukir Naga','Pintu penyekat ruang tamu, ukir naga','Gebyok enam daun dari jati perhutani dengan motif naga yang dipahat tangan.','Gebyok enam daun dengan tinggi 220 cm dan lebar 300 cm. Rangka dibuat dari jati perhutani berumur minimal 40 tahun, dikeringkan delapan bulan, lalu dipahat langsung tanpa cetakan.\n\nHarga sudah termasuk perakitan di tempat untuk wilayah Magelang dan sekitarnya.','Motif naga ini diajarkan Pak Suryanto kepada anak-anak sanggar sejak 1998, dan hampir tidak berubah sampai sekarang.','Jati perhutani grade A','Melamik doff dua lapis',4500000,2,1,85000,0,NULL,'published',1,1,5.00,1,1,1,412,'2026-10-06 20:09:39','2026-10-01 20:09:39','2026-10-07 20:09:39',NULL),
(2,1,3,'P-10002','relif-ukir-motif-klasik','Relif Ukir Motif Klasik','Panel dinding 60x90 cm','Panel relif jati untuk dinding kantor atau ruang keluarga.','Panel relif berukuran 60x90 cm dengan bingkai kayu jati. Siap dipasang, sudah dilubangi pengait di bagian belakang.',NULL,'Jati perhutani','Walur alami',1250000,5,2,12000,0,NULL,'published',1,0,0.00,0,2,1,176,'2026-10-05 20:09:39','2026-09-30 20:09:39','2026-10-07 20:09:39',NULL),
(3,1,2,'P-10003','gebyok-pintu-ganda-minimalis','Gebyok Pintu Ganda Minimalis','Dibuat sesuai pesanan','Gebyok dua daun bergaya minimalis, dikerjakan setelah pesanan masuk.','Gebyok dua daun untuk pintu masuk utama. Ukuran dan motif disesuaikan dengan lebar ruang Anda setelah survey lokasi.',NULL,'Jati perhutani','Melamik semi doff',6750000,0,0,92000,1,30,'published',1,0,0.00,0,0,0,98,'2026-10-04 20:09:39','2026-09-29 20:09:39','2026-10-07 20:09:39',NULL),
(4,2,5,'P-10004','tenun-ikat-sogan-magelang','Tenun Ikat Sogan Magelang','Sutera, warna alami','Kain tenun ikat sutera dengan pewarnaan alami daun tomentosa.','Kain tenun ikat berbahan sutera, ditenun tangan dengan alat tenun BUK. Tersedia dua ukuran panjang.','Setiap helai dicelup tiga kali agar warna sogan tetap pekat setelah beberapa kali dicuci.','Sutera alami','Renda tangan',850000,8,3,450,0,NULL,'published',1,1,5.00,1,1,1,305,'2026-10-03 20:09:40','2026-09-28 20:09:40','2026-10-07 20:09:40',NULL),
(5,2,5,'P-10005','selendang-tenun-eksklusif','Selendang Tenun Eksklusif','200x60 cm','Selendang tenun bermotif parang untuk acara resmi.','Selendang tenun ukuran 200x60 cm dengan motif parang dan pinggiran berumbai.',NULL,'Sutera dan katun','Pinggiran berumbai',650000,12,4,280,0,NULL,'published',1,0,0.00,0,1,1,141,'2026-10-02 20:09:40','2026-09-27 20:09:40','2026-10-07 20:09:40',NULL),
(6,3,6,'P-10006','teko-keramik-daur-ulang','Teko Keramik Daur Ulang','Kapasitas 900 ml','Teo teh dari tanah liat daur ulang dengan glasir matte.','Teo teh berkapasitas 900 ml, aman untuk mesin cuci piring dan microwave. Glasir matte tidak mudah tergores.',NULL,'Tanah liat daur ulang','Glasir matte',320000,20,5,620,0,NULL,'published',1,1,5.00,1,2,2,268,'2026-10-01 20:09:40','2026-09-26 20:09:40','2026-10-07 20:09:40',NULL),
(7,3,6,'P-10007','vas-keramik-tone-on-tone','Vas Keramik Tone-on-Tone','Tinggi 24 cm','Vas bunga dengan glasir satu warna bertekstur halus.','Vas setinggi 24 cm dengan glasir satu warna. Cocok untuk bunga kering maupun segar.',NULL,'Porselen','Glasir bertekstur',475000,15,3,900,0,NULL,'published',1,0,0.00,0,0,0,122,'2026-09-30 20:09:40','2026-09-25 20:09:40','2026-10-07 20:09:40',NULL),
(8,3,7,'P-10008','tas-anyaman-bambu-magelang','Tas Anyaman Bambu Magelang','Masih berupa draf','Tas belanja dari bambu muda, menunggu foto akhir.','Tas belanja anyaman bambu muda dari Magelang.',NULL,'Bambu muda','Pernis kayu',185000,0,0,350,0,NULL,'draft',1,0,0.00,0,0,0,0,NULL,'2026-09-24 20:09:41','2026-10-07 20:09:41',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `review_images`
--

DROP TABLE IF EXISTS `review_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumb_path` varchar(255) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_id_position` (`review_id`,`position`),
  CONSTRAINT `review_images_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_images`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `review_images` WRITE;
/*!40000 ALTER TABLE `review_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `review_images` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(11) unsigned NOT NULL,
  `seller_id` int(11) unsigned NOT NULL,
  `rating` tinyint(1) unsigned NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `variant_label` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'published',
  `seller_reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id_product_id` (`order_id`,`product_id`),
  UNIQUE KEY `order_item_id` (`order_item_id`),
  KEY `product_id_status_created_at` (`product_id`,`status`,`created_at`),
  KEY `seller_id_status` (`seller_id`,`status`),
  KEY `customer_id_created_at` (`customer_id`,`created_at`),
  KEY `rating` (`rating`),
  CONSTRAINT `reviews_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES
(1,1,1,1,4,1,5,'Ukirannya rapi sekali','Datang dengan packing kayu, tidak ada lecet sama sekali. Motif naganya persis seperti foto.',NULL,'published',NULL,NULL,'2026-09-29 20:09:41','2026-09-29 20:09:41',NULL),
(2,4,2,3,4,2,5,'Warnanya lebih pekat dari fotonya','Sutera halus dan pinggirannya rapi. Sampai dua hari lebih cepat dari estimasi.',NULL,'published',NULL,NULL,'2026-10-04 20:09:41','2026-10-04 20:09:41',NULL),
(3,6,3,5,4,3,5,'Glasirnya awet','Sudah dicuci puluhan kali warnanya tidak berubah, pegangan teko juga tidak panas.',NULL,'published',NULL,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41',NULL);
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seller_business_hours`
--

DROP TABLE IF EXISTS `seller_business_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_business_hours` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) unsigned NOT NULL,
  `day_of_week` tinyint(1) unsigned NOT NULL,
  `opens_at` time DEFAULT NULL,
  `closes_at` time DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_id_day_of_week` (`seller_id`,`day_of_week`),
  CONSTRAINT `seller_business_hours_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_business_hours`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seller_business_hours` WRITE;
/*!40000 ALTER TABLE `seller_business_hours` DISABLE KEYS */;
INSERT INTO `seller_business_hours` VALUES
(1,1,1,'08:00:00','17:00:00',0),
(2,1,2,'08:00:00','17:00:00',0),
(3,1,3,'08:00:00','17:00:00',0),
(4,1,4,'08:00:00','17:00:00',0),
(5,1,5,'08:00:00','17:00:00',0),
(6,1,6,'08:00:00','14:00:00',0),
(7,1,7,NULL,NULL,1),
(8,2,1,'08:00:00','17:00:00',0),
(9,2,2,'08:00:00','17:00:00',0),
(10,2,3,'08:00:00','17:00:00',0),
(11,2,4,'08:00:00','17:00:00',0),
(12,2,5,'08:00:00','17:00:00',0),
(13,2,6,'09:00:00','13:00:00',0),
(14,2,7,NULL,NULL,1),
(15,3,1,'08:00:00','17:00:00',0),
(16,3,2,'08:00:00','17:00:00',0),
(17,3,3,'08:00:00','17:00:00',0),
(18,3,4,'08:00:00','17:00:00',0),
(19,3,5,'08:00:00','17:00:00',0),
(20,3,6,'08:00:00','14:00:00',0),
(21,3,7,NULL,NULL,1);
/*!40000 ALTER TABLE `seller_business_hours` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seller_daily_stats`
--

DROP TABLE IF EXISTS `seller_daily_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_daily_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) unsigned NOT NULL,
  `stat_date` date NOT NULL,
  `visit_count` int(11) unsigned NOT NULL DEFAULT 0,
  `visitor_count` int(11) unsigned NOT NULL DEFAULT 0,
  `product_view_count` int(11) unsigned NOT NULL DEFAULT 0,
  `chat_started_count` int(11) unsigned NOT NULL DEFAULT 0,
  `order_count` int(11) unsigned NOT NULL DEFAULT 0,
  `revenue_total` bigint(15) unsigned NOT NULL DEFAULT 0,
  `completed_count` int(11) unsigned NOT NULL DEFAULT 0,
  `top_product_id` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_id_stat_date` (`seller_id`,`stat_date`),
  KEY `stat_date` (`stat_date`),
  KEY `top_product_id` (`top_product_id`),
  CONSTRAINT `seller_daily_stats_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seller_daily_stats_top_product_id_foreign` FOREIGN KEY (`top_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_daily_stats`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seller_daily_stats` WRITE;
/*!40000 ALTER TABLE `seller_daily_stats` DISABLE KEYS */;
INSERT INTO `seller_daily_stats` VALUES
(1,1,'2026-09-20',69,57,145,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(2,1,'2026-09-21',60,50,131,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(3,1,'2026-09-22',51,43,117,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(4,1,'2026-09-23',87,71,173,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(5,1,'2026-09-24',78,64,159,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(6,1,'2026-09-25',69,57,145,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(7,1,'2026-09-26',60,50,131,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(8,1,'2026-09-27',51,43,117,2,1,7000000,1,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(9,1,'2026-09-28',87,71,173,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(10,1,'2026-09-29',78,64,159,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(11,1,'2026-09-30',69,57,145,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(12,1,'2026-10-01',60,50,131,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(13,1,'2026-10-02',51,43,117,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(14,1,'2026-10-03',87,71,173,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(15,1,'2026-10-04',78,64,159,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(16,1,'2026-10-05',69,57,145,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(17,1,'2026-10-06',60,50,131,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(18,1,'2026-10-07',52,43,117,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(19,2,'2026-09-20',81,67,165,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(20,2,'2026-09-21',72,60,151,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(21,2,'2026-09-22',63,53,137,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(22,2,'2026-09-23',54,46,123,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(23,2,'2026-09-24',90,74,179,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(24,2,'2026-09-25',81,67,165,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(25,2,'2026-09-26',72,60,151,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(26,2,'2026-09-27',63,53,137,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(27,2,'2026-09-28',54,46,123,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(28,2,'2026-09-29',90,74,179,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(29,2,'2026-09-30',81,67,165,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(30,2,'2026-10-01',72,60,151,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(31,2,'2026-10-02',63,53,137,3,1,1485000,1,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(32,2,'2026-10-03',54,46,123,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(33,2,'2026-10-04',90,74,179,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(34,2,'2026-10-05',81,67,165,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(35,2,'2026-10-06',72,60,151,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(36,2,'2026-10-07',64,53,138,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(37,3,'2026-09-20',93,77,185,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(38,3,'2026-09-21',84,70,171,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(39,3,'2026-09-22',75,63,157,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(40,3,'2026-09-23',66,56,143,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(41,3,'2026-09-24',57,49,129,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(42,3,'2026-09-25',93,77,185,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(43,3,'2026-09-26',84,70,171,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(44,3,'2026-09-27',75,63,157,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(45,3,'2026-09-28',66,56,143,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(46,3,'2026-09-29',57,49,129,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(47,3,'2026-09-30',93,77,185,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(48,3,'2026-10-01',84,70,171,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(49,3,'2026-10-02',75,63,157,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(50,3,'2026-10-03',66,56,143,3,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(51,3,'2026-10-04',57,49,129,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(52,3,'2026-10-05',93,77,185,3,1,653000,1,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(53,3,'2026-10-06',84,70,171,2,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41'),
(54,3,'2026-10-07',76,63,157,4,0,0,0,NULL,'2026-10-07 20:09:41','2026-10-07 20:09:41');
/*!40000 ALTER TABLE `seller_daily_stats` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seller_facilities`
--

DROP TABLE IF EXISTS `seller_facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_facilities` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) unsigned NOT NULL,
  `facility` varchar(40) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_id_facility` (`seller_id`,`facility`),
  CONSTRAINT `seller_facilities_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_facilities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seller_facilities` WRITE;
/*!40000 ALTER TABLE `seller_facilities` DISABLE KEYS */;
INSERT INTO `seller_facilities` VALUES
(1,1,'galeri','Galeri Karya',0),
(2,1,'parkir_mobil','Parkir Mobil',1),
(3,1,'wifi','WiFi',2),
(4,1,'opsi_takeaway','Bisa Dibeli Offline',3),
(5,2,'kelas_mengamik','Kelas Mengamik',0),
(6,2,'galeri','Galeri Karya',1),
(7,2,'parkir_mobil','Parkir Mobil',2),
(8,2,'wc','Toilet Umum',3),
(9,3,'kelas_melukis','Kelas Melukis',0),
(10,3,'galeri','Galeri Karya',1),
(11,3,'parkir_mobil','Parkir Mobil',2),
(12,3,'wifi','WiFi',3),
(13,3,'wc','Toilet Umum',4);
/*!40000 ALTER TABLE `seller_facilities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seller_media`
--

DROP TABLE IF EXISTS `seller_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_media` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) unsigned NOT NULL,
  `media_type` varchar(20) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `duration_seconds` int(11) unsigned DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_id_media_type_position` (`seller_id`,`media_type`,`position`),
  CONSTRAINT `seller_media_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_media`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seller_media` WRITE;
/*!40000 ALTER TABLE `seller_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_media` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seller_profiles`
--

DROP TABLE IF EXISTS `seller_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_profiles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `slug` varchar(120) NOT NULL,
  `partner_code` varchar(32) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `owner_name` varchar(150) DEFAULT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `craft_focus` varchar(150) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `cover_path` varchar(255) DEFAULT NULL,
  `banner_path` varchar(255) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `regency` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `landmark_name` varchar(150) DEFAULT NULL,
  `landmark_distance_km` decimal(6,2) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_verified_at` datetime DEFAULT NULL,
  `artisan_count` int(11) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_level` varchar(32) DEFAULT NULL,
  `verification_note` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `member_since` date DEFAULT NULL,
  `rating_average` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(11) unsigned NOT NULL DEFAULT 0,
  `sold_count` int(11) unsigned NOT NULL DEFAULT 0,
  `avg_response_minutes` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `partner_code` (`partner_code`),
  KEY `is_active_is_verified` (`is_active`,`is_verified`),
  KEY `district_is_active` (`district`,`is_active`),
  KEY `regency` (`regency`),
  KEY `province` (`province`),
  KEY `latitude_longitude` (`latitude`,`longitude`),
  KEY `rating_average` (`rating_average`),
  CONSTRAINT `seller_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_profiles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seller_profiles` WRITE;
/*!40000 ALTER TABLE `seller_profiles` DISABLE KEYS */;
INSERT INTO `seller_profiles` VALUES
(1,1,'gebyok-mandiri','AB-0001','Gebyok Mandiri','Suryanto','Ukiran kayu jati dari Magelang sejak 1998','Sanggar ukir keluarga di Panjang, Magelang Utara. Setiap gebyok dipahat tangan, tanpa cetakan, dan dikeringkan delapan bulan sebelum dirakit.\n\nKami menerima pesanan ukir menyesuaikan lebar pintu dan motif pilihan pembeli.','Ukiran & Gebyok Jati','demo/shops/gebyok-mandiri-logo.png','demo/shops/gebyok-mandiri-cover.png',NULL,'Jl. Urip Sumoharjo No. 12, Panjang','Panjang','Magelang Utara','Magelang','Jawa Tengah','56111','Tugu Kota Magelang',1.10,-7.4621000,110.2198000,'2026-10-07 20:09:37',12,1,1,'identitas',NULL,'2026-10-07 20:09:37','2018-03-04',5.00,1,3,18,'2018-03-04 08:00:00','2026-10-07 20:09:37',NULL),
(2,2,'tenun-magelang','AB-0002','Tenun Magelang','Siti Maryam','Tenun ikat sutra yang ditenun di depan pengunjung','Lima alat tenun non-mesin berdiri di ruang depan rumah. Kami membuka kelas menganyam setiap Sabtu untuk pelajar dan wisatawan.','Tenun Ikat & Selendang','demo/shops/tenun-magelang-logo.png','demo/shops/tenun-magelang-cover.png',NULL,'Jl. Jend. Ahmad Yani No. 45, Rejotengan','Rejotengan','Magelang Tengah','Magelang','Jawa Tengah','56125','Alun-alun Kota Magelang',0.60,-7.4774000,110.2241000,'2026-10-07 20:09:37',7,1,1,'identitas',NULL,'2026-10-07 20:09:37','2019-08-17',5.00,1,2,18,'2019-08-17 08:00:00','2026-10-07 20:09:37',NULL),
(3,3,'keramik-magelang','AB-0003','Keramik Magelang','Bagus Prasetyo','Peralatan makan dari tanah liat Magelang','Dua tungku listrik dan satu tungku kayu menghasilkan teko, vas, dan piring dengan glasir ramah pangan.','Keramik & Porselen','demo/shops/keramik-magelang-logo.png','demo/shops/keramik-magelang-cover.png',NULL,'Jl. Magelang-Yogyakarta KM 15, Muntilan','Muntilan','Muntilan','Magelang','Jawa Tengah','56412','Pasar Muntilan',0.90,-7.5608000,110.2472000,'2026-10-07 20:09:37',9,1,1,'identitas',NULL,'2026-10-07 20:09:37','2020-01-22',5.00,1,2,18,'2020-01-22 08:00:00','2026-10-07 20:09:37',NULL);
/*!40000 ALTER TABLE `seller_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seller_quick_replies`
--

DROP TABLE IF EXISTS `seller_quick_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_quick_replies` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) unsigned NOT NULL,
  `title` varchar(120) NOT NULL,
  `body` text DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `use_count` int(11) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_id_is_active_position` (`seller_id`,`is_active`,`position`),
  CONSTRAINT `seller_quick_replies_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_quick_replies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seller_quick_replies` WRITE;
/*!40000 ALTER TABLE `seller_quick_replies` DISABLE KEYS */;
INSERT INTO `seller_quick_replies` VALUES
(1,1,'Terima kasih','Terima kasih sudah menghubungi Gebyok Mandiri. Kami balas pada jam kerja 08.00–17.00 WIB.',0,0,1,'2026-10-07 20:09:38','2026-10-07 20:09:38'),
(2,1,'Estimasi produksi','Untuk pesanan khusus, estimasi pengerjaan 21–45 hari kerja tergantung tingkat kerumitan ukiran.',1,0,1,'2026-10-07 20:09:38','2026-10-07 20:09:38'),
(3,1,'Pengiriman','Kami mengirim lewat ekspedisi berat dengan packing kayu dan bubble wrap.',2,0,1,'2026-10-07 20:09:38','2026-10-07 20:09:38'),
(4,2,'Kelas menganyam','Kelas menganyam dibuka setiap Sabtu pukul 09.00 WIB, pendaftaran maksimal H-3.',0,0,1,'2026-10-07 20:09:39','2026-10-07 20:09:39'),
(5,2,'Ukuran selendang','Selendang tersedia ukuran 200x60 cm dan 250x70 cm. Keduanya bisa dipesan melalui chat.',1,0,1,'2026-10-07 20:09:39','2026-10-07 20:09:39'),
(6,2,'Perawatan tenun','Cuci kering atau cuci tangan dengan air dingin, jangan diperas.',2,0,1,'2026-10-07 20:09:39','2026-10-07 20:09:39'),
(7,3,'Pemesanan grosir','Harga grosir mulai 24 pcs. Silakan sebutkan jumlah dan model yang dibutuhkan.',0,0,1,'2026-10-07 20:09:39','2026-10-07 20:09:39'),
(8,3,'Kelas melukis glasir','Kelas melukis glasir berlangsung tiap Minggu, durasi dua jam, sudah termasuk bahan.',1,0,1,'2026-10-07 20:09:39','2026-10-07 20:09:39'),
(9,3,'Penggantian pecah','Barang pecah dalam perjalanan kami ganti baru, cukup kirim foto unboxing.',2,0,1,'2026-10-07 20:09:39','2026-10-07 20:09:39');
/*!40000 ALTER TABLE `seller_quick_replies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seller_story_sections`
--

DROP TABLE IF EXISTS `seller_story_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_story_sections` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `seller_id_position` (`seller_id`,`position`),
  CONSTRAINT `seller_story_sections_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_story_sections`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seller_story_sections` WRITE;
/*!40000 ALTER TABLE `seller_story_sections` DISABLE KEYS */;
INSERT INTO `seller_story_sections` VALUES
(1,1,'Kayu Jati Dijemur Delapan Bulan','Bahan baku','Pilih-pilih kayu jati berumur minimal 40 tahun sebelum masuk bengkel. Kayu yang terburu-buru diolah akan retak di kemarau.','demo/shops/1-story-0.png',0,1),
(2,1,'Pahatan yang Tidak Dibuat Cetakan','Teknik','Motif klasik dipahat langsung, sehingga tidak ada dua gebyok yang benar-benar identik.','demo/shops/1-story-1.png',1,1),
(3,2,'Lima Alat Tenun di Ruang Depan','Rumah sanggar','Pengunjung boleh duduk dan mencoba alat tenun selama jam buka.','demo/shops/2-story-0.png',0,1),
(4,2,'Pewarnaan Alami dari Daun Tomentosa','Bahan','Benang dicelup tiga kali agar warna sogan tetap pekat setelah dicuci.','demo/shops/2-story-1.png',1,1),
(5,3,'Dua Tungku Listrik dan Satu Tungku Kayu','Kilang','Tungku kayu dipakai untuk glasir bertekstur, sisanya dikendalikan suhunya lewat listrik.','demo/shops/3-story-0.png',0,1),
(6,3,'Glasir Ramah Pangan','Keamanan','Seluruh koleksi makanan lulus uji larut timbal sebelum dikemas.','demo/shops/3-story-1.png',1,1);
/*!40000 ALTER TABLE `seller_story_sections` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `class` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(31) NOT NULL DEFAULT 'string',
  `context` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `identity_status` varchar(20) DEFAULT NULL,
  `partner_level` varchar(30) DEFAULT NULL,
  `artisan_since` date DEFAULT NULL,
  `contribution_total` bigint(15) unsigned NOT NULL DEFAULT 0,
  `orders_total` int(11) unsigned NOT NULL DEFAULT 0,
  `reviews_written` int(11) unsigned NOT NULL DEFAULT 0,
  `prefers_antar_terima` tinyint(1) NOT NULL DEFAULT 0,
  `notify_chat` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `partner_level` (`partner_level`),
  CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
INSERT INTO `user_profiles` VALUES
(1,4,'Sari Wulandari','Sari',NULL,'Kolektor kriya kayu dan penikmat kopi manual.','1994-06-11','P','terverifikasi',NULL,NULL,0,2,0,0,1,'2026-10-07 20:09:41','2026-10-07 20:09:41');
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `status_message` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `last_active` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'gebyok.mandiri',NULL,NULL,1,NULL,'2026-10-07 20:09:26','2026-10-07 20:09:26',NULL),
(2,'tenun.magelang',NULL,NULL,1,NULL,'2026-10-07 20:09:29','2026-10-07 20:09:29',NULL),
(3,'keramik.magelang',NULL,NULL,1,NULL,'2026-10-07 20:09:32','2026-10-07 20:09:32',NULL),
(4,'sari.pembeli',NULL,NULL,1,NULL,'2026-10-07 20:09:35','2026-10-07 20:09:35',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed
