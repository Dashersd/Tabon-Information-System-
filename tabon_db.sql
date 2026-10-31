SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

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
-- Table structure for table `about_content`
--

DROP TABLE IF EXISTS `about_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `about_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `history` longtext NOT NULL,
  `mission` text NOT NULL,
  `vision` text NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT 'Images/Tabon Barangay hall.jpg',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `about_content`
--

LOCK TABLES `about_content` WRITE;
/*!40000 ALTER TABLE `about_content` DISABLE KEYS */;
INSERT INTO `about_content` VALUES (1,'Tabon means \"to cover\". It is derived to the characteristics of a bird name in Subanen dialect ΓÇ£MANGAWAGΓÇ¥, because when this kind of bird will lay eggs, they will dig a hole in the ground and lay eggs therein, after that it will covered with earth/soil.\n\nTabon was inhabited by Subanen who made their living by picking a fruit trees and hunting wild animals in the jungle. These Lumads made houses of ΓÇ£NIBONGΓÇ¥ and ΓÇ£SUSAYΓÇ¥ leaves for roofing and tying rattan for the light local materials, instead of using nails. As the Lumad hunters looked for food, he roamed around to hunt, until he saw something covered in the ground with dry leaves, branches and roots of trees with mixed soil. The hunter removed the cover and dig the hole, while digging he found out it was an eggs of a wild fowl called ΓÇ£MANGAWAGΓÇ¥. This kind of wild fowls, dig the ground to lay eggs and made it a nests. The word ΓÇ£TABONΓÇ¥ came from the word ΓÇ£TABONANΓÇ¥ means ΓÇ£TO COVERΓÇ¥.\n\nBarangay Tabon is the home of the Lumad Subanen, one of the 18 major Lumad tribes in Mindanao Island. The first Subanen families who inhabited the place when it was not yet a Barangay are only the families of ΓÇ£MAMULO, UNGOB, & MANGEDLOGΓÇ¥.\n\nWhen the Barangay was not yet created it was a sitio of ΓÇ£BARANGAY MARWINGΓÇ¥, and when Barangay Marwing was created into a Municipality in the year 1964, Tabon was created into Barangay by the Municipal Councilors of Marwing. That when Municipality of Marwing was dissolved in the year 1965, the municipal Government of Lapuyan still recognized Tabon as Barangay. That during those prior years a sitio will be created into a Barangay to not less than 500 inhabitants & in the year 1972, there was a Circular from the Office of the President that all Barangay should be approved by the House of Representatives and Senate and to be signed by the President before it will become a Regular Barangay. On ΓÇ£AUGUST 28, 1972ΓÇ¥, by Virtue of ΓÇ£REPUBLIC ACT 3590ΓÇ¥ Tabon was created a regular Barangay.\n\nThe first leader of the Barangay was ΓÇ£SAGUILE LIPAΓÇ¥, because it was created into Barangay when Marwing was created into Municipality and until Marwing was dissolved, the municipality of Lapuyan still recognized it as a Barangay. That during that time said SAGUILE LIPA was a municipal councilor.\n\nThe first appointed Barangay Captain when Maruing was a Municipality was ΓÇ£BENTIA MALINTANGΓÇ¥, and when Maruing was dissolved being SAGUILE LIPA is no longer a Municipal Councilor, he was then appointed as Barangay Captain until he was again elected. Thereafter, it was again ΓÇ£PAPAO GALS, then RUBIO TILABAD, then Barangay Captain JANITO LIPAΓÇ¥, and the new elected Punong Barangay Nelcy D. Ongub.','The Barangay Officials and the people in the Barangay united in working for the attainment of sufficient and effective services, increased income through developed agriculture and create activities that would foster greater unity and strong relationships among settlers, under the strong and good overnment administration and continually living and enjoying a balanced environment.','The Barangay Tabon envisions a dynamic Barangay whose united, God-loving and empowered people. The Subanens and settlers will live harmoniously and peacefully, having a progressive and decent living in a balanced environment under a strong and good government administration.','Images/Tabon Barangay hall.jpg','2026-10-29 01:07:43');
/*!40000 ALTER TABLE `about_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (7,1,'Admin Profile Updated','Credentials modified for Dash (admin@example.com)','::1','2026-10-31 04:16:17'),(8,1,'Admin Logout','Administrator signed out of session','::1','2026-10-31 04:16:27'),(9,1,'Admin Login','Administrator authenticated successfully','::1','2026-10-31 04:16:39'),(12,1,'Admin Login','Administrator authenticated successfully','::1','2026-10-31 09:10:20'),(13,1,'Profile Updated','Barangay profile details, branding seals, and hero video updated','::1','2026-10-31 09:53:53'),(14,1,'Profile Updated','Barangay profile details, branding seals, and hero video updated','::1','2026-10-31 09:55:03'),(15,1,'Profile Updated','Barangay profile details, branding seals, and hero video updated','::1','2026-10-31 09:55:41'),(16,1,'Profile Updated','Barangay profile details, branding seals, and hero video updated','::1','2026-10-31 09:56:08');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `what` varchar(255) DEFAULT NULL,
  `when_date` varchar(255) DEFAULT NULL,
  `where_place` varchar(255) DEFAULT NULL,
  `who` varchar(255) DEFAULT NULL,
  `why` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `status` enum('published','draft','archived') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'General Assembly Meeting','General Assembly Meeting & Community Dialogue','9:00 AM, October 15, 2026','Barangay Covered Gymnasium, Tabon','All Barangay Residents & Community Leaders','Discuss infrastructure projects, local budget allocation, and development initiatives.','Official Barangay Assembly for the 2nd Semester. We encourage all registered voters and residents of Barangay Tabon to actively participate in this open forum. Important updates on community peace and order, disaster preparedness, and planned street lighting projects will be presented.','uploads/announcements/reflection_poster.png','published','2026-10-15 00:00:00','2026-10-27 01:31:12'),(2,'Free Medical & Dental Mission','Free Medical Consultations & Dental Care','8:00 AM - 3:00 PM, October 22, 2026','Barangay Tabon Health Center','Senior Citizens, Persons with Disabilities, and Children','Offer free general health checkups, tooth extractions, and maintenance medicines.','In partnership with the Municipal Health Office and volunteer doctors, Barangay Tabon is holding a one-day comprehensive medical and dental mission. Free medicines, vitamins, reading glasses, and blood pressure monitoring will be distributed on a first-come, first-served basis.',NULL,'published','2026-10-22 00:00:00','2026-10-27 01:31:12'),(3,'Undas 2026 Traffic Advisory','Traffic Rerouting & Peace and Order Advisory','All Day, November 01, 2026','Main Barangay Access Road & Cemetery Perimeter','All Motorists, Commuters, and Visiting Residents','Ensure smooth vehicular flow and security during All Saints\' Day observance.','Please be guided accordingly on the temporary one-way traffic scheme along the cemetery perimeter. Designated parking zones will be guarded by Barangay Tanods and volunteer peacekeepers. Bladed weapons, loud sound systems, and alcoholic beverages are strictly prohibited within cemetery premises.','uploads/announcements/1793248162_6ae2cba2ce278.png','published','2026-10-25 02:00:00','2026-10-29 04:29:22');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `barangay_information`
--

DROP TABLE IF EXISTS `barangay_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barangay_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barangay_name` varchar(150) NOT NULL DEFAULT 'Barangay Tabon',
  `municipality` varchar(150) NOT NULL DEFAULT 'Lapuyan',
  `province` varchar(150) NOT NULL DEFAULT 'Zamboanga del Sur',
  `slogan` varchar(255) DEFAULT 'Serbisyong Tapat, Para sa Lahat',
  `office_hours` varchar(150) DEFAULT 'Monday - Friday, 8:00 AM - 5:00 PM',
  `hall_address` varchar(255) DEFAULT 'Barangay Hall, Tabon, Lapuyan, Zamboanga del Sur',
  `mission_vision` text DEFAULT NULL,
  `email` varchar(150) DEFAULT 'barangaytabon@gmail.com',
  `contact_number` varchar(50) DEFAULT '(+63) 912 345 6789',
  `facebook_url` varchar(255) DEFAULT 'https://www.facebook.com/',
  `police_hotline` varchar(50) DEFAULT '0998-598-7241',
  `fire_hotline` varchar(50) DEFAULT '0917-123-4567',
  `health_hotline` varchar(50) DEFAULT '0920-987-6543',
  `tanod_hotline` varchar(50) DEFAULT '0931-234-5678',
  `logo_path` varchar(255) DEFAULT '../public/image/Maruing Logo 2.png',
  `municipality_logo_path` varchar(255) DEFAULT '../Barangay Logo/lapuyanlogo.jpg',
  `hero_video_path` varchar(255) DEFAULT 'Video/Homepage.mp4',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barangay_information`
--

LOCK TABLES `barangay_information` WRITE;
/*!40000 ALTER TABLE `barangay_information` DISABLE KEYS */;
INSERT INTO `barangay_information` VALUES (1,'Barangay Tabon','Lapuyan','Zamboanga del Sur','Serbisyong Tapat, Para sa Lahat','Monday - Friday, 8:00 AM - 5:00 PM','Barangay Hall, Tabon, Lapuyan, Zamboanga del Sur','A peaceful, progressive, and resilient community of Tabon.','barangaytabon@gmail.com','(+63) 912 345 6789','https://www.facebook.com/','0998-598-7241','0917-123-4567','0920-987-6543','0931-234-5678','../Barangay Logo/Logo.png','../Barangay Logo/lapuyanlogo.jpg','uploads/branding/hero_video_1793440568.mp4','2026-10-29 06:17:10','2026-10-31 09:56:08');
/*!40000 ALTER TABLE `barangay_information` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `pin_code` varchar(10) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `service_type` varchar(150) DEFAULT 'Contact Inquiry',
  `message` text NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `resend_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verifications`
--

LOCK TABLES `email_verifications` WRITE;
/*!40000 ALTER TABLE `email_verifications` DISABLE KEYS */;
INSERT INTO `email_verifications` VALUES (1,'test.resident@gmail.com','736294','Test Resident','09123456789','Contact Inquiry','I would like to request Barangay Clearance certificate.','2026-10-28 13:41:33',0,0,'2026-10-28 12:38:33'),(4,'mugiw200@gmail.com','371874','Dash','945946349','Barangay Clearance','Hello','2026-10-28 14:14:06',2,0,'2026-10-28 13:11:06'),(5,'helmandacuma5@gmail.com','161700','Dash','32546347','Certificate of Residency','dsss','2026-10-29 01:31:27',1,0,'2026-10-29 00:28:27'),(6,'mugiw200@gmail.com','541976','Dash','2523523','Certificate of Indigency','sdsdbs','2026-10-29 01:34:20',1,0,'2026-10-29 00:31:20'),(7,'mugiw200@gmail.com','970346','sdasd','346345734','Certificate of Indigency','sdgsdgd','2026-10-29 01:40:07',0,0,'2026-10-29 00:37:07'),(8,'helmandacuma12@gmail.com','491065','Sdasd','q34634634','Business Clearance','AHSfhasgaha','2026-10-29 01:45:37',0,0,'2026-10-29 00:42:37'),(9,'helmandacuma5@gmail.com','997680','Sdasd','q34634634','Certificate of Residency','AHSfhasgaha','2026-10-29 02:01:22',2,0,'2026-10-29 00:58:22'),(10,'helmandacuma5@gmail.com','396233','Das','3252623','Certificate of Indigency','sdhehsreh','2026-10-29 02:01:57',2,0,'2026-10-29 00:58:57'),(11,'christianne.dale1998@gmail.com','830880','dale','09876543212','Barangay Clearance','hello','2026-10-29 06:35:55',1,0,'2026-10-29 05:32:55'),(12,'helmandacuma5@gmail.com','797252','Dash','436346','Certificate of Indigency','sgdsgsd','2026-10-29 06:38:43',1,0,'2026-10-29 05:35:43');
/*!40000 ALTER TABLE `email_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_chats`
--

DROP TABLE IF EXISTS `feedback_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback_chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` varchar(100) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sender_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `message` text NOT NULL,
  `file_attachment` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_thread_id` (`thread_id`),
  KEY `idx_sender_type_read` (`sender_type`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_chats`
--

LOCK TABLES `feedback_chats` WRITE;
/*!40000 ALTER TABLE `feedback_chats` DISABLE KEYS */;
INSERT INTO `feedback_chats` VALUES (1,'thread_17f50de22bc27e039da15e85af36d8fa','Juan Dela Cruz','juan.delacruz@gmail.com','user','[Service: Barangay Clearance]\n\nGood day po! Ask ko lang po kung pwede mag-request ng Barangay Clearance online for employment?',NULL,NULL,1,'2026-10-27 15:58:16'),(2,'thread_17f50de22bc27e039da15e85af36d8fa','Admin',NULL,'admin','Magandang araw din po Ginoong Juan! Ang requirements po ay 1 Valid ID at Cedula. Maaari po kayong magtungo sa Barangay Hall Lunes hanggang Biyernes 8:00 AM to 5:00 PM.',NULL,NULL,1,'2026-10-27 16:03:16'),(3,'thread_393db1414b4d0e3173d11914dc21b672','Maria Clara Santos','maria.clara@yahoo.com','user','[Service: Community Welfare / Indigency]\n\nHello po Barangay Admin, nais ko po sana humingi ng tulong para sa Certificate of Indigency para sa medical assistance ng nanay ko.',NULL,NULL,1,'2026-10-27 15:28:16'),(4,'thread_efffc1ad4429e356c4a5c2ed2ad0e7e3','Roberto Mendoza','roberto.mendoza@gmail.com','user','[Service: Business Permit Assessment]\n\nGood day po, kailangan po ba ng Barangay Inspection bago mag-renew ng sari-sari store permit?',NULL,NULL,1,'2026-10-25 16:13:16'),(5,'thread_efffc1ad4429e356c4a5c2ed2ad0e7e3','Admin',NULL,'admin','Opo Sir Roberto, may routine inspection po ang ating Barangay Lupon.',NULL,NULL,1,'2026-10-25 18:13:16'),(8,'thread_ca07d8548e76cdfc73c6813bedf413fd','Dash','helmandacuma5@gmail.com','user','[Service: Certificate of Residency]\n\ndsss\n\nContact Phone: 32546347',NULL,NULL,1,'2026-10-29 00:28:52'),(9,'thread_de75d29e18895e0dbd4941b336df79f1','Dash','mugiw200@gmail.com','user','[Service: Certificate of Indigency]\n\nsdsdbs\n\nContact Phone: 2523523',NULL,NULL,1,'2026-10-29 00:32:14'),(10,'thread_d7148505010f8c02af7e7bc949c81cf6','dale','christianne.dale1998@gmail.com','user','[Service: Barangay Clearance]\n\nhello\n\nContact Phone: 09876543212',NULL,NULL,1,'2026-10-29 05:34:00'),(11,'thread_566fb2842219a3a42f76c0d70fce58ff','Dash','helmandacuma5@gmail.com','user','[Service: Certificate of Indigency]\n\nsgdsgsd\n\nContact Phone: 436346',NULL,NULL,1,'2026-10-29 05:36:35');
/*!40000 ALTER TABLE `feedback_chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_conversations`
--

DROP TABLE IF EXISTS `feedback_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT 'General Feedback',
  `status` enum('new','open','resolved') DEFAULT 'new',
  `last_message` text DEFAULT NULL,
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unread_admin` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_conversations`
--

LOCK TABLES `feedback_conversations` WRITE;
/*!40000 ALTER TABLE `feedback_conversations` DISABLE KEYS */;
INSERT INTO `feedback_conversations` VALUES (1,'Juan Dela Cruz','juan.delacruz@gmail.com','0917-123-4567','Barangay Clearance Request','open','Good day po! Ask ko lang po kung pwede mag-request ng Barangay Clearance online for employment?','2026-10-27 15:52:51',0,'2026-10-27 15:29:45'),(2,'Maria Clara Santos','maria.clara@yahoo.com','0928-876-5432','Streetlight Maintenance in Purok 2','open','Salamat po sa mabilis na aksyon!','2026-10-27 15:44:45',0,'2026-10-27 13:44:45'),(3,'Roberto Mendoza','roberto.mendoza@gmail.com','0995-345-6789','Certificate of Indigency','resolved','Maraming salamat po, nakuha ko na po kanina.','2026-10-27 15:44:45',0,'2026-10-26 15:44:45');
/*!40000 ALTER TABLE `feedback_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_messages`
--

DROP TABLE IF EXISTS `feedback_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('resident','admin') NOT NULL,
  `sender_name` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  CONSTRAINT `fk_feedback_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `feedback_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_messages`
--

LOCK TABLES `feedback_messages` WRITE;
/*!40000 ALTER TABLE `feedback_messages` DISABLE KEYS */;
INSERT INTO `feedback_messages` VALUES (1,1,'resident','Juan Dela Cruz','Magandang araw po Barangay Tabon admin. Nais ko lang po sanang itanong kung ano po ang requirements para sa Barangay Clearance?',NULL,1,'2026-10-27 15:19:45'),(2,1,'admin','Barangay Secretary','Magandang araw din po Ginoong Juan! Ang requirements po ay 1 Valid ID at Cedula (Community Tax Certificate). Maaari po kayong magtungo sa Barangay Hall Lunes hanggang Biyernes 8:00 AM to 5:00 PM.',NULL,1,'2026-10-27 15:24:45'),(3,1,'resident','Juan Dela Cruz','Good day po! Ask ko lang po kung pwede mag-request ng Barangay Clearance online for employment?',NULL,1,'2026-10-27 15:29:45'),(4,2,'resident','Maria Clara Santos','Hello po Barangay Admin, may report lang po sana kami regarding sa streetlight malapit sa Purok 2 chapel na pundido na po.',NULL,1,'2026-10-27 12:44:45'),(5,2,'admin','Barangay Secretary','Noted po Maam Maria. Naitawag na po natin ito sa ating Barangay Maintenance Team para mapuntahan at mapalitan ngayong hapon.',NULL,1,'2026-10-27 13:14:45'),(6,2,'resident','Maria Clara Santos','Salamat po sa mabilis na aksyon!',NULL,1,'2026-10-27 13:44:45'),(7,3,'resident','Roberto Mendoza','Good day po, available po ba si Kapitan bukas para sa pirma ng Certificate of Indigency?',NULL,1,'2026-10-26 12:44:45'),(8,3,'admin','Barangay Secretary','Opo Sir Roberto, bukas po ang ating tanggapan buong araw.',NULL,1,'2026-10-26 13:44:45'),(9,3,'resident','Roberto Mendoza','Maraming salamat po, nakuha ko na po kanina.',NULL,1,'2026-10-26 15:44:45');
/*!40000 ALTER TABLE `feedback_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_folders`
--

DROP TABLE IF EXISTS `gallery_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_folders`
--

LOCK TABLES `gallery_folders` WRITE;
/*!40000 ALTER TABLE `gallery_folders` DISABLE KEYS */;
INSERT INTO `gallery_folders` VALUES (4,'Official Barangay Seals & Branding','Official insignia and municipal emblems of Barangay Tabon and Lapuyan.','2026-10-31 02:27:20','2026-10-31 02:27:20');
/*!40000 ALTER TABLE `gallery_folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_media`
--

DROP TABLE IF EXISTS `gallery_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folder_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `folder_id` (`folder_id`),
  CONSTRAINT `fk_gallery_folder` FOREIGN KEY (`folder_id`) REFERENCES `gallery_folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_media`
--

LOCK TABLES `gallery_media` WRITE;
/*!40000 ALTER TABLE `gallery_media` DISABLE KEYS */;
INSERT INTO `gallery_media` VALUES (5,4,'uploads/gallery/1793116977_6ae0cb31e7709_Logo.png','Logo.png','Barangay Tabon Official Insignia',45000,'2026-10-31 02:27:20'),(6,4,'uploads/gallery/1793116986_6ae0cb3a229f7_lapuyanlogo.jpg','lapuyanlogo.jpg','Municipality of Lapuyan Official Seal',52000,'2026-10-31 02:27:20');
/*!40000 ALTER TABLE `gallery_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legends`
--

DROP TABLE IF EXISTS `legends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `icon_class` varchar(100) DEFAULT 'fa-location-dot',
  `color` varchar(20) DEFAULT '#000000',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legends`
--

LOCK TABLES `legends` WRITE;
/*!40000 ALTER TABLE `legends` DISABLE KEYS */;
INSERT INTO `legends` VALUES (1,'Barangay Hall','fa-building','#4a6cf7','2026-10-08 02:57:00'),(2,'School','fa-book-open','#28a745','2026-10-08 02:57:00'),(3,'Court','fa-basketball','#fca311','2026-10-08 02:57:00');
/*!40000 ALTER TABLE `legends` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `officials`
--

DROP TABLE IF EXISTS `officials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `officials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `official_key` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `officials`
--

LOCK TABLES `officials` WRITE;
/*!40000 ALTER TABLE `officials` DISABLE KEYS */;
INSERT INTO `officials` VALUES (1,'captain','Hon. Reynaldo D. Santos','Barangay Captain','uploads/officials/official_captain_1793247736.png','To our residents, partners, and every member of our community, thank you for your trust, cooperation, and unwavering support. At Barangay Tabon, we remain committed to delivering honest and transparent public service that promotes safety, unity, and progress for all. Together, let us continue building a barangay where every family feels heard, respected, and supported.','active','2026-10-29 04:22:00','2026-10-29 04:22:16'),(2,'secretary','Hon. Maria Cristina S. Flores','Barangay Secretary','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00'),(3,'treasurer','Hon. Joseph L. Reyes','Barangay Treasurer','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00'),(4,'kagawad_1','Hon. Mark Anthony T. Cruz','Barangay Kagawad','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00'),(5,'kagawad_2','Hon. Alma B. Navarro','Barangay Kagawad','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00'),(6,'kagawad_3','Hon. Allan P. Santos','Barangay Kagawad','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00'),(7,'kagawad_4','Hon. Liza M. Torres','Barangay Kagawad','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00'),(8,'kagawad_5','Hon. Ramon C. Dela Cruz','Barangay Kagawad','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00'),(9,'kagawad_6','Hon. Nelcy D. Ongub','Barangay Kagawad','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00'),(10,'kagawad_7','Hon. Janito Lipa','Barangay Kagawad','Officials/Captain.jpg',NULL,'active','2026-10-29 04:22:00','2026-10-29 04:22:00');
/*!40000 ALTER TABLE `officials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purok1_locations`
--

DROP TABLE IF EXISTS `purok1_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purok1_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `house_number` varchar(50) NOT NULL,
  `husband_name` varchar(150) NOT NULL,
  `spouse_name` varchar(150) DEFAULT NULL,
  `children` text DEFAULT NULL,
  `house_image` varchar(255) NOT NULL,
  `marker_image` varchar(255) NOT NULL,
  `marker_width` int(11) DEFAULT 40,
  `marker_height` int(11) DEFAULT 40,
  `coordinate_x` decimal(5,2) NOT NULL,
  `coordinate_y` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purok1_locations`
--

LOCK TABLES `purok1_locations` WRITE;
/*!40000 ALTER TABLE `purok1_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `purok1_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purok2_locations`
--

DROP TABLE IF EXISTS `purok2_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purok2_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `house_number` varchar(50) NOT NULL,
  `husband_name` varchar(150) NOT NULL,
  `spouse_name` varchar(150) DEFAULT NULL,
  `children` text DEFAULT NULL,
  `house_image` varchar(255) NOT NULL,
  `marker_image` varchar(255) NOT NULL,
  `marker_width` int(11) DEFAULT 40,
  `marker_height` int(11) DEFAULT 40,
  `coordinate_x` decimal(5,2) NOT NULL,
  `coordinate_y` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purok2_locations`
--

LOCK TABLES `purok2_locations` WRITE;
/*!40000 ALTER TABLE `purok2_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `purok2_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purok3_locations`
--

DROP TABLE IF EXISTS `purok3_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purok3_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `house_number` varchar(50) NOT NULL,
  `husband_name` varchar(150) NOT NULL,
  `spouse_name` varchar(150) DEFAULT NULL,
  `children` text DEFAULT NULL,
  `house_image` varchar(255) NOT NULL,
  `marker_image` varchar(255) NOT NULL,
  `marker_width` int(11) DEFAULT 40,
  `marker_height` int(11) DEFAULT 40,
  `coordinate_x` decimal(5,2) NOT NULL,
  `coordinate_y` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purok3_locations`
--

LOCK TABLES `purok3_locations` WRITE;
/*!40000 ALTER TABLE `purok3_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `purok3_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `icon` varchar(100) NOT NULL DEFAULT 'bi-file-earmark-text',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `fee` varchar(100) DEFAULT 'Free',
  `processing_time` varchar(100) DEFAULT 'Same Day',
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `document_file` varchar(255) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT 'Request Document',
  `button_url` varchar(255) DEFAULT 'contact.php',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Barangay Clearance','fa-solid fa-stamp','active','Γé▒50.00','10-15 Minutes','Official certification required for job applications, postal ID, police clearance, and general legal verification.','Recent Cedula (Community Tax Certificate), 1 Valid Government ID, Proof of Residence in Barangay Tabon.','uploads/documents/template_1793250018_6ae2d2e244ea0.jpg','Request Document','contact.php',1,'2026-10-26 04:06:04','2026-10-29 05:00:18'),(2,'Certificate of Indigency','bi-person-badge','active','Free','Same Day','Issued to low-income residents for medical, educational, burial, or legal assistance from DSWD and other public agencies.','Purok Endorsement or Certificate, Valid ID, Proof of Low Income / Case Assessment.','uploads/documents/template_1793250113_6ae2d341a472f.jpg','Request Assistance','contact.php',2,'2026-10-27 01:02:46','2026-10-29 06:07:26'),(3,'Certificate of Residency','fa-solid fa-address-card','active','Free','Same Day','Formal verification confirming that a citizen has been a bonafide continuous resident of Barangay Tabon.','Purok Leader Certification, Valid Government-issued ID or School ID.','uploads/documents/template_1793250037_6ae2d2f59e481.jpg','Request Document','contact.php',3,'2026-10-27 01:02:46','2026-10-29 05:00:37');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sk_officials`
--

DROP TABLE IF EXISTS `sk_officials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sk_officials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `official_key` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sk_officials`
--

LOCK TABLES `sk_officials` WRITE;
/*!40000 ALTER TABLE `sk_officials` DISABLE KEYS */;
INSERT INTO `sk_officials` VALUES (1,'chairperson','Hon. Ryan M. Tubos','SK CHAIRPERSON','uploads/officials/sk_official_chairperson_1793247771.png','Empowering the youth of Barangay Tabon to lead with purpose, integrity, and passion. Together, we build a future anchored in education, leadership development, sportsmanship, and active civic participation.','active','2026-10-29 04:22:38','2026-10-29 04:22:51'),(2,'secretary','Hon. Rosemer G. Banagan','SK SECRETARY','public/image/SK Officials/Rosmer G. Banagan.jpeg',NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38'),(3,'treasurer','Hon. Judith G. Tambagel','SK TREASURER','public/image/SK Officials/Judith G. Tambagel.jpeg',NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38'),(4,'kagawad_1','Hon. Renzarr L. Roxas','SK KAGAWAD 1',NULL,NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38'),(5,'kagawad_2','Hon. Benie L. Ligao','SK KAGAWAD 2',NULL,NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38'),(6,'kagawad_3','Hon. Jhon Mark Charton D. Singue','SK KAGAWAD 3',NULL,NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38'),(7,'kagawad_4','Hon. Mark A. Alpay','SK KAGAWAD 4',NULL,NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38'),(8,'kagawad_5','Hon. Marlon B. Amplao','SK KAGAWAD 5',NULL,NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38'),(9,'kagawad_6','Hon. Verdelyn G. Halilong','SK KAGAWAD 6',NULL,NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38'),(10,'kagawad_7','Hon. Mc Felco T Bugao','SK KAGAWAD 7',NULL,NULL,'active','2026-10-29 04:22:38','2026-10-29 04:22:38');
/*!40000 ALTER TABLE `sk_officials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sm_legend_spots`
--

DROP TABLE IF EXISTS `sm_legend_spots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sm_legend_spots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legend_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `top_position` decimal(6,2) DEFAULT 50.00,
  `left_position` decimal(6,2) DEFAULT 50.00,
  `marker_width` int(11) DEFAULT 40,
  `marker_height` int(11) DEFAULT 40,
  `marker_image` varchar(255) DEFAULT NULL,
  `house_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_legend_id` (`legend_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sm_legend_spots`
--

LOCK TABLES `sm_legend_spots` WRITE;
/*!40000 ALTER TABLE `sm_legend_spots` DISABLE KEYS */;
INSERT INTO `sm_legend_spots` VALUES (6,6,'tabon school',69.45,17.76,40,40,'bi-book-half',NULL,'2026-10-29 05:28:10','2026-10-29 05:28:10'),(7,7,'barangay hall',20.99,51.23,40,40,'bi-building-fill',NULL,'2026-10-29 05:28:34','2026-10-29 05:28:34');
/*!40000 ALTER TABLE `sm_legend_spots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sm_legends`
--

DROP TABLE IF EXISTS `sm_legends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sm_legends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `icon_path` varchar(255) NOT NULL,
  `color_hex` varchar(20) DEFAULT '#6366f1',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `top_position` decimal(6,2) DEFAULT 50.00,
  `left_position` decimal(6,2) DEFAULT 50.00,
  `marker_width` int(11) DEFAULT 40,
  `marker_height` int(11) DEFAULT 40,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sm_legends`
--

LOCK TABLES `sm_legends` WRITE;
/*!40000 ALTER TABLE `sm_legends` DISABLE KEYS */;
INSERT INTO `sm_legends` VALUES (6,'tabon school','bi-book-half','#F59E0B',1,4,69.45,17.76,40,40,NULL,'2026-10-29 05:28:10','2026-10-29 05:28:10'),(7,'barangay hall','bi-building-fill','#10B981',1,2,20.99,51.23,40,40,NULL,'2026-10-29 05:28:34','2026-10-29 05:28:34');
/*!40000 ALTER TABLE `sm_legends` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sm_settings`
--

DROP TABLE IF EXISTS `sm_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sm_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sm_settings`
--

LOCK TABLES `sm_settings` WRITE;
/*!40000 ALTER TABLE `sm_settings` DISABLE KEYS */;
INSERT INTO `sm_settings` VALUES ('legends_seeded','1');
/*!40000 ALTER TABLE `sm_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `name` varchar(150) NOT NULL DEFAULT 'System Administrator',
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','System Administrator','admin@example.com','$2y$10$g6t/aLT1gn5aZkwWyszA.egApmgCp013XzvGF7xdl0WEgJz1J6Dom','admin','2026-10-29 06:17:11','2026-10-31 04:22:17');
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

-- Dump completed on 2026-10-31 18:06:37
