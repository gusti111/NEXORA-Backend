-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: nexora_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-admin.nexora|10.89.213.197','i:1;',1793945744),('laravel-cache-admin.nexora|10.89.213.197:timer','i:1793945744;',1793945744),('laravel-cache-admin.nexora|127.0.0.1','i:5;',1793941751),('laravel-cache-admin.nexora|127.0.0.1:timer','i:1793941751;',1793941751),('laravel-cache-register|127.0.0.1','i:3;',1793942376),('laravel-cache-register|127.0.0.1:timer','i:1793942376;',1793942376),('laravel-cache-upa.test@gmail.com|192.168.137.44','i:1;',1794128336),('laravel-cache-upa.test@gmail.com|192.168.137.44:timer','i:1794128336;',1794128336);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logbook_reviews`
--

DROP TABLE IF EXISTS `logbook_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `logbook_id` bigint(20) unsigned NOT NULL,
  `dosen_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `komentar` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logbook_reviews_logbook_id_foreign` (`logbook_id`),
  KEY `logbook_reviews_dosen_id_foreign` (`dosen_id`),
  CONSTRAINT `logbook_reviews_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `logbook_reviews_logbook_id_foreign` FOREIGN KEY (`logbook_id`) REFERENCES `logbooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logbook_reviews`
--

LOCK TABLES `logbook_reviews` WRITE;
/*!40000 ALTER TABLE `logbook_reviews` DISABLE KEYS */;
INSERT INTO `logbook_reviews` VALUES (1,3,3,'approved','Logbook telah diperiksa dan disetujui.','2026-11-04 01:25:29','2026-11-04 01:25:29'),(2,3,3,'approved','Logbook telah diperiksa dan disetujui.','2026-11-04 01:53:39','2026-11-04 01:53:39'),(3,2,3,'revision_requested','Mohon lengkapi deskripsi kegiatan PKL.','2026-11-04 02:00:37','2026-11-04 02:00:37'),(4,2,3,'revision_requested','Mohon lengkapi dokumentasi kegiatan PKL.','2026-11-04 02:14:44','2026-11-04 02:14:44'),(5,4,3,'revision_requested','Mohon perbaiki dokumentasi foto kegiatan PKL.','2026-11-05 03:59:51','2026-11-05 03:59:51'),(6,4,3,'revision_requested','Mohon perbaiki dokumentasi foto kegiatan PKL.','2026-11-05 04:04:45','2026-11-05 04:04:45'),(7,4,3,'revision_requested','Tes penggantian foto logbook.','2026-11-05 04:06:10','2026-11-05 04:06:10'),(8,4,3,'revision_requested','Tes ulang upload foto menggunakan method spoofing.','2026-11-05 04:09:14','2026-11-05 04:09:14'),(9,4,3,'revision_requested','Tes validasi resubmit kosong.','2026-11-05 04:12:58','2026-11-05 04:12:58'),(10,4,3,'revision_requested','Tes validasi resubmit kosong.','2026-11-05 04:16:55','2026-11-05 04:16:55'),(11,4,3,'approved','Logbook telah diperiksa dan disetujui setelah revisi.','2026-11-05 05:09:15','2026-11-05 05:09:15'),(12,2,3,'revision_requested','Tes ulang validasi perubahan data yang sama.','2026-11-05 05:20:03','2026-11-05 05:20:03'),(13,8,3,'approved',NULL,'2026-11-08 00:24:16','2026-11-08 00:24:16'),(14,2,3,'approved','nice','2026-11-08 00:24:33','2026-11-08 00:24:33'),(15,7,3,'approved',NULL,'2026-11-08 00:24:51','2026-11-08 00:24:51'),(16,1,3,'approved',NULL,'2026-11-08 00:24:58','2026-11-08 00:24:58'),(17,9,3,'revision_requested','ga boleh di lokale dek','2026-11-08 01:02:41','2026-11-08 01:02:41'),(18,9,3,'approved','nice','2026-11-08 01:13:44','2026-11-08 01:13:44');
/*!40000 ALTER TABLE `logbook_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logbooks`
--

DROP TABLE IF EXISTS `logbooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pkl_id` bigint(20) unsigned NOT NULL,
  `tanggal_kegiatan` date NOT NULL,
  `judul_kegiatan` varchar(255) DEFAULT NULL,
  `deskripsi_kegiatan` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `catatan_dosen` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logbooks_pkl_id_foreign` (`pkl_id`),
  CONSTRAINT `logbooks_pkl_id_foreign` FOREIGN KEY (`pkl_id`) REFERENCES `pkls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logbooks`
--

LOCK TABLES `logbooks` WRITE;
/*!40000 ALTER TABLE `logbooks` DISABLE KEYS */;
INSERT INTO `logbooks` VALUES (1,2,'2026-11-04',NULL,'Melakukan observasi lingkungan kerja, mengenali struktur organisasi, dan mempelajari alur pekerjaan di instansi.',NULL,-0.0263000,109.3425000,'approved',NULL,'2026-11-04 00:53:48','2026-11-08 00:24:58'),(2,2,'2026-11-05',NULL,'Melakukan observasi lingkungan PKL dan mempelajari proses administrasi instansi.',NULL,-0.0263000,109.3425000,'approved','nice','2026-11-04 01:01:47','2026-11-08 00:24:33'),(3,2,'2026-11-04',NULL,'Dokumentasi kegiatan PKL hari ini.','logbooks/Z5SL9n9T2mAVpFUBKhLvWkmRNLrcowaiPcG5hGJ2.jpg',-0.0263000,NULL,'approved','Logbook telah diperiksa dan disetujui.','2026-11-04 01:02:23','2026-11-04 01:53:39'),(4,2,'2026-11-04',NULL,'Mengikuti kegiatan administrasi dan dokumnetasi PKL (perbaikan).','logbooks/logbook_6aec6400a57a7.jpg',NULL,NULL,'approved','Logbook telah diperiksa dan disetujui setelah revisi.','2026-11-04 03:05:17','2026-11-05 05:09:15'),(7,2,'2026-11-05',NULL,'lebih dari 5.000 karakter.',NULL,NULL,NULL,'approved',NULL,'2026-11-05 21:23:11','2026-11-08 00:24:51'),(8,2,'2026-11-07',NULL,'lagi di coffee shop haruna nih dek','logbooks/a64e57b6-0766-4ed6-a131-95a7f2b7b29f.jpg',-0.0580316,109.3407628,'approved',NULL,'2026-11-06 22:50:56','2026-11-08 00:24:16'),(9,2,'2026-11-08',NULL,'oke deh','logbooks/855eb072-ae60-4d35-b9f7-e38cd55cffe4.jpg',-0.0493308,109.3575726,'approved','nice','2026-11-08 00:59:48','2026-11-08 01:13:44');
/*!40000 ALTER TABLE `logbooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_11_04_042404_create_personal_access_tokens_table',2),(5,'2026_11_04_044300_add_nim_and_role_to_users_table',3),(6,'2026_11_04_052255_create_pkls_table',4),(7,'2026_11_04_052558_create_logbooks_table',5),(8,'2026_11_04_052755_create_logbook_reviews_table',6),(9,'2026_11_04_060529_add_username_to_users_table',7),(10,'2026_11_04_064812_add_is_active_to_users_table',8),(11,'2026_11_08_000001_add_verification_to_pkls_table',9),(12,'2026_11_08_103434_add_judul_kegiatan_to_logbooks_table',10);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (5,'App\\Models\\User',2,'nexora-app','7d505a98381f58d1cc3c669531fa1444ac7e3b7f49779f723add0b04b7e1bb53','[\"*\"]','2026-11-03 23:24:15',NULL,'2026-11-03 23:21:03','2026-11-03 23:24:15'),(6,'App\\Models\\User',2,'nexora-app','37f0776cef6acb3697de40a5a6888aca342f11755d19efc0ae34bf32ac336dc1','[\"*\"]','2026-11-03 23:29:52',NULL,'2026-11-03 23:26:08','2026-11-03 23:29:52'),(8,'App\\Models\\User',2,'nexora-app','166bc903d532ff76bfbce5f918d64e65cf8cdc4c6f8557f6a291a02d956dc19e','[\"*\"]','2026-11-04 00:21:38',NULL,'2026-11-03 23:37:25','2026-11-04 00:21:38'),(10,'App\\Models\\User',3,'nexora-app','f2766ec0b4eaea42f5325563260ff1cb2fe543d5e87a70dfc75f09b2a3f7aa8f','[\"*\"]','2026-11-04 01:25:29',NULL,'2026-11-04 01:09:39','2026-11-04 01:25:29'),(11,'App\\Models\\User',4,'nexora-app','b75e483a51f1ce2ecefcacf59cbaaa8de391b752c0a9d202376d37e703633052','[\"*\"]','2026-11-04 01:40:40',NULL,'2026-11-04 01:30:31','2026-11-04 01:40:40'),(12,'App\\Models\\User',2,'nexora-app','1911a419e8d3c27b6b435506fbc768b033bf6370e71efb5296f6c921077b2135','[\"*\"]','2026-11-05 21:07:51',NULL,'2026-11-04 01:45:00','2026-11-05 21:07:51'),(13,'App\\Models\\User',3,'nexora-app','b025a413ca857df4156715c43198a7a75eb660b3e98e79a2911b4461bbef43b2','[\"*\"]',NULL,NULL,'2026-11-04 01:48:40','2026-11-04 01:48:40'),(14,'App\\Models\\User',3,'nexora-app','b6d81fdee343fd70a0dc37e03e85f13f3fa89b2e704aa8cec837e40a830384d5','[\"*\"]','2026-11-05 07:48:57',NULL,'2026-11-04 01:51:42','2026-11-05 07:48:57'),(16,'App\\Models\\User',4,'nexora-app','0d8247701414076a89b5d9155ffef4b50611de76f71ff04d88a8afd21ffadfee','[\"*\"]','2026-11-05 07:32:28',NULL,'2026-11-04 02:33:46','2026-11-05 07:32:28'),(19,'App\\Models\\User',1,'nexora-app','b05c83816c1298e15485b54d7efe04f0a9a154d7bd504547fd6a3a7fa4439d8c','[\"*\"]','2026-11-05 21:23:11',NULL,'2026-11-05 07:35:36','2026-11-05 21:23:11'),(20,'App\\Models\\User',4,'nexora-app','19768fdf05f224b1003d87cb01304f5c6cf9072e3b0ae4276e5e415ee12057e6','[\"*\"]',NULL,NULL,'2026-11-05 07:50:11','2026-11-05 07:50:11'),(21,'App\\Models\\User',4,'nexora-app','6a69c756a0191a43ce80fc72c8703605c66eae06320dd12ed6a9af47d98fd0b1','[\"*\"]','2026-11-05 20:54:57',NULL,'2026-11-05 07:51:05','2026-11-05 20:54:57'),(22,'App\\Models\\User',4,'nexora-app','68a9f7c5902d38ee58c48f4f000f3b0efdb0b3bad054ffde93d0f08277006196','[\"*\"]',NULL,NULL,'2026-11-05 21:28:29','2026-11-05 21:28:29'),(23,'App\\Models\\User',3,'nexora-app','8b36e8d35144d7e9b5e21ac8aaa4fd31c8eb9ce469a63e88b5976f9a3f6bcf27','[\"*\"]','2026-11-05 21:32:48',NULL,'2026-11-05 21:30:27','2026-11-05 21:32:48'),(35,'App\\Models\\User',2,'nexora-app','211023ffee54d1a9a5475151eb5d99e9c8e3204a0b5bfab8d4a6f81cc1317295','[\"*\"]',NULL,NULL,'2026-11-05 23:07:04','2026-11-05 23:07:04'),(36,'App\\Models\\User',2,'nexora-app','80b5d444829061fa4b502bc2f3c1a60aac69b64dbc53c4b5043f9d57435fca75','[\"*\"]','2026-11-05 23:16:46',NULL,'2026-11-05 23:16:46','2026-11-05 23:16:46'),(37,'App\\Models\\User',2,'nexora-app','5aea8e49360dffafa1f2d6592e85c9151634801e256ef4fdccf84ffc9f2a4a2a','[\"*\"]','2026-11-05 23:20:42',NULL,'2026-11-05 23:20:41','2026-11-05 23:20:42'),(38,'App\\Models\\User',1,'nexora-app','9fc683242de7221bec1097c1c2f32dfdf176fef595b89a4412893ffecdb77d95','[\"*\"]','2026-11-06 22:02:38',NULL,'2026-11-06 22:02:35','2026-11-06 22:02:38'),(39,'App\\Models\\User',1,'nexora-app','bbace5871d3ae4f6d28beb5ae2225fc397f38e0b854213e212ff01043fa97693','[\"*\"]','2026-11-06 22:44:15',NULL,'2026-11-06 22:43:55','2026-11-06 22:44:15'),(40,'App\\Models\\User',1,'nexora-app','72b487c5c457c794acfd7d6d759466c765a718308fad1da9a6396522bee033c4','[\"*\"]','2026-11-06 22:50:57',NULL,'2026-11-06 22:49:37','2026-11-06 22:50:57'),(41,'App\\Models\\User',1,'nexora-app','6071b885f4a2b24ec237a3e5e45889fc887c6bce10cad063989a3a463a50a1f4','[\"*\"]','2026-11-06 22:53:11',NULL,'2026-11-06 22:52:51','2026-11-06 22:53:11'),(42,'App\\Models\\User',1,'nexora-app','980fe97b42baed42631994f6c614130b6717cb67055bcb194b48e95384a7570a','[\"*\"]','2026-11-06 23:06:50',NULL,'2026-11-06 23:06:38','2026-11-06 23:06:50'),(43,'App\\Models\\User',1,'nexora-app','4611c3cbc15f1983e13f2e5d030d2ccfec86bdc600904438fc064533ab4c0f7e','[\"*\"]','2026-11-07 23:18:02',NULL,'2026-11-07 23:17:57','2026-11-07 23:18:02'),(44,'App\\Models\\User',1,'nexora-app','93d73a7467c2f91596b4bf2be288971f0a453dc9e2a9d25fb4e0a3d3d8f9243e','[\"*\"]','2026-11-07 23:19:45',NULL,'2026-11-07 23:19:34','2026-11-07 23:19:45'),(45,'App\\Models\\User',1,'nexora-app','a1d40b9c6d8f4d72b0bcb552b8d719a9bf645d95274596e9d0e1f87d4ffeccd1','[\"*\"]','2026-11-07 23:53:32',NULL,'2026-11-07 23:28:45','2026-11-07 23:53:32'),(46,'App\\Models\\User',1,'nexora-app','a9753dfa762686477db8b6be7356e9ffe884b385efd8c6716333d874f82622aa','[\"*\"]','2026-11-07 23:55:45',NULL,'2026-11-07 23:55:43','2026-11-07 23:55:45'),(47,'App\\Models\\User',2,'nexora-app','940a6170b532c14b9fb3bfc1a755eabbcd22608326b1ddca5a47de897c99e5f2','[\"*\"]','2026-11-08 00:07:29',NULL,'2026-11-08 00:03:36','2026-11-08 00:07:29'),(48,'App\\Models\\User',3,'nexora-app','d8c24ae526f8314034868c0e2533567b925a0d65199cd2624fb341f9db63b04c','[\"*\"]','2026-11-08 00:57:05',NULL,'2026-11-08 00:08:33','2026-11-08 00:57:05'),(49,'App\\Models\\User',1,'nexora-app','d6b51ae70dbca242f677c2e5b9f55fea10dcd52c0fefc5434296f0ca1dd8e483','[\"*\"]','2026-11-08 00:59:49',NULL,'2026-11-08 00:57:42','2026-11-08 00:59:49'),(50,'App\\Models\\User',3,'nexora-app','e4e17cf0b969751c0caa4f7707ac45e6fbc2370726e2356faa06e454c9988dcb','[\"*\"]','2026-11-08 01:02:42',NULL,'2026-11-08 01:01:06','2026-11-08 01:02:42'),(51,'App\\Models\\User',1,'nexora-app','25c16aad8fbc9c1ee92b817b5f1d5cbbd74dc2ca2d009c1ed4cf69e82683b92a','[\"*\"]','2026-11-08 01:08:22',NULL,'2026-11-08 01:03:26','2026-11-08 01:08:22'),(52,'App\\Models\\User',3,'nexora-app','97c4e2dbbc2f6ae99c81982126ee8aa526d8e748a719096ed552d895a426686a','[\"*\"]','2026-11-08 01:13:48',NULL,'2026-11-08 01:09:02','2026-11-08 01:13:48'),(53,'App\\Models\\User',1,'nexora-app','d7faca9fcbed45e284a2487380fc50a909c41832817d8ef839023bc6081e362a','[\"*\"]','2026-11-08 01:14:14',NULL,'2026-11-08 01:14:08','2026-11-08 01:14:14'),(54,'App\\Models\\User',4,'nexora-app','ff7ac318353bbd0f9fb45a6e4540c32b4cfde5e382f0225cd150839762e0b47d','[\"*\"]','2026-11-08 01:36:12',NULL,'2026-11-08 01:16:13','2026-11-08 01:36:12'),(55,'App\\Models\\User',1,'nexora-app','2c275f736b1c573c48208ed7fc6b72c9e98c961cfa03b8adea8dfbd6b603d6a4','[\"*\"]','2026-11-08 01:50:06',NULL,'2026-11-08 01:37:02','2026-11-08 01:50:06'),(56,'App\\Models\\User',1,'nexora-app','9f48bacf4aeed438693ef9a692b955330ef3e31fc51df83838b7eb88c9a7d701','[\"*\"]','2026-11-08 01:57:29',NULL,'2026-11-08 01:57:28','2026-11-08 01:57:29'),(57,'App\\Models\\User',1,'nexora-app','91e322764d1b4d852e9bc8ef29aaf41548cacac3771ca1d35573f2300f7b0846','[\"*\"]','2026-11-08 02:11:30',NULL,'2026-11-08 02:10:56','2026-11-08 02:11:30'),(58,'App\\Models\\User',19,'nexora-app','cb6ab38f6eccdf33b9089f1d5f2ee2862bdef8b513d350609d2a674346e9c85e','[\"*\"]','2026-11-08 02:12:19',NULL,'2026-11-08 02:12:09','2026-11-08 02:12:19'),(59,'App\\Models\\User',1,'nexora-app','b227f8a90a3bd40809abbb11bd274a3c84a823e88bd6ea22205f1a3f351fb3e7','[\"*\"]','2026-11-08 02:25:49',NULL,'2026-11-08 02:25:31','2026-11-08 02:25:49'),(60,'App\\Models\\User',19,'nexora-app','52067efb0c1871fff9b007d2417ab18fd4b3648ad358478931e1ca98bfa00961','[\"*\"]','2026-11-08 02:29:14',NULL,'2026-11-08 02:29:13','2026-11-08 02:29:14'),(61,'App\\Models\\User',3,'nexora-app','46b5c7a09058058ff50f9297e0610c2d8a68ddcfdd31cbee8db0507185e2a398','[\"*\"]','2026-11-08 02:41:37',NULL,'2026-11-08 02:39:36','2026-11-08 02:41:37'),(62,'App\\Models\\User',4,'nexora-app','c04deefd99c764f854aa170cfe043bbeee8e19729cbabec00c84ad4f692e0953','[\"*\"]','2026-11-08 03:30:54',NULL,'2026-11-08 03:07:03','2026-11-08 03:30:54'),(63,'App\\Models\\User',2,'nexora-app','9412ffd1c6600d4242b838cb610b88876fd905c17f56e5ec9cd91763937c1769','[\"*\"]','2026-11-08 03:38:26',NULL,'2026-11-08 03:32:52','2026-11-08 03:38:26');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pkls`
--

DROP TABLE IF EXISTS `pkls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pkls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mahasiswa_id` bigint(20) unsigned NOT NULL,
  `dosen_id` bigint(20) unsigned DEFAULT NULL,
  `korlap_id` bigint(20) unsigned DEFAULT NULL,
  `nama_instansi` varchar(255) NOT NULL,
  `alamat_instansi` text DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'berlangsung',
  `diverifikasi_oleh` bigint(20) unsigned DEFAULT NULL,
  `tanggal_selesai_verifikasi` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pkls_mahasiswa_id_foreign` (`mahasiswa_id`),
  KEY `pkls_dosen_id_foreign` (`dosen_id`),
  KEY `pkls_korlap_id_foreign` (`korlap_id`),
  KEY `pkls_diverifikasi_oleh_foreign` (`diverifikasi_oleh`),
  CONSTRAINT `pkls_diverifikasi_oleh_foreign` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pkls_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pkls_korlap_id_foreign` FOREIGN KEY (`korlap_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pkls_mahasiswa_id_foreign` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pkls`
--

LOCK TABLES `pkls` WRITE;
/*!40000 ALTER TABLE `pkls` DISABLE KEYS */;
INSERT INTO `pkls` VALUES (1,19,3,4,'PT NEXORA Teknologi','Pontianak, Kalimantan Barat','2026-11-09','2027-02-09','selesai',NULL,NULL,'2026-11-04 00:08:35','2026-11-08 02:02:47'),(2,1,3,4,'DPRD Provinsi Kalimantan Barat','Pontianak, Kalimantan Barat','2026-11-04','2027-02-09','berlangsung',NULL,NULL,'2026-11-04 00:08:58','2026-11-05 21:07:51');
/*!40000 ALTER TABLE `pkls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('02cRpjlIYLUGOhU8TJjuyTkzqJ94jb6Yq1qv0Nz6',NULL,'192.168.137.44','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidnFzWVBsNUZEOGN6ZnpkN1Fmemt5OXJtaHNGWjRoTWFRUE9vT3I2cSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly8xOTIuMTY4LjEzNy4xOjgwMDAiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1794134202),('3Im17E88ErjzwcGuEOOb2qHahR5cOFnvwrRddrZ8',NULL,'192.168.137.44','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoid1pUbGZqbzdEQ2pjWFFPZUN0MnJCNjZmS095NXJneWI5TnRkZ1VHUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly8xOTIuMTY4LjEzNy4xOjgwMDAiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1794117968),('aN3q9kHjKVnT8pY87wDqA2P2rqNl7rCpfV3GbHo8',NULL,'10.10.10.237','curl/8.21.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYmhJY2l0R2g5TDdvYXdlR0kwSlFxdlpCNUZ0b0swNnlQdGxGRVYzQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly8xMC4xMC4xMC4yMzc6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793943277),('BPiol3N4YqM0oadE5BxNp1eBemCFe6coo96Yrgdk',NULL,'10.10.9.129','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoic3BpT1dnZFRuUnlyVWkwbjQ3TTk2OHE5Tk1SSE5POHc5WjFtdzdjNyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHA6Ly8xMC4xMC45LjEyOTo4MDAwIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1794132338),('ewPydMSHTdy8yTJHi4EaP7fwn8FFsLXlXEMeHtRV',NULL,'127.0.0.1','curl/8.21.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTEtpYTNHNlNWNnpnaGhVSEVZNnUxdHJIMldTbldrU3MxRmVsaFhkWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793943372),('kMChojyzdEqDOVSyZWf24ggEgbHveTf5WwQai2Az',NULL,'10.10.9.129','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWGlxOHN2UUhQckZVcjF6c2JPN0RLT1hsZUtQUDRlZDNvTGdCSWdIbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHA6Ly8xMC4xMC45LjEyOTo4MDAwIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1794117835),('l6d1oyGcxlCGw2kn1l4WPlh4JFLCoPqWI528qahb',NULL,'172.16.9.245','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYm41OGVmTnp4ZGFuVmNPeVJXUTNiN2dlMXptUmhndFU3ZURodVFnayI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly8xNzIuMTYuMTAuNjg6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1794025222),('O11TjXz7D5DU38V3LgkdDOzkg7T4qlEZ6SXA9sYA',NULL,'172.16.10.68','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoicW9CNE1tcGdEa3FDQ1lxdmNXZG1wY2VQVWpzbFlpM2dMclhkc29HZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly8xNzIuMTYuMTAuNjg6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1794025193),('RTQbTAK9b7bvqac8qWcuEgy5P4JanyyAgcDUOW3g',NULL,'10.10.9.129','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWjR6WUdpYTJFb1oxckZtOWlPSmFaMEtaZjRNdXNCckRud3dwcWtsbCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHA6Ly8xMC4xMC45LjEyOTo4MDAwIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1794117836),('rVvodMf4ibftUAmduPMYcuNpgxBINzfHlOy4Pysj',NULL,'127.0.0.1','curl/8.21.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoibHRQcHRmN3NkNmhhMlNwcVpraXRINDNpVDBKVHJTSXg4cWRFN2E3ViI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793943269),('RYMwDV0mIcb0WMY5B6aZPtm2aqERqBuinCSnjjZ3',NULL,'10.89.213.197','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTllGYzhwaXl1ZFlXclFPVWRIWjJ1cm9yeTl0T1k1SWh4UWNCQWJ6MCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly8xMC44OS4yMTMuMTMwOjgwMDAiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1793944133),('swh8mNI4hB9bw1jUg6m5m1nRkqCrmyx02w0a9ZfG',NULL,'127.0.0.1','curl/8.21.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaXVzZlZ3b0pSV0hLZG5ZRzZhbkV5TktzR1U1MmlUM1dBQ3c1ZENadiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793943376),('vaLgKL51hvVTz7ZHCD2R4DwnNcweBABXUOftbzxd',NULL,'10.89.213.130','curl/8.21.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUEJXVWt6SXZYc1dyV2hGVGt1SENQNDlBVG96Y2l3eGZFbGNiNnhuYyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly8xMC44OS4yMTMuMTMwOjgwMDAiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1793943980),('xRHSfb5PeyHR4cpNzcfbe9C3ChSln60qA0NhxOeK',NULL,'10.10.9.129','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOWFLV2ppYlVmT1licFRLWURzUEJybWxNZXFWcjdHNDdDTW4xQ3ZzTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjM6Imh0dHA6Ly8xMC4xMC45LjEyOTo4MDAwIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1794117742),('xYG4XwcZcRqdHLM7LpKm9nuJQV55bypuqGB0mRWz',NULL,'10.10.10.237','curl/8.21.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUW5RcEtPSERKaDJHZ0N0Q0dQYmNHR3RXc0J1RzR2eDlHR08xQXhtdiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly8xMC4xMC4xMC4yMzc6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793943400);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nim` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'mahasiswa',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nim_unique` (`nim`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Upa Test','123456789',NULL,'mahasiswa',1,'mahasiswa.dprd@nexora.test',NULL,'$2y$12$gfpvkyK9o8YdezeeQSyfJuwWHOnnNYrHpVZe3I.r/7Dq/RZRIFrKe',NULL,'2026-11-03 22:05:19','2026-11-08 01:59:01'),(2,'Administrator NEXORA',NULL,'admin.nexora','admin',1,'admin@nexora.test',NULL,'$2y$12$22Vv8vNoH6k.68bEjkPtM.ZT61ZHClHX2/iLf07crQRFPvFRwVmfi',NULL,'2026-11-03 23:20:04','2026-11-08 03:31:27'),(3,'Dosen NEXORA Updated',NULL,NULL,'dosen',1,'dosen@nexora.test',NULL,'$2y$12$xnTsM5Aw5gFE7sgWS.DX0OWviRA2C9TqybksuX3749ImjxrDgWK1i',NULL,'2026-11-03 23:38:25','2026-11-05 21:30:04'),(4,'Korlap NEXORA',NULL,NULL,'korlap',1,'korlap@nexora.test',NULL,'$2y$12$dWuIQ5bAnn4m40UJ7PAVIuE8qyooOvMhVPncEiFJjjzjo7YgFTnr6',NULL,'2026-11-03 23:38:57','2026-11-04 02:32:55'),(19,'Mahasiswa PT NEXORA','423456789','mahasiswa.nexora','mahasiswa',1,'mahasiswa.nexora@nexora.test',NULL,'$2y$12$N/lkXVfMt4ap1tBsCMpuoe2Qrn3Ln7A5bFPvf0H.iQLQXuBxL9gUW',NULL,'2026-11-08 02:02:25','2026-11-08 02:02:25');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-08 18:07:19
