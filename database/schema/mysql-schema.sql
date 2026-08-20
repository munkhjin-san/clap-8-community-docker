/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `Knowledges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Knowledges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `____Knowledges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `____Knowledges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text,
  `content` text,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `actual_result_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actual_result_departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actual_result_report_id` bigint unsigned NOT NULL,
  `project_record_id` int DEFAULT NULL,
  `department_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_departments` json DEFAULT NULL,
  `metrics` json DEFAULT NULL,
  `accounts` json DEFAULT NULL,
  `external_sales` int NOT NULL DEFAULT '0',
  `internal_sales` int NOT NULL DEFAULT '0',
  `sales` int NOT NULL DEFAULT '0',
  `cost_of_goods_sold` int NOT NULL DEFAULT '0',
  `sg_and_a_expenses` int NOT NULL DEFAULT '0',
  `indirect_allocation_expense` int NOT NULL DEFAULT '0',
  `normal_profit` int NOT NULL DEFAULT '0',
  `performance_bonus_reserve` int NOT NULL DEFAULT '0',
  `real_profit` int NOT NULL DEFAULT '0',
  `real_margin` decimal(18,2) DEFAULT NULL,
  `basic_bonus_reserve` int NOT NULL DEFAULT '0',
  `paid_leave_reserve` int NOT NULL DEFAULT '0',
  `welfare_reserve` int NOT NULL DEFAULT '0',
  `refresh_reserve` int NOT NULL DEFAULT '0',
  `manual_adjusted` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `actual_result_dept_report_name_unique` (`actual_result_report_id`,`department_name`),
  KEY `actual_result_departments_updated_by_foreign` (`updated_by`),
  KEY `actual_result_dept_project_report_idx` (`project_record_id`,`actual_result_report_id`),
  CONSTRAINT `actual_result_departments_actual_result_report_id_foreign` FOREIGN KEY (`actual_result_report_id`) REFERENCES `actual_result_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `actual_result_departments_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `actual_result_departments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `actual_result_edit_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actual_result_edit_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actual_result_report_id` bigint unsigned NOT NULL,
  `actual_result_department_id` bigint unsigned DEFAULT NULL,
  `project_record_id` int DEFAULT NULL,
  `department_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `before_value` json DEFAULT NULL,
  `after_value` json DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `edited_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `actual_result_edit_histories_actual_result_department_id_foreign` (`actual_result_department_id`),
  KEY `actual_result_edit_histories_edited_by_foreign` (`edited_by`),
  KEY `actual_result_history_report_created_idx` (`actual_result_report_id`,`created_at`),
  KEY `actual_result_history_project_created_idx` (`project_record_id`,`created_at`),
  CONSTRAINT `actual_result_edit_histories_actual_result_department_id_foreign` FOREIGN KEY (`actual_result_department_id`) REFERENCES `actual_result_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `actual_result_edit_histories_actual_result_report_id_foreign` FOREIGN KEY (`actual_result_report_id`) REFERENCES `actual_result_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `actual_result_edit_histories_edited_by_foreign` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `actual_result_edit_histories_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `actual_result_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actual_result_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `target_month` date NOT NULL,
  `current_upload_id` bigint unsigned DEFAULT NULL,
  `file_metadata` json DEFAULT NULL,
  `summary` json DEFAULT NULL,
  `account_totals` json DEFAULT NULL,
  `result_payload` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `actual_result_reports_target_month_unique` (`target_month`),
  KEY `actual_result_reports_created_by_foreign` (`created_by`),
  KEY `actual_result_reports_updated_by_foreign` (`updated_by`),
  KEY `actual_result_reports_target_month_index` (`target_month`),
  KEY `actual_result_reports_current_upload_id_foreign` (`current_upload_id`),
  CONSTRAINT `actual_result_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `actual_result_reports_current_upload_id_foreign` FOREIGN KEY (`current_upload_id`) REFERENCES `actual_result_uploads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `actual_result_reports_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `actual_result_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actual_result_uploads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actual_result_report_id` bigint unsigned DEFAULT NULL,
  `target_month` date NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stored_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `file_metadata` json DEFAULT NULL,
  `calculated_summary` json DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `actual_result_uploads_actual_result_report_id_foreign` (`actual_result_report_id`),
  KEY `actual_result_uploads_uploaded_by_foreign` (`uploaded_by`),
  KEY `actual_result_uploads_target_month_created_at_index` (`target_month`,`created_at`),
  KEY `actual_result_uploads_file_hash_index` (`file_hash`),
  CONSTRAINT `actual_result_uploads_actual_result_report_id_foreign` FOREIGN KEY (`actual_result_report_id`) REFERENCES `actual_result_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `actual_result_uploads_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_conversation_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_conversation_messages` (
  `id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversation_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `agent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tool_calls` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tool_results` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `usage` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_index` (`conversation_id`,`user_id`,`updated_at`),
  KEY `agent_conversation_messages_user_id_index` (`user_id`),
  KEY `agent_conversation_messages_conversation_id_index` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_conversations` (
  `id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_conversations_user_id_updated_at_index` (`user_id`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_block_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_block_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `app_name` text NOT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `commentable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `commentable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `mentioned_user_ids` json DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `app_comments_commentable_type_commentable_id_created_at_index` (`commentable_type`,`commentable_id`,`created_at`),
  KEY `app_comments_commentable_type_index` (`commentable_type`),
  KEY `app_comments_commentable_id_index` (`commentable_id`),
  KEY `app_comments_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_file_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_file_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `parent_id` int NOT NULL DEFAULT '0',
  `old_parent_id` int DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `path` text,
  `name` text,
  `size` text,
  `extension` text,
  `mime_type` text,
  `folder` int NOT NULL DEFAULT '0',
  `updated_by` int DEFAULT NULL,
  `recycle_flag` int NOT NULL DEFAULT '0',
  `deleted_flag` int NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_folder_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_folder_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `old_parent_id` int DEFAULT NULL,
  `path` text,
  `folder` int NOT NULL DEFAULT '1',
  `color` int DEFAULT '0',
  `recycle_flag` int NOT NULL DEFAULT '0',
  `deleted_flag` int NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_remember_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_remember_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `favorite_tray` int NOT NULL DEFAULT '1',
  `my_task_priority` int NOT NULL DEFAULT '1',
  `file_sort_by` int NOT NULL DEFAULT '0',
  `file_sort_desc` int NOT NULL DEFAULT '1',
  `task_sort_desc` int NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_category_item_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_category_item_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_category_item_id` bigint unsigned NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placeholder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rules` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visible` enum('public','private','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_category_item_fields_asset_category_item_id_foreign` (`asset_category_item_id`),
  CONSTRAINT `asset_category_item_fields_asset_category_item_id_foreign` FOREIGN KEY (`asset_category_item_id`) REFERENCES `asset_category_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_category_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_category_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_data` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_confirm_log_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_confirm_log_use_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_confirm_log_id` bigint unsigned DEFAULT NULL,
  `file_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_confirm_log_use_files_asset_confirm_log_id_index` (`asset_confirm_log_id`),
  KEY `asset_confirm_log_use_files_file_id_index` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_confirm_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_confirm_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_record_id` int NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `external_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_confirm_logs_user_id_foreign` (`user_id`),
  KEY `asset_confirm_logs_asset_record_id_created_at_index` (`asset_record_id`,`created_at`),
  CONSTRAINT `asset_confirm_logs_asset_record_id_foreign` FOREIGN KEY (`asset_record_id`) REFERENCES `asset_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_confirm_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_record_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_record_field_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_record_id` int NOT NULL,
  `asset_category_item_field_id` bigint unsigned NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_record_field_unique` (`asset_record_id`,`asset_category_item_field_id`),
  KEY `asset_record_field_values_asset_category_item_field_id_foreign` (`asset_category_item_field_id`),
  CONSTRAINT `asset_record_field_values_asset_category_item_field_id_foreign` FOREIGN KEY (`asset_category_item_field_id`) REFERENCES `asset_category_item_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_record_field_values_asset_record_id_foreign` FOREIGN KEY (`asset_record_id`) REFERENCES `asset_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_category_item_id` bigint unsigned DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `external_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `office_id` int DEFAULT NULL,
  `item_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `specs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `model_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classification` int DEFAULT NULL,
  `value` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `change_in_status` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_records_asset_category_item_id_foreign` (`asset_category_item_id`),
  CONSTRAINT `asset_records_asset_category_item_id_foreign` FOREIGN KEY (`asset_category_item_id`) REFERENCES `asset_category_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_request_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_request_steps` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `asset_request_id` int DEFAULT NULL,
  `value` int NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_request_id` (`asset_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_record_id` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  `from_user` int DEFAULT NULL,
  `from_external_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_user` int DEFAULT NULL,
  `to_external_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `from_project` int DEFAULT NULL,
  `to_project` int DEFAULT NULL,
  `not_broken` int DEFAULT NULL,
  `return` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `memo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_types` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `used_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_use_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_request_id` int DEFAULT NULL,
  `file_record_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL COMMENT 'Attendance confirmed by user ID',
  `user_code` int NOT NULL,
  `pay_day` text,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date_year_month` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prescribed_working_hours` text,
  `work_type` text,
  `payment_date` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `month_petition` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `working_days_shift` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `normal_working_days` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `holiday_working_days` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `absence_days` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `absence_hour` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `paid_holiday_hours` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `half_day_holiday` int DEFAULT '0',
  `planned_paid_holiday` text,
  `petitionType8_count` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `petitionType7_count` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `petitionType6_count` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `petitionType5_count` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `petitionType4_count` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `petitionType3_count` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `petitionType2_count` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `petitionType1_count` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `condolence_holiday` int DEFAULT '0',
  `special_holiday` int DEFAULT '0',
  `oda_holiday` int DEFAULT '0',
  `comp_holiday` int DEFAULT '0',
  `closed_day` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `working_hours` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `working_hours_no_over` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `over_time` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `night_work_time` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `training_time` int DEFAULT '0',
  `stay_pay` int DEFAULT '0',
  `move_pay` int DEFAULT '0',
  `waiting_pay` int DEFAULT '0',
  `remote_pay` int DEFAULT '0',
  `vehicle_pay` int DEFAULT '0',
  `special_commute_pay` int DEFAULT '0',
  `remote_personal_pay` int DEFAULT '0',
  `remote_company_pay` int DEFAULT '0',
  `expenses` int DEFAULT NULL,
  `incentive` int DEFAULT NULL,
  `mileage` int NOT NULL DEFAULT '0',
  `status_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_year_month` (`date_year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_daily_digests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_daily_digests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `digest_date` date NOT NULL,
  `first_event_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_event_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_count` int NOT NULL,
  `digest_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sealed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_daily_digests_digest_date_unique` (`digest_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `board_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `public_flag` int DEFAULT '0',
  `message_id` int DEFAULT NULL,
  `unsee_users` text,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `board_awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_awards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `award_bet` int DEFAULT NULL COMMENT 'werr',
  `user_id` int DEFAULT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `board_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `board_list` text,
  `active_flag` int NOT NULL DEFAULT '0',
  `deleted_flag` int NOT NULL DEFAULT '0',
  `hide_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `board_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text,
  `icon_text` text,
  `icon_id` int DEFAULT NULL,
  `icon_path` varchar(100) DEFAULT NULL,
  `icon_bg` text,
  `private_flag` int NOT NULL DEFAULT '0',
  `app_type` int DEFAULT '1',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `icon_id` (`icon_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_updated_at` (`updated_at`),
  KEY `idx_private_updated` (`private_flag`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `board_to_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_to_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `admin_flag` int NOT NULL DEFAULT '0',
  `user_id` int DEFAULT NULL,
  `last_message` int DEFAULT NULL,
  `unread_count` int unsigned NOT NULL DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `deleted_status` int DEFAULT '0',
  `left_at` timestamp NULL DEFAULT NULL,
  `pin_flag` int DEFAULT '0',
  `notification` int NOT NULL DEFAULT '0',
  `show_from_start` int NOT NULL DEFAULT '0',
  `view_from` date DEFAULT NULL,
  `last_act` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`),
  KEY `id` (`id`),
  KEY `deleted_flag` (`deleted_flag`),
  KEY `deleted_status` (`deleted_status`),
  KEY `created_at` (`created_at`),
  KEY `idx_user_status_record` (`user_id`,`deleted_status`,`record_id`),
  KEY `btu_user_deleted_unread_record` (`user_id`,`deleted_status`,`deleted_flag`,`unread_count`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `board_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_use_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `board_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `board_use_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_facilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slot` smallint unsigned NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calendar_facilities_type_slot_unique` (`type`,`slot`),
  KEY `calendar_facilities_type_active_index` (`type`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `member_list` text,
  `display_type` text,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_meeting_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_meeting_summaries` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `overview` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `edited_version` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_meeting_summary_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_meeting_summary_details` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `label` text,
  `summary` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `calendar_meeting_summary_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_meeting_summery_id` (`calendar_meeting_summary_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_meeting_summary_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_meeting_summary_steps` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `calendar_meeting_summary_id` bigint DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_meeting_summary_id` (`calendar_meeting_summary_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `release_flag` int DEFAULT '1',
  `temp_flag` tinyint NOT NULL DEFAULT '0',
  `temp_unique_id` varchar(256) DEFAULT NULL,
  `edit_all` int DEFAULT '0',
  `members_only` int DEFAULT '0',
  `repetition_type` int DEFAULT '0',
  `r_group_id` varchar(180) DEFAULT NULL,
  `created_user` int DEFAULT NULL,
  `updated_user` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `board_id` int DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date_start` timestamp NULL DEFAULT NULL,
  `date_end` timestamp NULL DEFAULT NULL,
  `expiration_start` timestamp NULL DEFAULT NULL,
  `expiration_end` timestamp NULL DEFAULT NULL,
  `repeat_week` text,
  `repeat_days` text,
  `repeat_month` text,
  `qualified_institution` int DEFAULT NULL,
  `qualified_car` int DEFAULT NULL,
  `zoom_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `zoom_value` int DEFAULT NULL,
  `zoom_account` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `zoom_account_pass` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zoom_id` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zoom_pass` varchar(180) DEFAULT NULL,
  `zoom_waiting_room` tinyint(1) NOT NULL DEFAULT '0',
  `zoom_ai_companion` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `referrer` text,
  `shift` int NOT NULL DEFAULT '0',
  `task` int DEFAULT NULL,
  `descendant_of` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `real_created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `user_id` (`user_id`),
  KEY `date_start` (`date_start`),
  KEY `date_end` (`date_end`),
  KEY `deleted_flag` (`deleted_flag`),
  KEY `updated_user` (`updated_user`),
  KEY `created_user` (`created_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_repetition_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_repetition_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_use_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_use_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `comp_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`),
  KEY `deleted_flag` (`deleted_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_view_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_view_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `comp_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`),
  KEY `deleted_flag` (`deleted_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `challenge_awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenge_awards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `award_bet` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `challenge_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenge_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content_rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content_goal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `status_flag` int DEFAULT '0',
  `referrer` text,
  `award_entry` int DEFAULT '0',
  `clap_count` int DEFAULT '0',
  `key_users` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `key_tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `app_type` int DEFAULT '4',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `challenge_to_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenge_to_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `challenge_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenge_use_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `result_flag` int NOT NULL DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `challenge_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenge_use_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clap_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clap_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `from_user` int DEFAULT NULL,
  `to_users` text,
  `record_id` int DEFAULT NULL,
  `comment_id` int DEFAULT NULL,
  `app_name` text,
  `app_id` int NOT NULL DEFAULT '0',
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `app_id` (`app_id`),
  KEY `from_user` (`from_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comment_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `app_name` text,
  `record_id` int DEFAULT NULL,
  `messages` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment_type` varchar(50) NOT NULL DEFAULT 'normal',
  `progress_checkpoint` tinyint unsigned DEFAULT NULL,
  `status_to` tinyint unsigned DEFAULT NULL,
  `emoji_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_batch_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_batch_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_batch_id` bigint unsigned NOT NULL,
  `index` int unsigned NOT NULL DEFAULT '0',
  `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duplicate_candidates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `needs_review` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `scan_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `enrich_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `contact_record_id` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_batch_items_contact_batch_id_foreign` (`contact_batch_id`),
  KEY `contact_batch_items_contact_record_id_foreign` (`contact_record_id`),
  KEY `contact_batch_items_status_index` (`status`),
  KEY `contact_batch_items_card_hash_index` (`card_hash`),
  CONSTRAINT `contact_batch_items_contact_batch_id_foreign` FOREIGN KEY (`contact_batch_id`) REFERENCES `contact_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_batch_items_contact_record_id_foreign` FOREIGN KEY (`contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contact_batch_items_chk_1` CHECK (json_valid(`duplicate_candidates`)),
  CONSTRAINT `contact_batch_items_chk_2` CHECK (json_valid(`scan_result`)),
  CONSTRAINT `contact_batch_items_chk_3` CHECK (json_valid(`enrich_result`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_batch_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_batch_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_batch_id` bigint unsigned NOT NULL,
  `stage` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_batch_logs_contact_batch_id_foreign` (`contact_batch_id`),
  KEY `contact_batch_logs_stage_index` (`stage`),
  CONSTRAINT `contact_batch_logs_contact_batch_id_foreign` FOREIGN KEY (`contact_batch_id`) REFERENCES `contact_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_batch_logs_chk_1` CHECK (json_valid(`context`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_batch_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_batch_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `contact_batch_id` bigint unsigned NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `pushed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_batch_notifications_unique` (`user_id`,`contact_batch_id`,`status`),
  KEY `contact_batch_notifications_contact_batch_id_foreign` (`contact_batch_id`),
  KEY `contact_batch_notifications_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `contact_batch_notifications_contact_batch_id_foreign` FOREIGN KEY (`contact_batch_id`) REFERENCES `contact_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_batch_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `pseudo_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_type_id` bigint DEFAULT NULL,
  `type_ids` json DEFAULT NULL,
  `scan_operation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enrich_operation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scan_attempts` int unsigned NOT NULL DEFAULT '0',
  `enrich_attempts` int unsigned NOT NULL DEFAULT '0',
  `scan_requested_at` timestamp NULL DEFAULT NULL,
  `scan_completed_at` timestamp NULL DEFAULT NULL,
  `enrich_requested_at` timestamp NULL DEFAULT NULL,
  `enrich_completed_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_batches_user_id_foreign` (`user_id`),
  KEY `contact_batches_status_index` (`status`),
  KEY `contact_batches_scan_operation_index` (`scan_operation`),
  KEY `contact_batches_enrich_operation_index` (`enrich_operation`),
  CONSTRAINT `contact_batches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contact_batches_chk_1` CHECK (json_valid(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_comment_last_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_comment_last_reads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_comment_last_reads_contact_record_id_user_id_unique` (`contact_record_id`,`user_id`),
  KEY `contact_comment_last_reads_contact_record_id_index` (`contact_record_id`),
  KEY `contact_comment_last_reads_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_crm_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_crm_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_crm_options_type_name_unique` (`type`,`name`),
  KEY `contact_crm_options_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_private_memos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_private_memos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_private_memos_contact_record_id_index` (`contact_record_id`),
  KEY `contact_private_memos_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_record_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_record_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `calendar_record_id` bigint unsigned DEFAULT NULL,
  `activity_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'note',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `happened_at` timestamp NULL DEFAULT NULL,
  `next_action_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_record_activities_user_id_foreign` (`user_id`),
  KEY `contact_record_activities_contact_record_id_index` (`contact_record_id`),
  KEY `contact_record_activities_calendar_record_id_index` (`calendar_record_id`),
  KEY `contact_record_activities_activity_type_index` (`activity_type`),
  KEY `contact_record_activities_happened_at_index` (`happened_at`),
  KEY `contact_record_activities_next_action_on_index` (`next_action_on`),
  CONSTRAINT `contact_record_activities_contact_record_id_foreign` FOREIGN KEY (`contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_record_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_record_calendar_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_record_calendar_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint NOT NULL,
  `calendar_record_id` bigint unsigned NOT NULL,
  `purpose` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_record_calendar_unique` (`contact_record_id`,`calendar_record_id`),
  KEY `contact_record_calendar_records_calendar_record_id_index` (`calendar_record_id`),
  CONSTRAINT `contact_record_calendar_records_contact_record_id_foreign` FOREIGN KEY (`contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_record_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_record_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_record_comments_user_id_foreign` (`user_id`),
  KEY `contact_record_comments_contact_record_id_index` (`contact_record_id`),
  CONSTRAINT `contact_record_comments_contact_record_id_foreign` FOREIGN KEY (`contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_record_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_record_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_record_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_record_histories_contact_record_id_index` (`contact_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_record_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_record_project` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint NOT NULL,
  `project_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_record_project_contact_record_id_project_id_unique` (`contact_record_id`,`project_id`),
  KEY `contact_record_project_project_id_foreign` (`project_id`),
  CONSTRAINT `contact_record_project_contact_record_id_foreign` FOREIGN KEY (`contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_record_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_record_related`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_record_related` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint NOT NULL,
  `related_contact_record_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crr_pair_unique` (`contact_record_id`,`related_contact_record_id`),
  KEY `crr_related_fk` (`related_contact_record_id`),
  CONSTRAINT `crr_contact_fk` FOREIGN KEY (`contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crr_related_fk` FOREIGN KEY (`related_contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_record_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_record_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint NOT NULL,
  `contact_type_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_record_type_contact_record_id_contact_type_id_unique` (`contact_record_id`,`contact_type_id`),
  KEY `contact_record_type_contact_type_id_foreign` (`contact_type_id`),
  CONSTRAINT `contact_record_type_contact_record_id_foreign` FOREIGN KEY (`contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_record_type_contact_type_id_foreign` FOREIGN KEY (`contact_type_id`) REFERENCES `contact_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_record_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_record_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_record_id` bigint NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'owner',
  `private_memo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_record_user_contact_record_id_user_id_unique` (`contact_record_id`,`user_id`),
  KEY `contact_record_user_user_id_foreign` (`user_id`),
  CONSTRAINT `contact_record_user_contact_record_id_foreign` FOREIGN KEY (`contact_record_id`) REFERENCES `contact_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_record_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_records` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `enrichment_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `community_record_id` bigint DEFAULT NULL,
  `contact_type_id` bigint DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name_kana` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_name_kana` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fax` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `strategy` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `position` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `icon_path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `card_path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `card_hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_duplicate` tinyint(1) NOT NULL DEFAULT '0',
  `duplicate_of` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `department_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registered_at` datetime DEFAULT NULL,
  `front_info` text COLLATE utf8mb4_general_ci,
  `back_info` text COLLATE utf8mb4_general_ci,
  `progress_status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fit_rank` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `post_meeting_rank` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `call_assignee_user_id` bigint unsigned DEFAULT NULL,
  `call_assignee` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `product_fit_memo` text COLLATE utf8mb4_general_ci,
  `caution_memo` text COLLATE utf8mb4_general_ci,
  `response_memo` text COLLATE utf8mb4_general_ci,
  `appointment_type` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latest_contacted_on` date DEFAULT NULL,
  `visit_on` date DEFAULT NULL,
  `remote_on` date DEFAULT NULL,
  `visit_time` time DEFAULT NULL,
  `remote_time` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_record_id` (`community_record_id`),
  KEY `data` (`data`(768)),
  KEY `contact_records_card_hash_index` (`card_hash`),
  KEY `contact_records_duplicate_of_index` (`duplicate_of`),
  KEY `contact_records_call_assignee_user_id_foreign` (`call_assignee_user_id`),
  KEY `contact_records_department_name_index` (`department_name`),
  KEY `contact_records_registered_at_index` (`registered_at`),
  KEY `contact_records_progress_status_index` (`progress_status`),
  KEY `contact_records_fit_rank_index` (`fit_rank`),
  KEY `contact_records_post_meeting_rank_index` (`post_meeting_rank`),
  KEY `contact_records_call_assignee_index` (`call_assignee`),
  KEY `contact_records_appointment_type_index` (`appointment_type`),
  KEY `contact_records_latest_contacted_on_index` (`latest_contacted_on`),
  KEY `contact_records_visit_on_index` (`visit_on`),
  KEY `contact_records_remote_on_index` (`remote_on`),
  KEY `contact_records_department_index` (`department`),
  CONSTRAINT `contact_records_call_assignee_user_id_foreign` FOREIGN KEY (`call_assignee_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_review_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_review_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `review_type` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contract_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stored_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rendered_page_paths` json DEFAULT NULL,
  `use_extracted_text` tinyint(1) NOT NULL DEFAULT '0',
  `project_contract_id` bigint unsigned DEFAULT NULL,
  `result_json` json DEFAULT NULL,
  `raw_text` longtext COLLATE utf8mb4_unicode_ci,
  `document_input` json DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_review_jobs_user_id_index` (`user_id`),
  KEY `contract_review_jobs_status_index` (`status`),
  KEY `contract_review_jobs_project_contract_id_index` (`project_contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_no` int DEFAULT NULL COMMENT 'レコード番号',
  `start_date` date DEFAULT NULL COMMENT '適用開始日',
  `basic_wage` int DEFAULT NULL COMMENT '基本給',
  `basic_wage_unit` text COMMENT '基本給単位',
  `position_extra` int DEFAULT NULL COMMENT '役職手当',
  `active_position_extra_unit` text COMMENT '通勤手当単位',
  `other_extra1_name` text COMMENT 'その他手当名1',
  `other_extra1_value` int DEFAULT NULL COMMENT 'その他金額1',
  `other_extra2_name` text COMMENT 'その他手当名2',
  `other_extra2_value` int DEFAULT NULL COMMENT 'その他金額2',
  `other_extra3_name` text COMMENT 'その他手当名3',
  `other_extra3_value` int DEFAULT NULL COMMENT 'その他金額3',
  `insurance_wellware` int DEFAULT NULL COMMENT '保険[厚生年金]',
  `insurance_health` int DEFAULT NULL COMMENT '保険[健康保険]',
  `insurance_employnent` int DEFAULT NULL COMMENT '保険[雇用保険]',
  `insurance_accident` int DEFAULT NULL COMMENT '保険[労災保険]',
  `other_unit1` text COMMENT 'その他単位1',
  `other_unit2` text COMMENT 'その他単位2',
  `other_unit3` text COMMENT 'その他単位3',
  `employee_code` int DEFAULT NULL COMMENT '社員コード',
  `full_name` text COMMENT '氏名',
  `mori_mgnt_office` date DEFAULT NULL COMMENT 'GH⇒もり労務管理事務所',
  `mori_mgnt_office_confirmed` date DEFAULT NULL COMMENT 'もり労務管理事務所_確認済',
  `status` text COMMENT 'ステータス',
  `worker` text COMMENT '作業者',
  `update_possibility` text COMMENT '更新の可能性の有無',
  `employment_status` text COMMENT '雇用形態',
  `position` text COMMENT '職階',
  `job_direction` text COMMENT '業務内容',
  `job_place` text COMMENT '就業場所',
  `address1` text COMMENT '住所1',
  `break_time` text COMMENT '休憩時間',
  `over_time_possibility` text COMMENT '所定外労働時間の有無',
  `over_time_per_month` int DEFAULT NULL COMMENT '月の所定外労働時間',
  `scheduled_work_hour` text COMMENT '所定労働時間',
  `day_off` text COMMENT '休日',
  `annual_paid_leave` text COMMENT '年次有給休暇',
  `other_legal` text COMMENT 'その他法定',
  `trial_period` text COMMENT '試用期間',
  `pay_day` text COMMENT '賃金支払日',
  `bonus` text COMMENT '賞与の支給',
  `wage_change` text COMMENT '賃金の改定',
  `retire_payment` text COMMENT '退職金の支給',
  `retire_age` text COMMENT '定年',
  `reamark` text COMMENT '備考',
  `division` text COMMENT '区分',
  `work_hour` text COMMENT '勤務時間',
  `a_start_time` time DEFAULT NULL COMMENT 'A開始時刻',
  `a_end_time` time DEFAULT NULL COMMENT 'A終了時刻',
  `b_start_time` time DEFAULT NULL COMMENT 'B開始時刻',
  `b_end_time` time DEFAULT NULL COMMENT 'B終了時刻',
  `title` text COMMENT 'タイトル',
  `contract_expire_date` date DEFAULT NULL COMMENT '契約満了日',
  `transport_extra` int DEFAULT NULL COMMENT '通勤手当',
  `contract_create_date` date DEFAULT NULL COMMENT '契約書作成日',
  `private_car` text COMMENT 'マイカー通勤',
  `company_car` text COMMENT '社用車通勤',
  `address2` text COMMENT '住所2',
  `wellfare_pension` text COMMENT '厚生年金',
  `health_insurance` text COMMENT '健康保険',
  `employ_insurance` text COMMENT '雇用保険',
  `accident_insurance` text COMMENT '労災保険',
  `record_auto_duplicate` text COMMENT 'レコード自動複製',
  `gradation_wage_lookup` text COMMENT '号俸_ルックアップ',
  `basic_wage_lookup` int DEFAULT NULL COMMENT '基本給_ルックアップ',
  `one_way_distance` int DEFAULT NULL COMMENT '片道距離',
  `calculation` int DEFAULT NULL COMMENT '計算',
  `distance` int DEFAULT NULL COMMENT '距離',
  `gas_unit_price` int DEFAULT NULL COMMENT 'ガソリン単価',
  `subtotal` int DEFAULT NULL COMMENT '小計',
  `vehicle` text COMMENT '車両',
  `gas_fee` int DEFAULT NULL COMMENT 'ガソリン代',
  `work_at_home_day` date DEFAULT NULL COMMENT '住宅手当期日',
  `gradation_wage` int DEFAULT NULL COMMENT '号俸',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cost_item_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_item_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cost_item_id` bigint unsigned NOT NULL,
  `rate` decimal(18,4) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `source_system` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_app_id` int unsigned DEFAULT NULL,
  `source_record_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_updated_at` timestamp NULL DEFAULT NULL,
  `source_synced_at` timestamp NULL DEFAULT NULL,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `updated_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cost_item_rate_from` (`cost_item_id`,`effective_from`),
  KEY `idx_cost_item_rates_period` (`cost_item_id`,`effective_from`,`effective_to`),
  KEY `idx_cost_item_rates_source` (`source_system`,`source_app_id`,`source_record_id`),
  CONSTRAINT `cost_item_rates_cost_item_id_foreign` FOREIGN KEY (`cost_item_id`) REFERENCES `cost_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cost_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_system` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_app_id` int unsigned DEFAULT NULL,
  `source_record_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_key` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_updated_at` timestamp NULL DEFAULT NULL,
  `source_synced_at` timestamp NULL DEFAULT NULL,
  `account_category` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_rate` decimal(18,4) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `memo` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `updated_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cost_items_source_identity` (`source_system`,`source_key`,`account_category`),
  KEY `idx_cost_items_type_category_active` (`type`,`account_category`,`active`),
  KEY `idx_cost_items_name` (`name`),
  KEY `idx_cost_items_source_key_category` (`source_system`,`source_key`,`account_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_field_data_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_field_data_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int DEFAULT NULL,
  `type_id` int DEFAULT NULL,
  `app_name` text,
  `date` date DEFAULT NULL,
  `table_record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `value_int` int DEFAULT NULL,
  `value_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `label` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`),
  KEY `value_int` (`value_int`),
  KEY `date` (`date`),
  KEY `table_record_id` (`table_record_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_field_emote_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_field_emote_users` (
  `custom_field_data_record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `emote_id` smallint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `emote_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  UNIQUE KEY `custom_field_emote_users_unique` (`custom_field_data_record_id`,`user_id`,`emote_id`),
  KEY `custom_field_emote_users_custom_field_data_record_id_index` (`custom_field_data_record_id`),
  KEY `custom_field_emote_users_user_id_index` (`user_id`),
  KEY `custom_field_emote_users_emote_id_index` (`emote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_field_parts_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_field_parts_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `parts_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `parts_lavel` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `use_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_field_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_field_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` text,
  `app_name` text,
  `user_id` int DEFAULT NULL,
  `use_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_field_type_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_field_type_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` text,
  `app_name` text,
  `record_id` int DEFAULT NULL,
  `form_type` text,
  `form_parts_array` text,
  `help_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `use_flag` int DEFAULT '0',
  `sort_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_form_block_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_form_block_elements` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `custom_form_block_id` bigint DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `has_sub_text` tinyint(1) NOT NULL DEFAULT '0',
  `has_sub_text_required` tinyint(1) NOT NULL DEFAULT '0',
  `has_file_attachment` tinyint(1) NOT NULL DEFAULT '0',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `placeholder` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_form_block_project_checkitem_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_form_block_project_checkitem_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_form_block_id` bigint NOT NULL,
  `project_checkitem_category_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cf_block_checkitem_category_unique` (`custom_form_block_id`,`project_checkitem_category_id`),
  KEY `fk_cct_category_id` (`project_checkitem_category_id`),
  CONSTRAINT `fk_cct_category_id` FOREIGN KEY (`project_checkitem_category_id`) REFERENCES `project_checkitem_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cfblock_pct_cfblock_id` FOREIGN KEY (`custom_form_block_id`) REFERENCES `custom_form_blocks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_form_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_form_blocks` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `custom_form_id` bigint DEFAULT NULL,
  `project_assign_record_id` bigint unsigned DEFAULT NULL,
  `type` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `order_number` int NOT NULL DEFAULT '0',
  `placeholder` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `depends_on` json DEFAULT NULL,
  `categories` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cf_blocks_par_record_id_idx` (`project_assign_record_id`),
  CONSTRAINT `cf_blocks_par_record_id_fk` FOREIGN KEY (`project_assign_record_id`) REFERENCES `project_assign_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_form_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_form_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `custom_form_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `authority` tinyint(1) DEFAULT '0',
  `try_flag` tinyint(1) NOT NULL DEFAULT '0',
  `prize` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_forms` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `community_record_id` bigint DEFAULT NULL,
  `board_record_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `repeat_setting` int NOT NULL DEFAULT '0',
  `repeat_day` int DEFAULT NULL,
  `has_prize` tinyint(1) DEFAULT '0',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `public_token` varchar(64) DEFAULT NULL,
  `status` int DEFAULT '0',
  `usage` varchar(255) NOT NULL DEFAULT 'general',
  `project_type_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_forms_public_token_unique` (`public_token`),
  KEY `board_record_id` (`board_record_id`),
  KEY `custom_forms_project_type_id_foreign` (`project_type_id`),
  CONSTRAINT `custom_forms_project_type_id_foreign` FOREIGN KEY (`project_type_id`) REFERENCES `project_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customfield_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customfield_reads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type_id` int unsigned NOT NULL,
  `last_read_customfield_id` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customfield_reads_user_id_type_id_unique` (`user_id`,`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drive_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `drive_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_path` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_path` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` bigint unsigned DEFAULT NULL,
  `client_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `occurred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drive_activity_logs_project_id_occurred_at_index` (`project_id`,`occurred_at`),
  KEY `drive_activity_logs_item_id_occurred_at_index` (`item_id`,`occurred_at`),
  KEY `drive_activity_logs_action_project_id_index` (`action`,`project_id`),
  KEY `drive_activity_logs_item_id_index` (`item_id`),
  KEY `drive_activity_logs_project_id_index` (`project_id`),
  KEY `drive_activity_logs_user_id_index` (`user_id`),
  CONSTRAINT `drive_activity_logs_chk_1` CHECK (json_valid(`context`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drive_download_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `drive_download_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `node_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_count` int unsigned NOT NULL DEFAULT '1',
  `bytes_expected` bigint unsigned DEFAULT NULL,
  `bytes_sent` bigint unsigned DEFAULT NULL,
  `status` smallint unsigned NOT NULL DEFAULT '200',
  `success` tinyint(1) NOT NULL DEFAULT '1',
  `client_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manifest` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration_ms` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drive_download_logs_node_id_started_at_index` (`node_id`,`started_at`),
  KEY `drive_download_logs_user_id_started_at_index` (`user_id`,`started_at`),
  KEY `drive_download_logs_action_started_at_index` (`action`,`started_at`),
  KEY `drive_download_logs_user_id_index` (`user_id`),
  CONSTRAINT `drive_download_logs_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `drive_nodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `drive_download_logs_chk_1` CHECK (json_valid(`manifest`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drive_node_acls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `drive_node_acls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `node_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('viewer','editor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `inherited_from` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `granted_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `drive_node_acls_node_id_user_id_inherited_from_unique` (`node_id`,`user_id`,`inherited_from`),
  KEY `drive_node_acls_inherited_from_foreign` (`inherited_from`),
  KEY `drive_node_acls_node_id_user_id_role_index` (`node_id`,`user_id`,`role`),
  CONSTRAINT `drive_node_acls_inherited_from_foreign` FOREIGN KEY (`inherited_from`) REFERENCES `drive_nodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `drive_node_acls_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `drive_nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drive_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `drive_nodes` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('folder','file') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ext` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL DEFAULT '0',
  `storage_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_id` int DEFAULT NULL,
  `visibility` enum('public','private') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `drive_nodes_parent_id_name_deleted_at_unique` (`parent_id`,`name`,`deleted_at`),
  KEY `drive_nodes_parent_id_index` (`parent_id`),
  KEY `drive_nodes_owner_id_index` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emergency_contact_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_contact_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `emergency_contact_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emergency_contact_actions_emergency_contact_id_foreign` (`emergency_contact_id`),
  KEY `emergency_contact_actions_user_id_foreign` (`user_id`),
  CONSTRAINT `emergency_contact_actions_emergency_contact_id_foreign` FOREIGN KEY (`emergency_contact_id`) REFERENCES `emergency_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emergency_contact_actions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_change_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_change_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `submitted_by` bigint unsigned NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `effective_date` date DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_comment` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_change_applications_submitted_by_foreign` (`submitted_by`),
  KEY `employee_change_applications_reviewed_by_foreign` (`reviewed_by`),
  KEY `employee_change_applications_user_id_type_index` (`user_id`,`type`),
  KEY `employee_change_applications_status_created_at_index` (`status`,`created_at`),
  KEY `employee_change_applications_type_index` (`type`),
  KEY `employee_change_applications_status_index` (`status`),
  KEY `employee_change_applications_effective_date_index` (`effective_date`),
  CONSTRAINT `employee_change_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_change_applications_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_change_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_commute_change_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_commute_change_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_change_application_id` bigint unsigned NOT NULL,
  `commute_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_date` date DEFAULT NULL,
  `route` text COLLATE utf8mb4_unicode_ci,
  `pass_amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `one_way_fare` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rainy_commute_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parking_amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `one_way_distance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fuel_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `car_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_commute_app_fk` (`employee_change_application_id`),
  KEY `employee_commute_change_details_commute_type_index` (`commute_type`),
  KEY `employee_commute_change_details_effective_date_index` (`effective_date`),
  CONSTRAINT `employee_commute_app_fk` FOREIGN KEY (`employee_change_application_id`) REFERENCES `employee_change_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_leave_application_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_leave_application_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_change_application_id` bigint unsigned NOT NULL,
  `leave_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `illness_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `expected_birth_date` date DEFAULT NULL,
  `maternity_leave_start` date DEFAULT NULL,
  `maternity_leave_end` date DEFAULT NULL,
  `childcare_leave_start` date DEFAULT NULL,
  `childcare_leave_end` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_leave_app_fk` (`employee_change_application_id`),
  KEY `employee_leave_application_details_leave_type_index` (`leave_type`),
  KEY `employee_leave_application_details_start_date_index` (`start_date`),
  KEY `employee_leave_application_details_end_date_index` (`end_date`),
  CONSTRAINT `employee_leave_app_fk` FOREIGN KEY (`employee_change_application_id`) REFERENCES `employee_change_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_profile_change_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_profile_change_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_change_application_id` bigint unsigned NOT NULL,
  `change_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_date` date DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `dependent_action` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annual_income` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dependent_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dependent_name_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `dependent_my_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dependent_address` text COLLATE utf8mb4_unicode_ci,
  `retired_on` date DEFAULT NULL,
  `employment_on` date DEFAULT NULL,
  `work_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` text COLLATE utf8mb4_unicode_ci,
  `monthly_pass_amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `one_way_distance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_profile_change_app_fk` (`employee_change_application_id`),
  KEY `employee_profile_change_details_change_type_index` (`change_type`),
  KEY `employee_profile_change_details_effective_date_index` (`effective_date`),
  KEY `employee_profile_change_details_dependent_action_index` (`dependent_action`),
  CONSTRAINT `employee_profile_change_app_fk` FOREIGN KEY (`employee_change_application_id`) REFERENCES `employee_change_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluation_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluation_candidates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `increase_id` int DEFAULT NULL,
  `evaluation_record_id` int DEFAULT NULL,
  `last_candidate` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `next_candidate` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `increase_id` (`increase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluation_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluation_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `mentor_id` int DEFAULT NULL,
  `general_position` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `new_position` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `current_level` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `employment_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date` date DEFAULT NULL,
  `year` year DEFAULT NULL,
  `which_half` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grade` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `current_salary_rank` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `after_salary_rank` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `vision` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mentor_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` smallint NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `temp_flag` tinyint(1) NOT NULL DEFAULT '0',
  `monthly_goal_slot` int NOT NULL DEFAULT '6',
  `alert_streak` smallint unsigned NOT NULL DEFAULT '0',
  `last_alert_goal_month` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_processed_goal_month` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `users` (`user_id`,`mentor_id`),
  KEY `date` (`date`),
  KEY `year` (`year`),
  KEY `which_half` (`which_half`),
  KEY `evaluation_records_user_id_year_which_half_index` (`user_id`,`year`,`which_half`),
  KEY `evaluation_records_last_alert_goal_month_index` (`last_alert_goal_month`),
  KEY `evaluation_records_last_processed_goal_month_index` (`last_processed_goal_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evaluation_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluation_skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `increase_id` int DEFAULT NULL,
  `evaluation_record_id` int DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `increase_id` (`increase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `file_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_id` bigint unsigned NOT NULL,
  `attachable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachable_id` bigint unsigned NOT NULL,
  `collection` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'attachments',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_attachments_unique` (`file_id`,`attachable_type`,`attachable_id`,`collection`),
  KEY `file_attachables_index` (`attachable_type`,`attachable_id`),
  KEY `file_attachments_file_id_index` (`file_id`),
  CONSTRAINT `file_attachments_file_id_foreign` FOREIGN KEY (`file_id`) REFERENCES `file_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `file_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `path` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `mime_type` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `extension` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `size` int DEFAULT NULL,
  `remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `user_id` int DEFAULT NULL,
  `share_id` int DEFAULT NULL,
  `app_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_app_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_app_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_add` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage` tinyint(1) NOT NULL DEFAULT '0',
  `can_import` tinyint(1) NOT NULL DEFAULT '0',
  `can_export` tinyint(1) NOT NULL DEFAULT '0',
  `can_bulk` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_app_perms_def_sort_idx` (`flow_definition_id`,`sort_order`),
  KEY `flow_app_permissions_flow_definition_id_index` (`flow_definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_app_pins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_app_pins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `flow_definition_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flow_app_pins_user_id_flow_definition_id_unique` (`user_id`,`flow_definition_id`),
  KEY `flow_app_pins_user_id_index` (`user_id`),
  KEY `flow_app_pins_flow_definition_id_index` (`flow_definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_app_tools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_app_tools` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `tool_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_app_tools_flow_definition_id_sort_order_index` (`flow_definition_id`,`sort_order`),
  KEY `flow_app_tools_flow_definition_id_index` (`flow_definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `flow_record_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `flow_audit_logs_flow_definition_id_created_at_index` (`flow_definition_id`,`created_at`),
  KEY `flow_audit_logs_flow_definition_id_action_created_at_index` (`flow_definition_id`,`action`,`created_at`),
  KEY `flow_audit_logs_flow_definition_id_index` (`flow_definition_id`),
  KEY `flow_audit_logs_user_id_index` (`user_id`),
  KEY `flow_audit_logs_flow_record_id_index` (`flow_record_id`),
  KEY `flow_audit_logs_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_definitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_seq` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color_id` tinyint unsigned DEFAULT NULL,
  `icon_svg` text COLLATE utf8mb4_unicode_ci,
  `icon_image` longtext COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `project_record_id` bigint unsigned DEFAULT NULL,
  `visibility` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'limited',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `use_status_flow` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_definitions_created_by_index` (`created_by`),
  KEY `flow_definitions_visibility_index` (`visibility`),
  KEY `flow_definitions_is_active_index` (`is_active`),
  KEY `flow_definitions_project_record_id_index` (`project_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_field_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_field_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `field_id` bigint unsigned NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '1',
  `can_edit` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_field_perms_def_field_idx` (`flow_definition_id`,`field_id`),
  KEY `flow_field_permissions_flow_definition_id_index` (`flow_definition_id`),
  KEY `flow_field_permissions_field_id_index` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` json DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `order_number` int unsigned NOT NULL DEFAULT '0',
  `layout_row` int unsigned NOT NULL DEFAULT '0',
  `width` int unsigned NOT NULL DEFAULT '260',
  `depends_on` json DEFAULT NULL,
  `validation` json DEFAULT NULL,
  `formula` text COLLATE utf8mb4_unicode_ci,
  `result_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_fields_flow_definition_id_order_number_index` (`flow_definition_id`,`order_number`),
  KEY `flow_fields_flow_definition_id_index` (`flow_definition_id`),
  KEY `flow_fields_order_number_index` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_portal_prefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_portal_prefs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `density` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `sort` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created_desc',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flow_portal_prefs_user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_record_assignees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_record_assignees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_record_id` bigint unsigned NOT NULL,
  `flow_status_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flow_record_assignee_unique` (`flow_record_id`,`flow_status_id`,`user_id`),
  KEY `flow_record_assignees_user_id_completed_at_index` (`user_id`,`completed_at`),
  KEY `flow_record_assignees_flow_record_id_index` (`flow_record_id`),
  KEY `flow_record_assignees_flow_status_id_index` (`flow_status_id`),
  KEY `flow_record_assignees_user_id_index` (`user_id`),
  KEY `flow_record_assignees_completed_at_index` (`completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_record_permission_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_record_permission_conditions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `set_id` bigint unsigned NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_id` bigint unsigned DEFAULT NULL,
  `operator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `values` json DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_record_permission_conditions_set_id_index` (`set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_record_permission_grants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_record_permission_grants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `set_id` bigint unsigned NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_record_permission_grants_set_id_index` (`set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_record_permission_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_record_permission_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `match_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_rec_perm_sets_def_sort_idx` (`flow_definition_id`,`sort_order`),
  KEY `flow_record_permission_sets_flow_definition_id_index` (`flow_definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_record_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_record_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_record_id` bigint unsigned NOT NULL,
  `flow_field_id` bigint unsigned NOT NULL,
  `value_text` text COLLATE utf8mb4_unicode_ci,
  `value_numeric` decimal(20,4) DEFAULT NULL,
  `value_date` date DEFAULT NULL,
  `value_datetime` datetime DEFAULT NULL,
  `value_boolean` tinyint(1) DEFAULT NULL,
  `value_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flow_record_values_flow_record_id_flow_field_id_unique` (`flow_record_id`,`flow_field_id`),
  KEY `flow_record_values_flow_record_id_index` (`flow_record_id`),
  KEY `flow_record_values_flow_field_id_index` (`flow_field_id`),
  KEY `flow_record_values_value_numeric_index` (`value_numeric`),
  KEY `flow_record_values_value_date_index` (`value_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `record_number` int unsigned DEFAULT NULL,
  `current_status_id` bigint unsigned DEFAULT NULL,
  `source` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flow_record_number_unique` (`flow_definition_id`,`record_number`),
  KEY `flow_records_flow_definition_id_current_status_id_index` (`flow_definition_id`,`current_status_id`),
  KEY `flow_records_flow_definition_id_index` (`flow_definition_id`),
  KEY `flow_records_current_status_id_index` (`current_status_id`),
  KEY `flow_records_created_by_index` (`created_by`),
  KEY `flow_records_updated_by_index` (`updated_by`),
  KEY `flow_records_source_idx` (`source`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_shares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `position_id` bigint unsigned DEFAULT NULL,
  `access_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'use',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_shares_flow_definition_id_index` (`flow_definition_id`),
  KEY `flow_shares_user_id_index` (`user_id`),
  KEY `flow_shares_position_id_index` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_status_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_status_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `flow_status_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status_id` bigint unsigned DEFAULT NULL,
  `eligible` json DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_status_actions_flow_definition_id_index` (`flow_definition_id`),
  KEY `flow_status_actions_flow_status_id_index` (`flow_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_status_field_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_status_field_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_status_id` bigint unsigned NOT NULL,
  `flow_field_id` bigint unsigned NOT NULL,
  `rule` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'edit',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flow_status_field_rules_flow_status_id_flow_field_id_unique` (`flow_status_id`,`flow_field_id`),
  KEY `flow_status_field_rules_flow_status_id_index` (`flow_status_id`),
  KEY `flow_status_field_rules_flow_field_id_index` (`flow_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_initial` tinyint(1) NOT NULL DEFAULT '0',
  `color` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_number` int unsigned NOT NULL DEFAULT '0',
  `ui_x` int DEFAULT NULL,
  `ui_y` int DEFAULT NULL,
  `is_locked` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignment_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'creator',
  `assignment_target_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_statuses_flow_definition_id_order_number_index` (`flow_definition_id`,`order_number`),
  KEY `flow_statuses_flow_definition_id_index` (`flow_definition_id`),
  KEY `flow_statuses_order_number_index` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flow_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flow_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `flow_definition_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `view_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'table',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `columns` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `sort` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flow_views_flow_definition_id_index` (`flow_definition_id`),
  KEY `flow_views_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gasoline_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gasoline_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rate` decimal(10,2) NOT NULL,
  `effective_from` date NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gasoline_rates_effective_from_index` (`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `groups_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `icons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `icons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `use_of` text,
  `profile_id` int DEFAULT NULL,
  `name` text,
  `path` text,
  `mime_type` text,
  `extension` text NOT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incident_advice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_advice` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_advice_created_by_foreign` (`created_by`),
  KEY `incident_advice_incident_id_type_created_at_index` (`incident_id`,`type`,`created_at`),
  KEY `incident_advice_type_index` (`type`),
  CONSTRAINT `incident_advice_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incident_advice_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incident_assignees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_assignees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_report_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `report` text COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incident_assignees_incident_report_id_user_id_unique` (`incident_report_id`,`user_id`),
  KEY `incident_assignees_user_id_completed_at_index` (`user_id`,`completed_at`),
  KEY `incident_assignees_incident_report_id_index` (`incident_report_id`),
  KEY `incident_assignees_user_id_index` (`user_id`),
  KEY `incident_assignees_completed_at_index` (`completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incident_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_candidates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_user_id` bigint unsigned NOT NULL,
  `project_record_id` bigint unsigned DEFAULT NULL,
  `audience` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` json DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `decision_reason` text COLLATE utf8mb4_unicode_ci,
  `decided_by` bigint unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `resulting_incident_id` bigint unsigned DEFAULT NULL,
  `dedup_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incident_candidate_dedup_unique` (`source_type`,`subject_user_id`,`dedup_key`),
  KEY `incident_candidates_subject_user_id_foreign` (`subject_user_id`),
  KEY `incident_candidates_decided_by_foreign` (`decided_by`),
  KEY `incident_candidates_resulting_incident_id_foreign` (`resulting_incident_id`),
  KEY `incident_candidates_audience_status_index` (`audience`,`status`),
  KEY `incident_candidates_project_record_id_status_index` (`project_record_id`,`status`),
  KEY `incident_candidates_source_type_index` (`source_type`),
  KEY `incident_candidates_audience_index` (`audience`),
  KEY `incident_candidates_status_index` (`status`),
  CONSTRAINT `incident_candidates_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incident_candidates_resulting_incident_id_foreign` FOREIGN KEY (`resulting_incident_id`) REFERENCES `incidents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incident_candidates_subject_user_id_foreign` FOREIGN KEY (`subject_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incident_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incident_punishments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_punishments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incident_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_id` bigint unsigned NOT NULL,
  `step` int unsigned NOT NULL DEFAULT '1',
  `user_id` bigint unsigned DEFAULT NULL,
  `report` text COLLATE utf8mb4_unicode_ci,
  `request` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_reports_incident_id_index` (`incident_id`),
  KEY `incident_reports_user_id_index` (`user_id`),
  KEY `incident_reports_step_index` (`step`),
  KEY `incident_reports_created_by_index` (`created_by`),
  KEY `incident_reports_completed_at_index` (`completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incident_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incident_statuses_name_unique` (`name`),
  KEY `incident_statuses_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reported_by` bigint unsigned DEFAULT NULL,
  `reported_date` date DEFAULT NULL,
  `caused_by` bigint unsigned DEFAULT NULL,
  `incident_category_id` bigint unsigned DEFAULT NULL,
  `incident_punishment_id` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `prevention` text COLLATE utf8mb4_unicode_ci,
  `prevention_apply_status` text COLLATE utf8mb4_unicode_ci,
  `instruction` text COLLATE utf8mb4_unicode_ci,
  `resolution` text COLLATE utf8mb4_unicode_ci,
  `occured_location` text COLLATE utf8mb4_unicode_ci,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `aftermath_comment` text COLLATE utf8mb4_unicode_ci,
  `occurred_date` date DEFAULT NULL,
  `instruction_date` date DEFAULT NULL,
  `related_parties` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_record_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_of_damage` double DEFAULT NULL,
  `payee` text COLLATE utf8mb4_unicode_ci,
  `expense_details` text COLLATE utf8mb4_unicode_ci,
  `risk_level` int DEFAULT NULL,
  `severity_level` int DEFAULT NULL,
  `private_notes` text COLLATE utf8mb4_unicode_ci,
  `committee_members` text COLLATE utf8mb4_unicode_ci,
  `committee_decision` text COLLATE utf8mb4_unicode_ci,
  `committee_decision_date` date DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incidents_project_record_id_status_index` (`project_record_id`,`status`),
  KEY `incidents_reported_by_status_index` (`reported_by`,`status`),
  KEY `incidents_project_record_id_reported_by_index` (`project_record_id`,`reported_by`),
  KEY `incidents_reported_by_index` (`reported_by`),
  KEY `incidents_caused_by_index` (`caused_by`),
  KEY `incidents_incident_category_id_index` (`incident_category_id`),
  KEY `incidents_incident_punishment_id_index` (`incident_punishment_id`),
  KEY `incidents_occurred_date_index` (`occurred_date`),
  KEY `incidents_instruction_date_index` (`instruction_date`),
  KEY `incidents_project_record_id_index` (`project_record_id`),
  KEY `incidents_status_index` (`status`),
  KEY `incidents_reported_date_index` (`reported_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `info_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `info_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `related_users` text,
  `record_id` int DEFAULT NULL,
  `sub_record_id` int DEFAULT NULL,
  `app_name` text,
  `action` text,
  `level` int NOT NULL DEFAULT '0',
  `seen` text,
  `confirmed` text,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`),
  KEY `sub_record_id` (`sub_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `referrer` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `key_users` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `key_tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `app_type` int DEFAULT '2',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_to_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_to_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_use_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_use_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_access` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_theme_id` int NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lesson_access_lesson_theme_id_user_id_unique` (`lesson_theme_id`,`user_id`),
  KEY `lesson_access_user_id_lesson_theme_id_index` (`user_id`,`lesson_theme_id`),
  CONSTRAINT `lesson_access_lesson_theme_id_foreign` FOREIGN KEY (`lesson_theme_id`) REFERENCES `lesson_themes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_access_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `material_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `answer` text,
  `ai_review` text,
  `cant_understand` text,
  `reason_dnt_und` text,
  `status` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_exam_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_exam_answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_exam_attempt_id` bigint unsigned NOT NULL,
  `lesson_exam_question_id` bigint unsigned NOT NULL,
  `lesson_exam_option_id` bigint unsigned DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_answer_unique` (`lesson_exam_attempt_id`,`lesson_exam_question_id`),
  KEY `lesson_exam_answers_lesson_exam_question_id_foreign` (`lesson_exam_question_id`),
  KEY `lesson_exam_answers_lesson_exam_option_id_foreign` (`lesson_exam_option_id`),
  CONSTRAINT `lesson_exam_answers_lesson_exam_attempt_id_foreign` FOREIGN KEY (`lesson_exam_attempt_id`) REFERENCES `lesson_exam_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_exam_answers_lesson_exam_option_id_foreign` FOREIGN KEY (`lesson_exam_option_id`) REFERENCES `lesson_exam_options` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lesson_exam_answers_lesson_exam_question_id_foreign` FOREIGN KEY (`lesson_exam_question_id`) REFERENCES `lesson_exam_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_exam_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_exam_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_exam_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `score` int unsigned NOT NULL DEFAULT '0',
  `attempt_number` int unsigned NOT NULL DEFAULT '1',
  `status` enum('passed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'failed',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_exam_attempts_lesson_exam_id_foreign` (`lesson_exam_id`),
  KEY `lesson_exam_attempts_user_id_foreign` (`user_id`),
  CONSTRAINT `lesson_exam_attempts_lesson_exam_id_foreign` FOREIGN KEY (`lesson_exam_id`) REFERENCES `lesson_exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_exam_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_exam_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_exam_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_exam_question_id` bigint unsigned NOT NULL,
  `label` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_exam_options_lesson_exam_question_id_foreign` (`lesson_exam_question_id`),
  CONSTRAINT `lesson_exam_options_lesson_exam_question_id_foreign` FOREIGN KEY (`lesson_exam_question_id`) REFERENCES `lesson_exam_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_exam_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_exam_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_exam_id` bigint unsigned NOT NULL,
  `prompt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `correct_explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_exam_questions_lesson_exam_id_foreign` (`lesson_exam_id`),
  CONSTRAINT `lesson_exam_questions_lesson_exam_id_foreign` FOREIGN KEY (`lesson_exam_id`) REFERENCES `lesson_exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_exams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_theme_id` int NOT NULL,
  `lesson_material_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `passing_score` int unsigned NOT NULL DEFAULT '80',
  `max_attempts` int unsigned NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_exams_lesson_theme_id_foreign` (`lesson_theme_id`),
  KEY `lesson_exams_created_by_foreign` (`created_by`),
  KEY `lesson_exams_updated_by_foreign` (`updated_by`),
  KEY `lesson_exams_lesson_material_id_index` (`lesson_material_id`),
  CONSTRAINT `lesson_exams_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lesson_exams_lesson_theme_id_foreign` FOREIGN KEY (`lesson_theme_id`) REFERENCES `lesson_themes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_exams_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_forms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `lesson_theme_id` int NOT NULL,
  `question1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `answer1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `question2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `answer2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `question3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `answer3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_material_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_material_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_theme_id` int NOT NULL,
  `version_no` int unsigned NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lesson_material_versions_theme_version_unique` (`lesson_theme_id`,`version_no`),
  KEY `lesson_material_versions_lesson_theme_id_index` (`lesson_theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_theme_id` int DEFAULT NULL,
  `lesson_material_version_id` bigint unsigned DEFAULT NULL,
  `assistant_id` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prompt_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '0',
  `user_id` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content_detailed` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `has_feedback` tinyint(1) NOT NULL DEFAULT '0',
  `has_question` int DEFAULT '0',
  `has_understand` int NOT NULL DEFAULT '1',
  `has_exam` tinyint(1) NOT NULL DEFAULT '0',
  `material_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `retired_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_materials_retired_at_index` (`retired_at`),
  KEY `lesson_materials_lesson_material_version_id_index` (`lesson_material_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_personal_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_personal_materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_theme_id` int NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `lesson_theme_ai_config_id` bigint unsigned DEFAULT NULL,
  `config_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `understand` tinyint(1) DEFAULT NULL,
  `important_point` longtext COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `source_snapshot` json DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lesson_personal_material_unique` (`lesson_theme_id`,`user_id`,`config_key`),
  KEY `lesson_personal_materials_user_id_foreign` (`user_id`),
  KEY `lesson_personal_material_ai_config_foreign` (`lesson_theme_ai_config_id`),
  CONSTRAINT `lesson_personal_material_ai_config_foreign` FOREIGN KEY (`lesson_theme_ai_config_id`) REFERENCES `lesson_theme_ai_configs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lesson_personal_materials_lesson_theme_id_foreign` FOREIGN KEY (`lesson_theme_id`) REFERENCES `lesson_themes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_personal_materials_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_portfolio_deletion_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_portfolio_deletion_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_portfolio_id` bigint unsigned NOT NULL,
  `lesson_theme_id` bigint unsigned DEFAULT NULL,
  `owner_user_id` bigint unsigned NOT NULL,
  `deleted_by` bigint unsigned NOT NULL,
  `attempt_no` int unsigned DEFAULT NULL,
  `status` int DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `snapshot` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_portfolio_deletion_logs_lesson_portfolio_id_index` (`lesson_portfolio_id`),
  KEY `lesson_portfolio_deletion_logs_lesson_theme_id_index` (`lesson_theme_id`),
  KEY `lesson_portfolio_deletion_logs_owner_user_id_index` (`owner_user_id`),
  KEY `lesson_portfolio_deletion_logs_deleted_by_index` (`deleted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_portfolios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_portfolios` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `lesson_theme_id` bigint NOT NULL,
  `attempt_no` int unsigned NOT NULL DEFAULT '1',
  `salary_issue_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `understand` int NOT NULL DEFAULT '0',
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `portfolio_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `episode` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `public_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `public_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `noticed` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `discussion_topic` text COLLATE utf8mb4_general_ci,
  `basic_knowledge` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `positive_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `negative_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ai_review_pre` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ai_review_final` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `lesson_theme_id` (`lesson_theme_id`),
  KEY `lesson_portfolios_salary_issue_id_index` (`salary_issue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `portfolio_id` int DEFAULT NULL,
  `material_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portfolio_id` (`portfolio_id`),
  KEY `material_id` (`material_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_summaries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_material_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_material_id` (`lesson_material_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_summary_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_summary_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `lesson_summary_id` int DEFAULT NULL,
  `lesson_summary_question_id` int DEFAULT NULL,
  `answer_val` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_summary_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_summary_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_summary_id` int DEFAULT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_theme_ai_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_theme_ai_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_theme_id` int NOT NULL,
  `config_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'theme_general',
  `lesson_material_id` int DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` longtext COLLATE utf8mb4_unicode_ci,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lesson_theme_ai_configs_theme_key_unique` (`lesson_theme_id`,`config_key`),
  KEY `lesson_theme_ai_configs_material_index` (`lesson_material_id`),
  CONSTRAINT `lesson_theme_ai_configs_lesson_material_id_foreign` FOREIGN KEY (`lesson_material_id`) REFERENCES `lesson_materials` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lesson_theme_ai_configs_lesson_theme_id_foreign` FOREIGN KEY (`lesson_theme_id`) REFERENCES `lesson_themes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_theme_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_theme_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_theme_category_theme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_theme_category_theme` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesson_theme_category_id` bigint unsigned NOT NULL,
  `lesson_theme_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lesson_theme_category_theme_unique` (`lesson_theme_category_id`,`lesson_theme_id`),
  KEY `lesson_theme_category_theme_lesson_theme_id_foreign` (`lesson_theme_id`),
  CONSTRAINT `lesson_theme_category_theme_lesson_theme_category_id_foreign` FOREIGN KEY (`lesson_theme_category_id`) REFERENCES `lesson_theme_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_theme_category_theme_lesson_theme_id_foreign` FOREIGN KEY (`lesson_theme_id`) REFERENCES `lesson_themes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_themes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `archive` tinyint(1) NOT NULL DEFAULT '0',
  `salary_issue_target` tinyint(1) NOT NULL DEFAULT '0',
  `axis` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `previous_version` int DEFAULT NULL,
  `portfolio` tinyint(1) NOT NULL DEFAULT '1',
  `has_case_study` int DEFAULT '0',
  `prompt_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assistant_id` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `custom_form_id` bigint DEFAULT NULL,
  `discussion_date` date DEFAULT NULL,
  `guidance` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `episode_guidance` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `title_guidance` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_form_id` (`custom_form_id`),
  KEY `lesson_themes_previous_version_index` (`previous_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `memo_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `memo_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `board_id` int DEFAULT NULL,
  `message_id` int DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `board_id` (`board_id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `path` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `mime_type` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `extension` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `size` int DEFAULT NULL,
  `remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `user_id` int DEFAULT NULL,
  `share_id` int DEFAULT NULL,
  `app_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_check_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_check_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_check_users_message_record_id_foreign` (`message_record_id`),
  KEY `message_check_users_user_id_foreign` (`user_id`),
  KEY `mr_user_message_idx` (`user_id`,`message_record_id`),
  CONSTRAINT `message_check_users_message_record_id_foreign` FOREIGN KEY (`message_record_id`) REFERENCES `message_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_check_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_emote_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_emote_users` (
  `message_record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `emote_id` smallint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `emote_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  UNIQUE KEY `message_emote_users_unique` (`message_record_id`,`user_id`,`emote_id`),
  KEY `message_emote_users_message_record_id_index` (`message_record_id`),
  KEY `message_emote_users_user_id_index` (`user_id`),
  KEY `message_emote_users_emote_id_index` (`emote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int DEFAULT NULL,
  `board_id` int DEFAULT NULL,
  `salary_issue_report_id` int DEFAULT NULL,
  `comment_record_id` int DEFAULT NULL,
  `contact_record_id` bigint unsigned DEFAULT NULL,
  `contact_file_kind` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_comment_id` bigint unsigned DEFAULT NULL,
  `project_goal_report_id` int DEFAULT NULL,
  `project_checkitem_report_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mime_type` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `extension` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `size` int DEFAULT NULL,
  `edit_flag` timestamp NULL DEFAULT NULL,
  `edit_user` int DEFAULT NULL,
  `sign_flag` int DEFAULT '0',
  `multiple_flag` int DEFAULT '0',
  `original_file_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  KEY `board_id` (`board_id`),
  KEY `sign_flag` (`sign_flag`),
  KEY `message_files_project_goal_report_id_index` (`project_goal_report_id`),
  KEY `message_files_salary_issue_report_id_index` (`salary_issue_report_id`),
  KEY `message_files_comment_record_id_index` (`comment_record_id`),
  KEY `board_id_created_at` (`board_id`,`created_at`),
  KEY `mf_message_deleted` (`message_id`,`deleted_at`),
  KEY `message_files_app_comment_id_index` (`app_comment_id`),
  KEY `message_files_contact_record_id_index` (`contact_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_reacted_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_reacted_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_reacted_users_message_record_id_foreign` (`message_record_id`),
  KEY `message_reacted_users_user_id_foreign` (`user_id`),
  KEY `mr_user_message_idx` (`user_id`,`message_record_id`),
  CONSTRAINT `message_reacted_users_message_record_id_foreign` FOREIGN KEY (`message_record_id`) REFERENCES `message_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_reacted_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `project_record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `actual_sender_id` int DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `message_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `check_flag` int DEFAULT '0',
  `emoji_flag` int NOT NULL DEFAULT '0',
  `info_flag` int DEFAULT '0',
  `draft_flag` int NOT NULL DEFAULT '0',
  `reacted_users` text,
  `checked_users` text,
  `unchecked_users` text,
  `app_export` text,
  `reply_flag` int DEFAULT NULL,
  `reply_id` int DEFAULT NULL,
  `quot_flag` int DEFAULT NULL,
  `quot_id` int DEFAULT NULL,
  `quot_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `forward_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `check_request_at` timestamp NULL DEFAULT NULL,
  `check_request_deadline` timestamp NULL DEFAULT NULL,
  `reserved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `deleted_flag` (`deleted_flag`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `check_request_at` (`check_request_at`),
  KEY `actual_sender_id` (`actual_sender_id`),
  KEY `idx_record_draft_created` (`record_id`,`draft_flag`,`created_at`),
  KEY `idx_messages_record_draft_deleted_created_id` (`record_id`,`draft_flag`,`deleted_at`,`created_at`,`id`),
  KEY `msg_board_trash_flag_created` (`record_id`,`deleted_flag`,`deleted_at`,`created_at`),
  KEY `reply_id` (`reply_id`),
  KEY `quot_id` (`quot_id`),
  KEY `forward_id` (`forward_id`),
  KEY `reserved_at` (`reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_remind_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_remind_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `reminded` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `message_id` (`message_id`),
  KEY `reminded` (`reminded`),
  KEY `mr_user_message_idx` (`user_id`,`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_sign_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_sign_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_file_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `signed` tinyint(1) NOT NULL DEFAULT '0',
  `cancel_flag` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `message_file_id` (`message_file_id`),
  KEY `cancel_flag` (`cancel_flag`),
  KEY `mr_user_message_idx` (`user_id`,`message_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `my_group_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `my_group_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `selected_as_calendar_member` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `my_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `my_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deleted_flag` int DEFAULT '0',
  `selected` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `my_work_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `my_work_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `work_group_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `native_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `native_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `fcm_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active_flag` int DEFAULT '0',
  `mobile_flag` int NOT NULL DEFAULT '0',
  `referrer` text,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nice_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nice_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `referrer` text,
  `clap_count` int DEFAULT '0',
  `key_users` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `key_tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `app_type` int DEFAULT '3',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nice_to_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nice_to_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nice_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nice_use_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nice_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nice_use_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tag_id` (`tag_id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notice_edit_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice_edit_histories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `old_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `old_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notice_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mime_type` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `extension` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `size` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notice_readers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice_readers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `notice_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notice_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `public_flag` int NOT NULL DEFAULT '0',
  `priority_flag` int NOT NULL DEFAULT '0',
  `publish_flag` int NOT NULL DEFAULT '0',
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `read_users` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `unread_users` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `app_type` int DEFAULT '0',
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notice_tag_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice_tag_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notice_to_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice_to_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notice_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notice_use_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `o_auth_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `o_auth_credentials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `service` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calendar_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `access_token_enc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `refresh_token_enc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `id_token_enc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NULL DEFAULT NULL,
  `token_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scope` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_oauth_cred` (`user_id`,`provider`,`service`,`account_email`),
  KEY `o_auth_credentials_provider_index` (`provider`),
  KEY `o_auth_credentials_account_email_index` (`account_email`),
  CONSTRAINT `o_auth_credentials_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `office_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `office_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text,
  `post_code_1` text,
  `post_code_2` text,
  `address` text,
  `tel` text,
  `fax` text,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `offices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `address` text,
  `tel` text,
  `fax` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `olddb_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `olddb_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_code` int DEFAULT NULL,
  `username` text,
  `password` text,
  `name` text,
  `kana` text,
  `mail` text,
  `office_id` int DEFAULT NULL,
  `title_id` int DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `employment_id` int DEFAULT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `nice_from_count` int NOT NULL DEFAULT '0',
  `nice_to_count` int NOT NULL DEFAULT '0',
  `nice_score` float NOT NULL DEFAULT '0',
  `clap_from_count` int NOT NULL DEFAULT '0',
  `clap_to_count` int NOT NULL DEFAULT '0',
  `clap_score` float NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_date` datetime DEFAULT NULL,
  `flag_niceclap_ng` tinyint(1) NOT NULL DEFAULT '0',
  `features_allow` text,
  `features` text NOT NULL,
  `retire` tinyint(1) NOT NULL DEFAULT '0',
  `memo` text,
  `cache_count_nice` int NOT NULL DEFAULT '0',
  `cache_count_niced` int NOT NULL DEFAULT '0',
  `cache_count_clap` int NOT NULL DEFAULT '0',
  `cache_point_nice` float NOT NULL DEFAULT '0',
  `cache_point_clap` float NOT NULL DEFAULT '0',
  `cache_count_clapped` int NOT NULL DEFAULT '0',
  `mail2` text,
  `mail_send` int DEFAULT '0',
  `setting_times_nice_week` int DEFAULT '0',
  `setting_times_clap_week` int DEFAULT '0',
  `setting_point_nice` int DEFAULT '0',
  `setting_point_clap` float DEFAULT '0',
  `icalendar` int NOT NULL DEFAULT '0',
  `compliance` tinyint(1) NOT NULL DEFAULT '0',
  `tel` text,
  `filename` text,
  `filesize` int DEFAULT NULL,
  `content_type` text,
  `filename_disk` text,
  `cache_count_nice_comment` int NOT NULL DEFAULT '0',
  `cache_count_nice_commented` int NOT NULL DEFAULT '0',
  `setting_point_clap_comment` float DEFAULT '0',
  `setting_point_clap_thread` float DEFAULT '0',
  `setting_point_clap_response` float DEFAULT '0',
  `pr` text,
  `url_interview` text,
  `name_en` text,
  `hobby` text,
  `work` text,
  `words` text,
  `flex_flag` int NOT NULL DEFAULT '0',
  `flex_time` text,
  `consign_flag` int DEFAULT NULL,
  `attendance_flag` int DEFAULT NULL,
  `award_charge` text,
  `payment_date` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paid_leave_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paid_leave_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `user_code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `last_granted_at` timestamp NULL DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `source_system` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'glowd',
  `source_app_id` bigint unsigned DEFAULT NULL,
  `source_record_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_payload` json DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paid_leave_accounts_user_id_unique` (`user_id`),
  KEY `paid_leave_accounts_user_code_index` (`user_code`),
  KEY `paid_leave_accounts_active_joined_date_index` (`active`,`joined_date`),
  CONSTRAINT `paid_leave_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paid_leave_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paid_leave_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paid_leave_account_id` bigint unsigned NOT NULL,
  `paid_leave_grant_id` bigint unsigned DEFAULT NULL,
  `adjusted_on` date NOT NULL,
  `amount_minutes` int NOT NULL,
  `adjustment_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `source_system` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'glowd',
  `source_key` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_app_id` bigint unsigned DEFAULT NULL,
  `source_record_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paid_leave_adjustments_source_unique` (`source_system`,`source_key`),
  KEY `paid_leave_adjustments_paid_leave_grant_id_foreign` (`paid_leave_grant_id`),
  KEY `paid_leave_adjustments_paid_leave_account_id_adjusted_on_index` (`paid_leave_account_id`,`adjusted_on`),
  CONSTRAINT `paid_leave_adjustments_paid_leave_account_id_foreign` FOREIGN KEY (`paid_leave_account_id`) REFERENCES `paid_leave_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paid_leave_adjustments_paid_leave_grant_id_foreign` FOREIGN KEY (`paid_leave_grant_id`) REFERENCES `paid_leave_grants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paid_leave_grant_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paid_leave_grant_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paid_leave_policy_id` bigint unsigned NOT NULL,
  `service_months` smallint unsigned NOT NULL,
  `legal_min_days` decimal(5,2) NOT NULL,
  `grant_days` decimal(5,2) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `memo` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `updated_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paid_leave_rules_policy_month_unique` (`paid_leave_policy_id`,`service_months`),
  KEY `paid_leave_rules_policy_active_sort_index` (`paid_leave_policy_id`,`active`,`sort_order`),
  CONSTRAINT `paid_leave_grant_rules_paid_leave_policy_id_foreign` FOREIGN KEY (`paid_leave_policy_id`) REFERENCES `paid_leave_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paid_leave_grants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paid_leave_grants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paid_leave_account_id` bigint unsigned NOT NULL,
  `paid_leave_policy_id` bigint unsigned DEFAULT NULL,
  `grant_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'annual',
  `granted_at` date NOT NULL,
  `expires_at` date DEFAULT NULL,
  `service_months` smallint unsigned DEFAULT NULL,
  `grant_days` decimal(6,2) NOT NULL,
  `amount_minutes` int NOT NULL,
  `remaining_minutes` int NOT NULL,
  `planned_required_minutes` int NOT NULL DEFAULT '0',
  `policy_snapshot` json DEFAULT NULL,
  `source_system` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'glowd',
  `source_key` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_app_id` bigint unsigned DEFAULT NULL,
  `source_record_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paid_leave_grants_account_source_unique` (`paid_leave_account_id`,`source_system`,`source_key`),
  KEY `paid_leave_grants_paid_leave_policy_id_foreign` (`paid_leave_policy_id`),
  KEY `paid_leave_grants_paid_leave_account_id_granted_at_index` (`paid_leave_account_id`,`granted_at`),
  KEY `paid_leave_grants_paid_leave_account_id_expires_at_index` (`paid_leave_account_id`,`expires_at`),
  CONSTRAINT `paid_leave_grants_paid_leave_account_id_foreign` FOREIGN KEY (`paid_leave_account_id`) REFERENCES `paid_leave_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paid_leave_grants_paid_leave_policy_id_foreign` FOREIGN KEY (`paid_leave_policy_id`) REFERENCES `paid_leave_policies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paid_leave_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paid_leave_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_from` date DEFAULT NULL,
  `first_grant_after_months` smallint unsigned NOT NULL DEFAULT '6',
  `annual_grant_interval_months` smallint unsigned NOT NULL DEFAULT '12',
  `expires_after_months` smallint unsigned NOT NULL DEFAULT '24',
  `minimum_attendance_rate` decimal(5,2) NOT NULL DEFAULT '80.00',
  `carryover_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `hourly_leave_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `hourly_deduction_unit_minutes` smallint unsigned NOT NULL DEFAULT '60',
  `minutes_per_leave_day` smallint unsigned NOT NULL DEFAULT '480',
  `max_hourly_leave_days_per_year` decimal(5,2) NOT NULL DEFAULT '5.00',
  `allow_negative_balance` tinyint(1) NOT NULL DEFAULT '0',
  `memo` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `updated_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paid_leave_policies_name_unique` (`name`),
  KEY `paid_leave_policies_active_effective_from_index` (`active`,`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paid_leave_usage_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paid_leave_usage_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paid_leave_usage_id` bigint unsigned NOT NULL,
  `paid_leave_grant_id` bigint unsigned NOT NULL,
  `amount_minutes` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paid_leave_usage_grant_unique` (`paid_leave_usage_id`,`paid_leave_grant_id`),
  KEY `paid_leave_usage_allocations_paid_leave_grant_id_foreign` (`paid_leave_grant_id`),
  CONSTRAINT `paid_leave_usage_allocations_paid_leave_grant_id_foreign` FOREIGN KEY (`paid_leave_grant_id`) REFERENCES `paid_leave_grants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paid_leave_usage_allocations_paid_leave_usage_id_foreign` FOREIGN KEY (`paid_leave_usage_id`) REFERENCES `paid_leave_usages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paid_leave_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paid_leave_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paid_leave_account_id` bigint unsigned NOT NULL,
  `shift_record_id` bigint unsigned DEFAULT NULL,
  `used_on` date NOT NULL,
  `amount_minutes` int NOT NULL,
  `usage_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'shift',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `source_system` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'glowd',
  `source_key` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paid_leave_usages_source_unique` (`source_system`,`source_key`),
  KEY `paid_leave_usages_shift_record_id_foreign` (`shift_record_id`),
  KEY `paid_leave_usages_paid_leave_account_id_used_on_index` (`paid_leave_account_id`,`used_on`),
  CONSTRAINT `paid_leave_usages_paid_leave_account_id_foreign` FOREIGN KEY (`paid_leave_account_id`) REFERENCES `paid_leave_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paid_leave_usages_shift_record_id_foreign` FOREIGN KEY (`shift_record_id`) REFERENCES `shift_records` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `petition_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `petition_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `petition_month` text,
  `petition_day` date DEFAULT NULL,
  `petition_type` int DEFAULT NULL,
  `petition_value` int DEFAULT NULL,
  `status_flag` int DEFAULT '0',
  `authorizer_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `petition_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `petition_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text,
  `abbreviation` text,
  `value` int DEFAULT NULL,
  `deleted_flag` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planned_leave_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planned_leave_change_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_record_id` bigint unsigned DEFAULT NULL,
  `approver_id` bigint unsigned DEFAULT NULL,
  `original_date` date NOT NULL,
  `requested_date` date NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pm_approval_required` tinyint(1) NOT NULL DEFAULT '0',
  `project_id` bigint unsigned DEFAULT NULL,
  `pm_id` bigint unsigned DEFAULT NULL,
  `pm_approval_date` timestamp NULL DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `planned_leave_change_requests_user_id_foreign` (`user_id`),
  KEY `plcr_sr_id_index` (`shift_record_id`),
  CONSTRAINT `planned_leave_change_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `position_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `position_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `sort_flag` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_awards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `award_bet` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_batch_id` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_awards_refunded_at_index` (`refunded_at`),
  KEY `post_awards_refund_batch_id_index` (`refund_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_record_id` int NOT NULL,
  `calories` int NOT NULL DEFAULT '0',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_record_id` (`post_record_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_entry_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_entry_use_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_entry_use_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_entry_use_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_grants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_grants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_record_id` int DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `expenses` int DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_grants_deleted_at_unique` (`deleted_at`),
  KEY `post_grants_post_record_id_index` (`post_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content_rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content_goal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `status_flag` int NOT NULL DEFAULT '0',
  `award_entry` int NOT NULL DEFAULT '0',
  `chargeable` tinyint(1) NOT NULL DEFAULT '0',
  `referrer` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `key_users` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `key_tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `app_type` int DEFAULT NULL,
  `mini` tinyint NOT NULL DEFAULT '0',
  `donation_target` text,
  `challenge_main_category` varchar(100) DEFAULT NULL,
  `challenge_sub_category` varchar(100) DEFAULT NULL,
  `challenge_difficult` varchar(10) DEFAULT 'easy',
  `refresh_amount` int DEFAULT NULL,
  `grantable` int NOT NULL DEFAULT '0',
  `donatable` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `rakuaward_granted_at` timestamp NULL DEFAULT NULL,
  `rakuaward_refunded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_records_get_posts_feed_idx` (`app_type`,`deleted_at`,`updated_at`),
  KEY `post_records_get_posts_category_idx` (`app_type`,`challenge_main_category`,`challenge_sub_category`,`deleted_at`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_refresh_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_refresh_use_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_relay_prizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_relay_prizes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `root_post_id` int NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `source` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prize` int NOT NULL DEFAULT '0',
  `try_flag` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_relay_prizes_unique_participant` (`root_post_id`,`user_id`),
  KEY `post_relay_prizes_user_id_try_flag_index` (`user_id`,`try_flag`),
  KEY `post_relay_prizes_user_id_prize_created_at_index` (`user_id`,`prize`,`created_at`),
  CONSTRAINT `post_relay_prizes_root_post_id_foreign` FOREIGN KEY (`root_post_id`) REFERENCES `post_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_relay_prizes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_relays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_relays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `relay_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_post_id` int NOT NULL,
  `accepted_post_id` int DEFAULT NULL,
  `from_user_id` bigint unsigned NOT NULL,
  `to_user_id` bigint unsigned NOT NULL,
  `declined_by_user_id` bigint unsigned DEFAULT NULL,
  `closed_by_user_id` bigint unsigned DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `assigned_at` timestamp NULL DEFAULT NULL,
  `deadline_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_relays_unique_source_path` (`relay_type`,`source_post_id`,`from_user_id`,`to_user_id`),
  KEY `post_relays_from_user_id_foreign` (`from_user_id`),
  KEY `post_relays_to_user_id_foreign` (`to_user_id`),
  KEY `post_relays_declined_by_user_id_foreign` (`declined_by_user_id`),
  KEY `post_relays_closed_by_user_id_foreign` (`closed_by_user_id`),
  KEY `post_relays_recipient_index` (`relay_type`,`status`,`to_user_id`,`deadline_at`),
  KEY `post_relays_sender_index` (`relay_type`,`status`,`from_user_id`),
  KEY `post_relays_accepted_post_id_index` (`accepted_post_id`),
  KEY `post_relays_source_post_id_foreign` (`source_post_id`),
  CONSTRAINT `post_relays_accepted_post_id_foreign` FOREIGN KEY (`accepted_post_id`) REFERENCES `post_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `post_relays_closed_by_user_id_foreign` FOREIGN KEY (`closed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `post_relays_declined_by_user_id_foreign` FOREIGN KEY (`declined_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `post_relays_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_relays_source_post_id_foreign` FOREIGN KEY (`source_post_id`) REFERENCES `post_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_relays_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_to_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_to_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_use_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `result_flag` int NOT NULL DEFAULT '0',
  `progress` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_use_sport_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_use_sport_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `post_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_use_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `depth` tinyint unsigned NOT NULL DEFAULT '0',
  `is_postable` tinyint(1) NOT NULL DEFAULT '1',
  `is_formula` tinyint(1) NOT NULL DEFAULT '0',
  `formula` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_accounts_project_record_id_code_unique` (`project_record_id`,`code`),
  UNIQUE KEY `project_accounts_project_record_id_path_unique` (`project_record_id`,`path`),
  KEY `project_accounts_parent_id_foreign` (`parent_id`),
  KEY `project_accounts_project_record_id_parent_id_index` (`project_record_id`,`parent_id`),
  KEY `project_record_id` (`project_record_id`,`is_active`),
  CONSTRAINT `project_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `project_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_accounts_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_assign_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_assign_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_assign_record_id` bigint unsigned NOT NULL,
  `user_id` int DEFAULT NULL,
  `actual_user_id` int DEFAULT NULL,
  `content` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `additional_data` json DEFAULT NULL,
  `action_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_assign_actions_project_assign_record_id_foreign` (`project_assign_record_id`),
  CONSTRAINT `project_assign_actions_project_assign_record_id_foreign` FOREIGN KEY (`project_assign_record_id`) REFERENCES `project_assign_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_assign_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_assign_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_user_id` int DEFAULT NULL,
  `project_member_role_id` bigint unsigned DEFAULT NULL,
  `score` double DEFAULT NULL,
  `assign_data` json DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `project_record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `support_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_assign_records_project_record_id_index` (`project_record_id`),
  KEY `project_assign_records_user_id_index` (`user_id`),
  KEY `project_assign_records_support_level_index` (`support_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_assign_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_assign_status_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_assign_record_id` bigint unsigned NOT NULL,
  `project_record_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `from_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_assign_status_histories_project_assign_record_id_index` (`project_assign_record_id`),
  KEY `project_assign_status_histories_project_record_id_index` (`project_record_id`),
  KEY `project_assign_status_histories_user_id_index` (`user_id`),
  KEY `project_assign_status_histories_changed_at_index` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_cases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `project_goal_id` int DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `report_date` date NOT NULL COMMENT '対象月（1日で揃える）',
  `status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `case_count` int unsigned NOT NULL DEFAULT '0',
  `amount` bigint unsigned NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  `state` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'draft | submitted',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `timecard_record_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_cases_user_id_foreign` (`user_id`),
  KEY `project_cases_project_record_id_report_date_index` (`project_record_id`,`report_date`),
  KEY `project_cases_project_record_id_state_index` (`project_record_id`,`state`),
  KEY `project_cases_timecard_record_id_index` (`timecard_record_id`),
  KEY `project_cases_project_goal_id_index` (`project_goal_id`),
  CONSTRAINT `project_cases_project_goal_id_foreign` FOREIGN KEY (`project_goal_id`) REFERENCES `project_goals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_cases_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_cases_timecard_record_id_foreign` FOREIGN KEY (`timecard_record_id`) REFERENCES `timecard_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_cases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_checkitem_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_checkitem_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_checkitem_categories_key_unique` (`key`),
  UNIQUE KEY `project_checkitem_categories_label_unique` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_checkitem_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_checkitem_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_type_id` bigint unsigned NOT NULL,
  `project_checkitem_category_id` bigint unsigned DEFAULT NULL,
  `category_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_checkitem_templates_project_type_id_foreign` (`project_type_id`),
  KEY `fk_pct_category_id` (`project_checkitem_category_id`),
  KEY `project_checkitem_templates_parent_id_foreign` (`parent_id`),
  CONSTRAINT `fk_pct_category_id` FOREIGN KEY (`project_checkitem_category_id`) REFERENCES `project_checkitem_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_checkitem_templates_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `project_checkitem_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_checkitem_templates_project_type_id_foreign` FOREIGN KEY (`project_type_id`) REFERENCES `project_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_checkitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_checkitems` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `project_record_id` int NOT NULL,
  `project_checkitem_template_id` bigint unsigned DEFAULT NULL,
  `project_checkitem_category_id` bigint unsigned DEFAULT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_applicable` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `checked_by` bigint unsigned DEFAULT NULL,
  `linked_by` bigint unsigned DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_checkitems_project_record_id_index` (`project_record_id`),
  KEY `project_checkitems_project_record_id_status_index` (`project_record_id`,`status`),
  KEY `project_checkitems_parent_id_foreign` (`parent_id`),
  KEY `project_checkitems_project_checkitem_template_id_foreign` (`project_checkitem_template_id`),
  KEY `project_checkitems_project_checkitem_category_id_foreign` (`project_checkitem_category_id`),
  CONSTRAINT `project_checkitems_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `project_checkitems` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_checkitems_project_checkitem_category_id_foreign` FOREIGN KEY (`project_checkitem_category_id`) REFERENCES `project_checkitem_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_checkitems_project_checkitem_template_id_foreign` FOREIGN KEY (`project_checkitem_template_id`) REFERENCES `project_checkitem_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_checkitems_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_checkitems_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_checkitems_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `project_record_id` int NOT NULL,
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_checkitems_reports_user_id_foreign` (`user_id`),
  KEY `project_checkitems_reports_project_record_id_foreign` (`project_record_id`),
  CONSTRAINT `project_checkitems_reports_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_checkitems_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_conditions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `value` int DEFAULT NULL,
  `week_start_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_record_id` (`project_record_id`,`user_id`,`week_start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `review_type` enum('quick','deep') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `overall_risk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `findings_count` int unsigned NOT NULL DEFAULT '0',
  `result_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `response_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `role` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contract_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `project_contracts_project_record_id_foreign` (`project_record_id`),
  KEY `project_contracts_overall_risk_review_type_index` (`overall_risk`,`review_type`),
  KEY `project_contracts_overall_risk_index` (`overall_risk`),
  KEY `project_contracts_active_version_index` (`active`,`version`),
  KEY `project_contracts_role_index` (`role`),
  CONSTRAINT `project_contracts_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_contracts_chk_1` CHECK (json_valid(`result_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_customer_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_customer_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `sections` json DEFAULT NULL,
  `source_snapshot` json DEFAULT NULL,
  `public_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_customer_reports_public_token_unique` (`public_token`),
  KEY `project_customer_reports_created_by_foreign` (`created_by`),
  KEY `project_customer_reports_updated_by_foreign` (`updated_by`),
  KEY `project_customer_reports_project_record_id_status_index` (`project_record_id`,`status`),
  KEY `project_customer_reports_status_index` (`status`),
  CONSTRAINT `project_customer_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_customer_reports_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_customer_reports_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_evaluations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `mentor_id` int DEFAULT NULL,
  `general_position` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `new_position` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `current_level` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `employment_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date` date DEFAULT NULL,
  `grade` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `current_salary_rank` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `after_salary_rank` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `users` (`user_id`,`mentor_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `period` date NOT NULL,
  `salaries` int NOT NULL DEFAULT '0',
  `outsourcing` int NOT NULL DEFAULT '0',
  `internal_orders` int NOT NULL DEFAULT '0',
  `sga_other` int NOT NULL DEFAULT '0',
  `indirect` int NOT NULL DEFAULT '0',
  `bonus` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_expenses_project_record_id_index` (`project_record_id`),
  KEY `project_expenses_period_index` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_finance_comment_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_finance_comment_checks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `comment_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `project_finance_comment_checks_user_id_foreign` (`user_id`),
  KEY `project_finance_comment_checks_comment_id_foreign` (`comment_id`),
  CONSTRAINT `project_finance_comment_checks_comment_id_foreign` FOREIGN KEY (`comment_id`) REFERENCES `project_finance_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_finance_comment_checks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_finance_comment_mentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_finance_comment_mentions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint unsigned NOT NULL,
  `mentioned_user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pfc_mention_unique` (`comment_id`,`mentioned_user_id`),
  KEY `project_finance_comment_mentions_comment_id_index` (`comment_id`),
  KEY `project_finance_comment_mentions_mentioned_user_id_index` (`mentioned_user_id`),
  CONSTRAINT `pfc_mention_comment_fk` FOREIGN KEY (`comment_id`) REFERENCES `project_finance_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pfc_mention_user_fk` FOREIGN KEY (`mentioned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_finance_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_finance_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reply_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_finance_comments_project_record_id_index` (`project_record_id`),
  KEY `project_finance_comments_user_id_index` (`user_id`),
  KEY `project_finance_comments_reply_id_foreign` (`reply_id`),
  KEY `project_finance_comments_period_index` (`period`),
  CONSTRAINT `project_finance_comments_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_finance_comments_reply_id_foreign` FOREIGN KEY (`reply_id`) REFERENCES `project_finance_comments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_finance_last_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_finance_last_reads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `period` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pf_last_reads_unique` (`project_record_id`,`user_id`,`period`),
  KEY `project_finance_last_reads_project_record_id_index` (`project_record_id`),
  KEY `project_finance_last_reads_user_id_index` (`user_id`),
  KEY `project_finance_last_reads_period_index` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_goal_incident_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_goal_incident_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_goal_id` bigint unsigned NOT NULL,
  `incident_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsible_user_id` bigint unsigned DEFAULT NULL,
  `message_record_id` bigint unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_goal_incident_unique` (`project_goal_id`,`incident_type`),
  KEY `project_goal_incident_reports_project_goal_id_index` (`project_goal_id`),
  KEY `project_goal_incident_reports_responsible_user_id_index` (`responsible_user_id`),
  KEY `project_goal_incident_reports_sent_at_index` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_goal_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_goal_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `project_goal_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_goal_id` (`project_goal_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_goal_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_goal_steps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_goal_id` int DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` int NOT NULL DEFAULT '0',
  `progress` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_goals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `employment_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `target_period` date DEFAULT NULL,
  `year` year DEFAULT NULL,
  `which_half` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `outcome_goal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `action_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `expected_effect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `situation_analysis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `target_value` int DEFAULT NULL,
  `source_kind` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_status_label` varchar(191) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `achievement_rate` int DEFAULT '0',
  `report` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `criteria` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ai_review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ai_advice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `custom_instruction` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `private_memo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `kgi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `miso` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` tinyint NOT NULL DEFAULT '0',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `stakeholder_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stakeholder_point` smallint NOT NULL DEFAULT '0',
  `stakeholder_review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `frozen_progress` int DEFAULT NULL,
  `frozen_at` timestamp NULL DEFAULT NULL,
  `hr_confirmed_at` timestamp NULL DEFAULT NULL,
  `hr_confirmed_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ids` (`project_id`,`user_id`),
  KEY `date` (`target_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_increase_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_increase_candidates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `increase_id` int DEFAULT NULL,
  `evaluation_record_id` int DEFAULT NULL,
  `last_candidate` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `next_candidate` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `increase_id` (`increase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_increase_checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_increase_checklists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `increase_id` int DEFAULT NULL,
  `evaluation_record_id` int DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `increase_id` (`increase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_kintone_contract_update_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_kintone_contract_update_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_id` int unsigned DEFAULT NULL,
  `record_id` int unsigned DEFAULT NULL,
  `project_id` int unsigned DEFAULT NULL,
  `project_name` text COLLATE utf8mb4_unicode_ci,
  `target_user_id` int unsigned DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pkcun_app_id_idx` (`app_id`),
  KEY `pkcun_record_id_idx` (`record_id`),
  KEY `pkcun_project_id_idx` (`project_id`),
  KEY `pkcun_target_user_id_idx` (`target_user_id`),
  KEY `pkcun_notification_id_idx` (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_management_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_management_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'note',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `happened_at` datetime DEFAULT NULL,
  `next_action_on` date DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `calendar_record_id` bigint unsigned DEFAULT NULL,
  `calendar_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_management_actions_user_id_foreign` (`user_id`),
  KEY `pm_actions_record_happened_idx` (`record_id`,`happened_at`),
  KEY `pm_actions_type_next_idx` (`action_type`,`next_action_on`),
  KEY `pm_actions_calendar_record_idx` (`calendar_record_id`),
  CONSTRAINT `project_management_actions_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `project_management_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_management_actions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_management_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_management_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `list_id` bigint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `settings` json DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pm_fields_list_key_unique` (`list_id`,`key`),
  KEY `project_management_fields_created_by_foreign` (`created_by`),
  KEY `project_management_fields_updated_by_foreign` (`updated_by`),
  KEY `pm_fields_list_sort_idx` (`list_id`,`sort_order`),
  CONSTRAINT `project_management_fields_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_management_fields_list_id_foreign` FOREIGN KEY (`list_id`) REFERENCES `project_management_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_management_fields_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_management_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_management_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_management_lists_created_by_foreign` (`created_by`),
  KEY `project_management_lists_updated_by_foreign` (`updated_by`),
  KEY `pml_project_sort_idx` (`project_record_id`,`sort_order`),
  CONSTRAINT `project_management_lists_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_management_lists_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_management_lists_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_management_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_management_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `list_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_management_records_created_by_foreign` (`created_by`),
  KEY `project_management_records_updated_by_foreign` (`updated_by`),
  KEY `pm_records_list_sort_idx` (`list_id`,`sort_order`),
  CONSTRAINT `project_management_records_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_management_records_list_id_foreign` FOREIGN KEY (`list_id`) REFERENCES `project_management_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_management_records_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_management_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_management_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL,
  `field_id` bigint unsigned NOT NULL,
  `value_text` longtext COLLATE utf8mb4_unicode_ci,
  `value_number` decimal(20,6) DEFAULT NULL,
  `value_date` date DEFAULT NULL,
  `value_datetime` datetime DEFAULT NULL,
  `value_boolean` tinyint(1) DEFAULT NULL,
  `value_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pm_values_record_field_unique` (`record_id`,`field_id`),
  KEY `project_management_values_field_id_foreign` (`field_id`),
  CONSTRAINT `project_management_values_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `project_management_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_management_values_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `project_management_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_management_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_management_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `list_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `columns` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `sort` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_management_views_list_id_foreign` (`list_id`),
  KEY `project_management_views_created_by_foreign` (`created_by`),
  CONSTRAINT `project_management_views_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_management_views_list_id_foreign` FOREIGN KEY (`list_id`) REFERENCES `project_management_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_member_report_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_member_report_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `target_user_id` int DEFAULT NULL,
  `from_user_id` int DEFAULT NULL,
  `project_goal_id` int DEFAULT NULL,
  `salary_issue_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `which_half` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_member_report_notifications_user_id_index` (`user_id`),
  KEY `project_member_report_notifications_target_user_id_index` (`target_user_id`),
  KEY `project_member_report_notifications_from_user_id_index` (`from_user_id`),
  KEY `project_member_report_notifications_project_goal_id_index` (`project_goal_id`),
  KEY `project_member_report_notifications_salary_issue_id_index` (`salary_issue_id`),
  KEY `project_member_report_notifications_project_id_index` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_member_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_member_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `risk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risk_management` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `member_limit` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `work_conditions` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_member_roles_project_record_id_user_id_index` (`project_record_id`,`user_id`),
  KEY `project_member_roles_project_record_id_index` (`project_record_id`),
  KEY `project_member_roles_user_id_index` (`user_id`),
  CONSTRAINT `project_member_roles_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_member_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `role` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `authority` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `compatibility_number` decimal(5,2) DEFAULT NULL,
  `review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `project_member_role_id` bigint unsigned DEFAULT NULL,
  `assign_data` json DEFAULT NULL,
  `overall_assign_score` decimal(4,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ids` (`project_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_metric_display_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_metric_display_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_metric_id` bigint unsigned NOT NULL,
  `display_position` enum('main','sub') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_metric_id` bigint DEFAULT NULL,
  `color_scheme` enum('red','green','blue','yellow') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `project_metric_display_config_project_metric_id_index` (`project_metric_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_metric_formulas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_metric_formulas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_metric_id` bigint unsigned NOT NULL,
  `expression` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_metric_formulas_project_metric_id_foreign` (`project_metric_id`),
  CONSTRAINT `project_metric_formulas_project_metric_id_foreign` FOREIGN KEY (`project_metric_id`) REFERENCES `project_metrics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_metric_sub_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_metric_sub_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_metric_id` bigint unsigned NOT NULL,
  `expression` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_metric_sub_metrics_project_metric_id_foreign` (`project_metric_id`),
  CONSTRAINT `project_metric_sub_metrics_project_metric_id_foreign` FOREIGN KEY (`project_metric_id`) REFERENCES `project_metrics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_metric_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_metric_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `period` date NOT NULL,
  `project_metric_id` bigint unsigned NOT NULL,
  `value` decimal(18,2) DEFAULT NULL,
  `source` enum('manual','calc') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `calc_version` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_period_metric` (`project_record_id`,`period`,`project_metric_id`),
  KEY `project_metric_values_project_metric_id_foreign` (`project_metric_id`),
  KEY `project_metric_values_period_index` (`period`),
  KEY `project_metric_values_project_record_id_index` (`project_record_id`),
  CONSTRAINT `project_metric_values_project_metric_id_foreign` FOREIGN KEY (`project_metric_id`) REFERENCES `project_metrics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_metric_values_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `label_ja` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` enum('input','derived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_type` enum('amount','rate','currency') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'amount',
  `line` enum('sales','expense','profit','profit_rate') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `scenario_label_ja` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_metrics_label_ja_unique` (`label_ja`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_plan_amounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_plan_amounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `project_plan_year_id` bigint unsigned NOT NULL,
  `project_account_id` bigint unsigned NOT NULL,
  `project_plan_scenario_id` bigint unsigned DEFAULT NULL,
  `period_index` tinyint unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `scenario_key` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_plan_amount` (`project_record_id`,`project_plan_year_id`,`project_account_id`,`period_index`,`scenario_key`),
  KEY `project_plan_amounts_project_account_id_foreign` (`project_account_id`),
  KEY `project_plan_amounts_project_plan_scenario_id_foreign` (`project_plan_scenario_id`),
  KEY `idx_plan_year_period` (`project_plan_year_id`,`period_index`),
  KEY `project_record_id` (`project_record_id`,`project_plan_year_id`,`scenario_key`),
  CONSTRAINT `project_plan_amounts_project_account_id_foreign` FOREIGN KEY (`project_account_id`) REFERENCES `project_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_plan_amounts_project_plan_scenario_id_foreign` FOREIGN KEY (`project_plan_scenario_id`) REFERENCES `project_plan_scenarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_plan_amounts_project_plan_year_id_foreign` FOREIGN KEY (`project_plan_year_id`) REFERENCES `project_plan_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_plan_amounts_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_plan_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_plan_locks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `project_plan_year_id` bigint unsigned NOT NULL,
  `project_plan_scenario_id` bigint unsigned DEFAULT NULL,
  `scenario_key` bigint unsigned NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `locked_by_user_id` bigint unsigned DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_plan_lock` (`project_record_id`,`project_plan_year_id`,`scenario_key`),
  KEY `project_plan_locks_project_plan_year_id_foreign` (`project_plan_year_id`),
  KEY `project_plan_locks_project_plan_scenario_id_foreign` (`project_plan_scenario_id`),
  KEY `project_plan_locks_locked_by_user_id_foreign` (`locked_by_user_id`),
  KEY `idx_project_plan_lock_project_year` (`project_record_id`,`project_plan_year_id`),
  CONSTRAINT `project_plan_locks_locked_by_user_id_foreign` FOREIGN KEY (`locked_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_plan_locks_project_plan_scenario_id_foreign` FOREIGN KEY (`project_plan_scenario_id`) REFERENCES `project_plan_scenarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_plan_locks_project_plan_year_id_foreign` FOREIGN KEY (`project_plan_year_id`) REFERENCES `project_plan_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_plan_locks_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_plan_scenarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_plan_scenarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT '1.00',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_plan_scenarios_project_record_id_code_unique` (`project_record_id`,`code`),
  CONSTRAINT `project_plan_scenarios_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_plan_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_plan_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fiscal_year` smallint unsigned NOT NULL,
  `start_month` tinyint unsigned NOT NULL DEFAULT '3',
  `starts_on` date NOT NULL,
  `months` tinyint unsigned NOT NULL DEFAULT '12',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_plan_years_code_unique` (`code`),
  KEY `fiscal_year` (`fiscal_year`,`start_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_profit_plan_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_profit_plan_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_profit_plan_id` bigint unsigned NOT NULL,
  `scope` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_index` tinyint unsigned DEFAULT NULL,
  `category` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `details` json DEFAULT NULL,
  `revision_no` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_profit_plan_entry` (`project_profit_plan_id`,`scope`,`period_index`,`category`,`revision_no`),
  KEY `idx_profit_entry_scope_period` (`project_profit_plan_id`,`scope`,`period_index`),
  CONSTRAINT `project_profit_plan_entries_project_profit_plan_id_foreign` FOREIGN KEY (`project_profit_plan_id`) REFERENCES `project_profit_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_profit_plan_line_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_profit_plan_line_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_profit_plan_id` bigint unsigned NOT NULL,
  `revision_id` bigint unsigned NOT NULL,
  `period_index` tinyint unsigned NOT NULL,
  `revision_no` int unsigned NOT NULL,
  `line_type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_uid` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty_override` decimal(12,8) DEFAULT NULL,
  `worksite_id_override` bigint unsigned DEFAULT NULL,
  `rate_override` decimal(12,2) DEFAULT NULL,
  `rent_rate_override` decimal(12,2) DEFAULT NULL,
  `amount_override` decimal(14,2) DEFAULT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT '0',
  `addition_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_line_override` (`project_profit_plan_id`,`period_index`,`revision_no`,`line_type`,`line_uid`),
  KEY `fk_line_override_revision` (`revision_id`),
  KEY `fk_line_override_worksite` (`worksite_id_override`),
  KEY `idx_line_override_period` (`project_profit_plan_id`,`period_index`),
  CONSTRAINT `fk_line_override_plan` FOREIGN KEY (`project_profit_plan_id`) REFERENCES `project_profit_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_line_override_revision` FOREIGN KEY (`revision_id`) REFERENCES `project_profit_plan_revisions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_line_override_worksite` FOREIGN KEY (`worksite_id_override`) REFERENCES `worksites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_profit_plan_member_line_months`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_profit_plan_member_line_months` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_line_id` bigint unsigned NOT NULL,
  `period_index` tinyint unsigned NOT NULL,
  `qty` decimal(12,8) NOT NULL DEFAULT '0.00000000',
  `unit_price` decimal(14,2) DEFAULT NULL,
  `worksite_id` bigint unsigned DEFAULT NULL,
  `rate_snapshot` decimal(12,2) DEFAULT NULL,
  `work_days_snapshot` decimal(8,4) DEFAULT NULL,
  `unit_snapshot` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rent_rate_snapshot` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_member_line_month` (`member_line_id`,`period_index`),
  KEY `project_profit_plan_member_line_months_worksite_id_foreign` (`worksite_id`),
  CONSTRAINT `fk_member_line_month_line` FOREIGN KEY (`member_line_id`) REFERENCES `project_profit_plan_member_lines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_profit_plan_member_line_months_worksite_id_foreign` FOREIGN KEY (`worksite_id`) REFERENCES `worksites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_profit_plan_member_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_profit_plan_member_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_profit_plan_id` bigint unsigned NOT NULL,
  `line_uid` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_kind` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `user_id` int DEFAULT NULL,
  `employee_code` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_item_id` bigint unsigned DEFAULT NULL,
  `role_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `memo` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_member_line_uid` (`project_profit_plan_id`,`line_uid`),
  KEY `project_profit_plan_member_lines_cost_item_id_foreign` (`cost_item_id`),
  KEY `idx_member_line_sort` (`project_profit_plan_id`,`sort_order`),
  KEY `idx_member_line_employee_code` (`employee_code`),
  KEY `idx_member_line_kind` (`project_profit_plan_id`,`line_kind`),
  CONSTRAINT `project_profit_plan_member_lines_cost_item_id_foreign` FOREIGN KEY (`cost_item_id`) REFERENCES `cost_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_profit_plan_member_lines_project_profit_plan_id_foreign` FOREIGN KEY (`project_profit_plan_id`) REFERENCES `project_profit_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_profit_plan_project_line_months`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_profit_plan_project_line_months` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_line_id` bigint unsigned NOT NULL,
  `period_index` tinyint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `quantity` decimal(14,4) DEFAULT NULL,
  `unit_price` decimal(14,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_line_month` (`project_line_id`,`period_index`),
  CONSTRAINT `fk_project_line_month_line` FOREIGN KEY (`project_line_id`) REFERENCES `project_profit_plan_project_lines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_profit_plan_project_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_profit_plan_project_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_profit_plan_id` bigint unsigned NOT NULL,
  `line_uid` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `counterparty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_item_id` bigint unsigned DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `memo` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_line_uid` (`project_profit_plan_id`,`line_uid`),
  KEY `project_profit_plan_project_lines_cost_item_id_foreign` (`cost_item_id`),
  KEY `idx_project_line_category` (`project_profit_plan_id`,`category`),
  CONSTRAINT `project_profit_plan_project_lines_cost_item_id_foreign` FOREIGN KEY (`cost_item_id`) REFERENCES `cost_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_profit_plan_project_lines_project_profit_plan_id_foreign` FOREIGN KEY (`project_profit_plan_id`) REFERENCES `project_profit_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_profit_plan_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_profit_plan_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_profit_plan_id` bigint unsigned NOT NULL,
  `period_index` tinyint unsigned NOT NULL,
  `revision_no` int unsigned NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saved_by_user_id` int DEFAULT NULL,
  `saved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_profit_plan_revision` (`project_profit_plan_id`,`period_index`,`revision_no`),
  KEY `idx_profit_revision_period` (`project_profit_plan_id`,`period_index`),
  CONSTRAINT `project_profit_plan_revisions_project_profit_plan_id_foreign` FOREIGN KEY (`project_profit_plan_id`) REFERENCES `project_profit_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_profit_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_profit_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `fiscal_year` smallint unsigned NOT NULL,
  `scenario_key` bigint unsigned NOT NULL DEFAULT '0',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `model_version` tinyint unsigned NOT NULL DEFAULT '1',
  `submitted_by_user_id` int DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `confirmed_by_user_id` int DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `rates_frozen_at` timestamp NULL DEFAULT NULL,
  `unlocked_by_user_id` int DEFAULT NULL,
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `pm_memo` text COLLATE utf8mb4_unicode_ci,
  `director_memo` text COLLATE utf8mb4_unicode_ci,
  `planning_blocks` json DEFAULT NULL,
  `calculation_settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_profit_plan` (`project_record_id`,`fiscal_year`,`scenario_key`),
  KEY `idx_profit_plan_project_status` (`project_record_id`,`status`),
  CONSTRAINT `project_profit_plans_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_record_read_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_record_read_states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `project_record_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prrs_project_record_id_user_id_type_unique` (`project_record_id`,`user_id`,`type`),
  KEY `project_record_read_states_user_id_foreign` (`user_id`),
  CONSTRAINT `project_record_read_states_project_record_id_foreign` FOREIGN KEY (`project_record_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_record_read_states_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `project_type_id` bigint unsigned DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `private_memo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `customers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `partners` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `risk_id` int DEFAULT NULL,
  `director_id` int DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `overview` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `strategy` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `kgi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `kpi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `miso` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mission` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `innovation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `strategy_miso` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `operation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `budget` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `stakeholder` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `board_id` int DEFAULT NULL,
  `category` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `industry_type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_new` tinyint(1) NOT NULL DEFAULT '0',
  `is_renewable` tinyint(1) NOT NULL DEFAULT '0',
  `transitioned_at` date DEFAULT NULL,
  `completed_at` date DEFAULT NULL,
  `contract_started_at` date DEFAULT NULL,
  `has_forecast` tinyint(1) NOT NULL DEFAULT '1',
  `has_goals` tinyint(1) NOT NULL DEFAULT '0',
  `has_actual_func` tinyint(1) NOT NULL DEFAULT '0',
  `unit_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'JPY',
  `custom_unit_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `actual_statuses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `board_id` (`board_id`),
  KEY `project_records_status_index` (`status`),
  KEY `completed_at` (`completed_at`),
  KEY `project_records_project_type_id_foreign` (`project_type_id`),
  CONSTRAINT `project_records_project_type_id_foreign` FOREIGN KEY (`project_type_id`) REFERENCES `project_types` (`id`),
  CONSTRAINT `project_records_chk_1` CHECK (json_valid(`customers`)),
  CONSTRAINT `project_records_chk_2` CHECK (json_valid(`partners`)),
  CONSTRAINT `project_records_chk_3` CHECK (json_valid(`category`)),
  CONSTRAINT `project_records_chk_4` CHECK (json_valid(`industry_type`)),
  CONSTRAINT `project_records_chk_5` CHECK (json_valid(`actual_statuses`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_resource_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_resource_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `period` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_resource_comments_member_name_index` (`member_name`),
  KEY `project_resource_comments_user_id_index` (`user_id`),
  KEY `project_resource_comments_period_index` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `period` date NOT NULL,
  `sales` int unsigned NOT NULL DEFAULT '0',
  `internal_sales` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_sales_project_record_id_index` (`project_record_id`),
  KEY `project_sales_period_index` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_set_increases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_set_increases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `last_set` int DEFAULT NULL,
  `last_achieved` int DEFAULT NULL,
  `change_in_position` tinyint NOT NULL DEFAULT '0',
  `position_approved` tinyint NOT NULL DEFAULT '0',
  `target_period` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mentor_entry` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dates` (`target_period`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_spec_reference_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_spec_reference_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_spec_id` bigint unsigned NOT NULL,
  `file_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_spec_reference_files_project_spec_id_foreign` (`project_spec_id`),
  KEY `project_spec_reference_files_file_id_foreign` (`file_id`),
  CONSTRAINT `project_spec_reference_files_file_id_foreign` FOREIGN KEY (`file_id`) REFERENCES `file_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_spec_reference_files_project_spec_id_foreign` FOREIGN KEY (`project_spec_id`) REFERENCES `project_specs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_specs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_specs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `spec_data` json DEFAULT NULL,
  `plan_data` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_specs_project_id_unique` (`project_id`),
  KEY `project_specs_created_by_foreign` (`created_by`),
  KEY `project_specs_updated_by_foreign` (`updated_by`),
  CONSTRAINT `project_specs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_specs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `project_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_specs_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_types_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_use_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `salary_issue_id` int DEFAULT NULL,
  `project_goal_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_v_plan_months`;
/*!50001 DROP VIEW IF EXISTS `project_v_plan_months`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `project_v_plan_months` AS SELECT 
 1 AS `plan_month_id`,
 1 AS `plan_year_id`,
 1 AS `period_index`,
 1 AS `calendar_year`,
 1 AS `calendar_month`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `public_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `public_holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `holiday_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_holidays_date_unique` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_aggregates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bucket` int unsigned NOT NULL,
  `period` mediumint unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `aggregate` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(20,2) NOT NULL,
  `count` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_aggregates_bucket_period_type_aggregate_key_hash_unique` (`bucket`,`period`,`type`,`aggregate`,`key_hash`),
  KEY `pulse_aggregates_period_bucket_index` (`period`,`bucket`),
  KEY `pulse_aggregates_type_index` (`type`),
  KEY `pulse_aggregates_period_type_aggregate_bucket_index` (`period`,`type`,`aggregate`,`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pulse_entries_timestamp_index` (`timestamp`),
  KEY `pulse_entries_type_index` (`type`),
  KEY `pulse_entries_key_hash_index` (`key_hash`),
  KEY `pulse_entries_timestamp_type_key_hash_value_index` (`timestamp`,`type`,`key_hash`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_values_type_key_hash_unique` (`type`,`key_hash`),
  KEY `pulse_values_timestamp_index` (`timestamp`),
  KEY `pulse_values_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `endpoint` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `vapid_public_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invalid_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_user_id_foreign` (`user_id`),
  KEY `push_subscriptions_vapid_public_hash_index` (`vapid_public_hash`),
  KEY `push_subscriptions_origin_index` (`origin`),
  KEY `push_subscriptions_invalid_at_index` (`invalid_at`),
  CONSTRAINT `push_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qanda_history_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qanda_history_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hits` int NOT NULL DEFAULT '1',
  `user_id` int DEFAULT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qanda_key_word_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qanda_key_word_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `useful_count` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qanda_tag_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qanda_tag_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `useful_count` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qanda_use_key_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qanda_use_key_words` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `useful_count` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qanda_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qanda_use_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `useful_count` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `question_and_answer_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_and_answer_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT '0',
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tag_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `useful_count` int DEFAULT '0',
  `ai_sync_status` varchar(255) NOT NULL DEFAULT 'not_synced',
  `ai_sync_error` text,
  `ai_synced_at` timestamp NULL DEFAULT NULL,
  `ai_sync_hash` varchar(64) DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qar_deleted_created_idx` (`deleted_flag`,`created_at`),
  KEY `qar_ai_status_idx` (`ai_sync_status`),
  KEY `qar_ai_hash_idx` (`ai_sync_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `question_and_answer_vector_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_and_answer_vector_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_and_answer_record_id` bigint unsigned NOT NULL,
  `markdown_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `markdown_copy_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openai_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vector_store_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qavd_record_idx` (`question_and_answer_record_id`),
  KEY `qavd_openai_idx` (`openai_file_id`),
  KEY `qavd_vs_idx` (`vector_store_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refresh_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refresh_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `joined_date` date DEFAULT NULL,
  `opening_total_granted` int NOT NULL DEFAULT '0',
  `opening_total_used` int NOT NULL DEFAULT '0',
  `opening_remaining_amount` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refresh_accounts_user_id_unique` (`user_id`),
  KEY `refresh_accounts_is_active_index` (`is_active`),
  CONSTRAINT `refresh_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refresh_annual_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refresh_annual_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `refresh_account_id` bigint unsigned NOT NULL,
  `grant_year` smallint unsigned NOT NULL,
  `grant_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grant_date` date DEFAULT NULL,
  `base_amount` int NOT NULL DEFAULT '0',
  `adjusted_amount` int NOT NULL DEFAULT '0',
  `attendance_status` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leave_status` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leave_review_confirmed_at` timestamp NULL DEFAULT NULL,
  `leave_review_confirmed_by_user_id` bigint unsigned DEFAULT NULL,
  `service_years` smallint unsigned DEFAULT NULL,
  `decision_note` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refresh_annual_reviews_account_year_unique` (`refresh_account_id`,`grant_year`),
  KEY `refresh_annual_reviews_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `refresh_annual_reviews_grant_year_status_index` (`grant_year`,`status`),
  KEY `refresh_annual_reviews_leave_review_confirmed_by_user_id_foreign` (`leave_review_confirmed_by_user_id`),
  CONSTRAINT `refresh_annual_reviews_leave_review_confirmed_by_user_id_foreign` FOREIGN KEY (`leave_review_confirmed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refresh_annual_reviews_refresh_account_id_foreign` FOREIGN KEY (`refresh_account_id`) REFERENCES `refresh_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refresh_annual_reviews_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refresh_expirations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refresh_expirations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `refresh_account_id` bigint unsigned NOT NULL,
  `refresh_grant_id` bigint unsigned DEFAULT NULL,
  `expired_at` date NOT NULL,
  `amount` int NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `source_system` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'glowd',
  `source_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refresh_expirations_grant_expired_at_unique` (`refresh_grant_id`,`expired_at`),
  KEY `refresh_expirations_refresh_account_id_expired_at_index` (`refresh_account_id`,`expired_at`),
  KEY `refresh_expirations_account_source_key_index` (`refresh_account_id`,`source_system`,`source_key`),
  CONSTRAINT `refresh_expirations_refresh_account_id_foreign` FOREIGN KEY (`refresh_account_id`) REFERENCES `refresh_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refresh_expirations_refresh_grant_id_foreign` FOREIGN KEY (`refresh_grant_id`) REFERENCES `refresh_grants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refresh_grants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refresh_grants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `refresh_account_id` bigint unsigned NOT NULL,
  `grant_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grant_year` smallint unsigned DEFAULT NULL,
  `granted_at` date NOT NULL,
  `expires_at` date DEFAULT NULL,
  `amount` int NOT NULL,
  `remaining_amount` int DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `source_system` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'glowd',
  `source_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refresh_grants_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `refresh_grants_refresh_account_id_granted_at_index` (`refresh_account_id`,`granted_at`),
  KEY `refresh_grants_refresh_account_id_grant_year_index` (`refresh_account_id`,`grant_year`),
  KEY `refresh_grants_refresh_account_id_expires_at_index` (`refresh_account_id`,`expires_at`),
  KEY `refresh_grants_account_source_key_index` (`refresh_account_id`,`source_system`,`source_key`),
  CONSTRAINT `refresh_grants_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refresh_grants_refresh_account_id_foreign` FOREIGN KEY (`refresh_account_id`) REFERENCES `refresh_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refresh_usage_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refresh_usage_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `refresh_usage_id` bigint unsigned NOT NULL,
  `refresh_grant_id` bigint unsigned NOT NULL,
  `amount` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refresh_usage_allocations_refresh_grant_id_foreign` (`refresh_grant_id`),
  KEY `refresh_usage_allocations_usage_grant_index` (`refresh_usage_id`,`refresh_grant_id`),
  CONSTRAINT `refresh_usage_allocations_refresh_grant_id_foreign` FOREIGN KEY (`refresh_grant_id`) REFERENCES `refresh_grants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refresh_usage_allocations_refresh_usage_id_foreign` FOREIGN KEY (`refresh_usage_id`) REFERENCES `refresh_usages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refresh_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refresh_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `refresh_account_id` bigint unsigned NOT NULL,
  `post_record_id` int DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `amount` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `note` text COLLATE utf8mb4_unicode_ci,
  `source_system` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'glowd',
  `source_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_by_user_id` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refresh_usages_post_record_id_unique` (`post_record_id`),
  KEY `refresh_usages_approved_by_user_id_foreign` (`approved_by_user_id`),
  KEY `refresh_usages_refresh_account_id_used_at_index` (`refresh_account_id`,`used_at`),
  KEY `refresh_usages_account_source_key_index` (`refresh_account_id`,`source_system`,`source_key`),
  CONSTRAINT `refresh_usages_approved_by_user_id_foreign` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refresh_usages_refresh_account_id_foreign` FOREIGN KEY (`refresh_account_id`) REFERENCES `refresh_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regulation_file_vector_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regulation_file_vector_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `regulation_file_id` bigint unsigned NOT NULL,
  `page_number` int unsigned NOT NULL,
  `markdown_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `markdown_copy_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openai_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vector_store_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rfvp_file_page_idx` (`regulation_file_id`,`page_number`),
  KEY `rfvp_openai_idx` (`openai_file_id`),
  KEY `rfvp_vs_idx` (`vector_store_file_id`),
  CONSTRAINT `regulation_file_vector_pages_regulation_file_id_foreign` FOREIGN KEY (`regulation_file_id`) REFERENCES `regulation_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regulation_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regulation_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `regulation_record_id` bigint unsigned DEFAULT NULL,
  `vector_file_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extension` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` bigint DEFAULT NULL,
  `chat_supported` tinyint(1) NOT NULL DEFAULT '0',
  `ai_sync_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_synced',
  `ai_sync_error` text COLLATE utf8mb4_unicode_ci,
  `ai_synced_at` timestamp NULL DEFAULT NULL,
  `ai_sync_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `regulation_files_regulation_record_id_index` (`regulation_record_id`),
  CONSTRAINT `regulation_files_regulation_record_id_foreign` FOREIGN KEY (`regulation_record_id`) REFERENCES `regulation_records` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regulation_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regulation_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_issue_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_issue_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `learning_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `learning_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `salary_issue_id` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_issue_id` (`salary_issue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_issue_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_issue_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `salary_issue_id` bigint unsigned NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_issues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `mentor_id` int DEFAULT NULL,
  `project_goal_id` int DEFAULT NULL,
  `lesson_theme_id` bigint unsigned DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `theme` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ability` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date` date DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ids` (`user_id`,`project_goal_id`),
  KEY `date` (`date`),
  KEY `salary_issues_lesson_theme_id_index` (`lesson_theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedule_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_use_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedule_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_use_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `search_history_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_history_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hits` int NOT NULL DEFAULT '1',
  `user_id` int DEFAULT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shift_overtime_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_overtime_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `overtime_day` date DEFAULT NULL,
  `minutes` int DEFAULT NULL,
  `project_segments` json DEFAULT NULL,
  `status` int DEFAULT '0',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `descendant_of` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shift_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status_flag` int NOT NULL DEFAULT '0',
  `shift_day` date DEFAULT NULL,
  `descendant_of` int DEFAULT NULL,
  `shift_type` int DEFAULT NULL,
  `planned_year` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `departure_report` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `date` (`shift_day`),
  KEY `types` (`shift_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shift_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` text,
  `abbreviation` text,
  `value` int DEFAULT NULL,
  `full_day` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stamps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stamps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stampable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stampable_id` bigint unsigned NOT NULL,
  `emote_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stamps_stampable_user_unique` (`stampable_type`,`stampable_id`,`user_id`),
  KEY `stamps_stampable_type_stampable_id_index` (`stampable_type`,`stampable_id`),
  KEY `stamps_user_id_foreign` (`user_id`),
  CONSTRAINT `stamps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `before_number` int DEFAULT NULL,
  `after_number` int DEFAULT NULL,
  `before_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `after_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_logs_type_record_id_index` (`type`,`record_id`),
  KEY `status_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_conversation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_conversation_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `support_conversation_id` bigint unsigned NOT NULL,
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `source` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `role` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_conversation_items_support_conversation_id_foreign` (`support_conversation_id`),
  CONSTRAINT `support_conversation_items_support_conversation_id_foreign` FOREIGN KEY (`support_conversation_id`) REFERENCES `support_conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `conversation_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_conversations_user_id_foreign` (`user_id`),
  CONSTRAINT `support_conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_mail_form_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_mail_form_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `date_end` text,
  `time_end` text,
  `kind_value` int DEFAULT NULL,
  `contact_address` text,
  `consultation_content` text,
  `sendKind_flag` int DEFAULT '0',
  `status_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_mail_responding_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_mail_responding_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `text` text,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `survey_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_answers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `guest_uuid` varchar(36) DEFAULT NULL,
  `community_record_user_id` bigint DEFAULT NULL,
  `custom_form_id` bigint DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  `target_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `community_record_user_id` (`community_record_user_id`),
  KEY `custom_form_id` (`custom_form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `survey_block_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_block_answers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `community_record_user_id` bigint DEFAULT NULL,
  `survey_answer_id` bigint DEFAULT NULL,
  `text_answer` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `custom_form_block_id` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_answer_id` (`survey_answer_id`),
  KEY `custom_form_block_id` (`custom_form_block_id`),
  KEY `user_id` (`user_id`),
  KEY `community_record_user_id` (`community_record_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `survey_block_element_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_block_element_answers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `community_record_user_id` bigint DEFAULT NULL,
  `survey_block_answer_id` bigint DEFAULT NULL,
  `custom_form_block_element_id` bigint DEFAULT NULL,
  `sub_text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_block_answer_id` (`survey_block_answer_id`),
  KEY `custom_form_block_element_id` (`custom_form_block_element_id`),
  KEY `user_id` (`user_id`),
  KEY `community_record_user_id` (`community_record_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `survey_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_use_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `survey_block_answer_id` int DEFAULT NULL,
  `file_record_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_update_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_update_checks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `system_update_record_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_update_checks_user_id_system_update_record_id_unique` (`user_id`,`system_update_record_id`),
  KEY `system_update_checks_system_update_record_id_index` (`system_update_record_id`),
  CONSTRAINT `system_update_checks_system_update_record_id_foreign` FOREIGN KEY (`system_update_record_id`) REFERENCES `system_update_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `system_update_checks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_update_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_update_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `system_update_record_id` bigint unsigned NOT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_update_details_system_update_record_id_foreign` (`system_update_record_id`),
  KEY `system_update_details_type_index` (`type`),
  KEY `system_update_details_sort_order_index` (`sort_order`),
  CONSTRAINT `system_update_details_system_update_record_id_foreign` FOREIGN KEY (`system_update_record_id`) REFERENCES `system_update_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_update_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_update_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `category` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `published_at` timestamp NULL DEFAULT NULL,
  `scheduled_start_at` timestamp NULL DEFAULT NULL,
  `scheduled_end_at` timestamp NULL DEFAULT NULL,
  `must_read` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_update_records_visible_idx` (`category`,`is_published`,`published_at`),
  KEY `system_update_records_user_id_index` (`user_id`),
  KEY `system_update_records_category_index` (`category`),
  KEY `system_update_records_status_index` (`status`),
  KEY `system_update_records_is_published_index` (`is_published`),
  KEY `system_update_records_published_at_index` (`published_at`),
  KEY `system_update_records_scheduled_start_at_index` (`scheduled_start_at`),
  KEY `system_update_records_must_read_index` (`must_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tag_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hits` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `type` int NOT NULL DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tag_records_deleted_flag` (`deleted_flag`),
  KEY `idx_tag_records_deleted_text` (`deleted_flag`,`text`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taggables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint unsigned NOT NULL,
  `taggable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taggable_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taggables_unique` (`tag_id`,`taggable_type`,`taggable_id`),
  KEY `taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  CONSTRAINT `taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_category_title_unique` (`category`,`title`),
  KEY `tags_slug_index` (`slug`),
  KEY `tags_category_index` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_comments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `task_record_id` bigint DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_record_id` (`task_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `updated_user` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `board_id` int DEFAULT NULL,
  `parent_task_id` bigint DEFAULT NULL,
  `project_goal_step_id` int DEFAULT NULL,
  `project_record_id` bigint DEFAULT NULL,
  `message_id` int DEFAULT NULL,
  `approver_id` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `start_at` date DEFAULT NULL,
  `end_at` date DEFAULT NULL,
  `response_time` int NOT NULL DEFAULT '0',
  `sync_to_schedule` tinyint NOT NULL DEFAULT '0',
  `glowd_nine` int DEFAULT '0',
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comp_flag` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `board_id` (`board_id`),
  KEY `message_id` (`message_id`),
  KEY `end_at` (`end_at`),
  KEY `comp_flag` (`comp_flag`),
  KEY `parent_task_id` (`parent_task_id`),
  KEY `project_record_id` (`project_record_id`),
  KEY `start_at` (`start_at`),
  KEY `board_id_created_at` (`board_id`,`created_at`),
  KEY `task_records_project_goal_step_id_index` (`project_goal_step_id`),
  CONSTRAINT `task_records_project_goal_step_id_foreign` FOREIGN KEY (`project_goal_step_id`) REFERENCES `project_goal_steps` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_use_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_use_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `supervisor` tinyint NOT NULL DEFAULT '0',
  `comp_flag` int DEFAULT '0',
  `glowd_nine` int DEFAULT '0',
  `prize` int DEFAULT '0',
  `try_flag` int DEFAULT '0',
  `status_flag` tinyint DEFAULT '0',
  `progress_flag` int NOT NULL DEFAULT '0',
  `pin_flag` int DEFAULT '0',
  `late_answer` int DEFAULT '0',
  `late_answer_custom` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comment` text,
  `deleted_flag` int DEFAULT '0',
  `checked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`),
  KEY `progress_flag` (`progress_flag`),
  KEY `user_id_progress_task` (`user_id`,`progress_flag`,`record_id`),
  KEY `task_user_idx` (`record_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_audit_event_projections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_audit_event_projections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timecard_audit_event_id` bigint unsigned NOT NULL,
  `timecard_record_id` bigint unsigned DEFAULT NULL,
  `timecard_cost_record_id` int DEFAULT NULL,
  `draft_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `subject_user_id` bigint unsigned DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `timecard_day` date DEFAULT NULL,
  `approval_state` int DEFAULT NULL,
  `merchant_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `expenses` decimal(12,2) DEFAULT NULL,
  `currency` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_file_id` bigint unsigned DEFAULT NULL,
  `file_sha256` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `internal_control_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocr_run_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `timecard_audit_event_projections_timecard_audit_event_id_unique` (`timecard_audit_event_id`),
  KEY `idx_subject_user_occurred` (`subject_user_id`,`occurred_at`),
  KEY `idx_event_type_occurred` (`event_type`,`occurred_at`),
  KEY `idx_merchant_name_occurred` (`merchant_name`,`occurred_at`),
  KEY `idx_approval_state_occurred` (`approval_state`,`occurred_at`),
  KEY `idx_timecard_day_occurred` (`timecard_day`,`occurred_at`),
  KEY `timecard_audit_event_projections_timecard_record_id_index` (`timecard_record_id`),
  KEY `timecard_audit_event_projections_timecard_cost_record_id_index` (`timecard_cost_record_id`),
  KEY `timecard_audit_event_projections_draft_uuid_index` (`draft_uuid`),
  KEY `timecard_audit_event_projections_event_type_index` (`event_type`),
  KEY `timecard_audit_event_projections_actor_user_id_index` (`actor_user_id`),
  KEY `timecard_audit_event_projections_subject_user_id_index` (`subject_user_id`),
  KEY `timecard_audit_event_projections_occurred_at_index` (`occurred_at`),
  KEY `timecard_audit_event_projections_timecard_day_index` (`timecard_day`),
  KEY `timecard_audit_event_projections_approval_state_index` (`approval_state`),
  KEY `timecard_audit_event_projections_merchant_name_index` (`merchant_name`),
  KEY `timecard_audit_event_projections_receipt_date_index` (`receipt_date`),
  KEY `timecard_audit_event_projections_expenses_index` (`expenses`),
  KEY `timecard_audit_event_projections_ocr_run_id_index` (`ocr_run_id`),
  KEY `idx_receipt_date_occurred` (`receipt_date`,`occurred_at`),
  KEY `idx_subject_receipt_occurred` (`subject_user_id`,`receipt_date`,`occurred_at`),
  KEY `timecard_audit_event_projections_receipt_file_id_index` (`receipt_file_id`),
  KEY `timecard_audit_event_projections_internal_control_status_index` (`internal_control_status`),
  CONSTRAINT `timecard_audit_event_projections_receipt_file_id_foreign` FOREIGN KEY (`receipt_file_id`) REFERENCES `timecard_receipt_files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_audit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_audit_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timecard_record_id` bigint unsigned DEFAULT NULL,
  `timecard_cost_record_id` int DEFAULT NULL,
  `draft_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `subject_user_id` bigint unsigned NOT NULL,
  `request_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `before_state` json DEFAULT NULL,
  `after_state` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `payload_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_event_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timecard_audit_events_actor_user_id_foreign` (`actor_user_id`),
  KEY `timecard_audit_events_timecard_record_id_occurred_at_index` (`timecard_record_id`,`occurred_at`),
  KEY `timecard_audit_events_timecard_cost_record_id_occurred_at_index` (`timecard_cost_record_id`,`occurred_at`),
  KEY `timecard_audit_events_subject_user_id_occurred_at_index` (`subject_user_id`,`occurred_at`),
  KEY `timecard_audit_events_event_type_occurred_at_index` (`event_type`,`occurred_at`),
  KEY `timecard_audit_events_draft_uuid_occurred_at_index` (`draft_uuid`,`occurred_at`),
  KEY `timecard_audit_events_event_hash_index` (`event_hash`),
  CONSTRAINT `timecard_audit_events_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_audit_events_subject_user_id_foreign` FOREIGN KEY (`subject_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timecard_audit_events_timecard_cost_record_id_foreign` FOREIGN KEY (`timecard_cost_record_id`) REFERENCES `timecard_cost_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_audit_events_timecard_record_id_foreign` FOREIGN KEY (`timecard_record_id`) REFERENCES `timecard_records` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_break_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_break_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `day` date DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `break_by_minute` int DEFAULT NULL,
  `break_flag` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_cost_ocr_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_cost_ocr_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timecard_record_id` bigint unsigned DEFAULT NULL,
  `timecard_cost_record_id` int DEFAULT NULL,
  `draft_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_file_sha256` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gemini',
  `model` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_result` json DEFAULT NULL,
  `raw_response` json DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `executed_by_user_id` bigint unsigned DEFAULT NULL,
  `applied_by_user_id` bigint unsigned DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timecard_cost_ocr_runs_timecard_record_id_foreign` (`timecard_record_id`),
  KEY `timecard_cost_ocr_runs_applied_by_user_id_foreign` (`applied_by_user_id`),
  KEY `timecard_cost_ocr_runs_timecard_cost_record_id_created_at_index` (`timecard_cost_record_id`,`created_at`),
  KEY `timecard_cost_ocr_runs_draft_uuid_created_at_index` (`draft_uuid`,`created_at`),
  KEY `timecard_cost_ocr_runs_source_file_sha256_index` (`source_file_sha256`),
  KEY `timecard_cost_ocr_runs_executed_by_user_id_created_at_index` (`executed_by_user_id`,`created_at`),
  CONSTRAINT `timecard_cost_ocr_runs_applied_by_user_id_foreign` FOREIGN KEY (`applied_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_cost_ocr_runs_executed_by_user_id_foreign` FOREIGN KEY (`executed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_cost_ocr_runs_timecard_cost_record_id_foreign` FOREIGN KEY (`timecard_cost_record_id`) REFERENCES `timecard_cost_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_cost_ocr_runs_timecard_record_id_foreign` FOREIGN KEY (`timecard_record_id`) REFERENCES `timecard_records` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_cost_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_cost_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `draft_uuid` char(36) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `date_month` varchar(7) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `department` text,
  `project_id` bigint unsigned DEFAULT NULL,
  `timecard_project_segment_id` bigint unsigned DEFAULT NULL,
  `merchant_name` varchar(255) DEFAULT NULL,
  `currency` varchar(8) NOT NULL DEFAULT 'JPY',
  `receipt_source_type` varchar(32) NOT NULL DEFAULT 'paper_scan',
  `type` int DEFAULT NULL,
  `transport_type` tinyint unsigned DEFAULT NULL,
  `departure_place` varchar(255) DEFAULT NULL,
  `arrival_place` varchar(255) DEFAULT NULL,
  `content` text,
  `expenses` int DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `receipt_file_id` bigint unsigned DEFAULT NULL,
  `file_original_name` varchar(255) DEFAULT NULL,
  `file_mime_type` varchar(255) DEFAULT NULL,
  `file_size_bytes` bigint unsigned DEFAULT NULL,
  `file_sha256` varchar(64) DEFAULT NULL,
  `file_uploaded_at` timestamp NULL DEFAULT NULL,
  `scan_dpi` int DEFAULT NULL,
  `scan_color_depth` int DEFAULT NULL,
  `scan_color_mode` varchar(32) DEFAULT NULL,
  `document_size` varchar(32) DEFAULT NULL,
  `image_width_px` int DEFAULT NULL,
  `image_height_px` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `date_month` (`date_month`),
  KEY `user_id` (`user_id`),
  KEY `timecard_cost_records_draft_uuid_index` (`draft_uuid`),
  KEY `timecard_cost_records_merchant_name_receipt_date_index` (`merchant_name`,`receipt_date`),
  KEY `timecard_cost_records_receipt_file_id_index` (`receipt_file_id`),
  KEY `timecard_cost_records_project_id_index` (`project_id`),
  KEY `timecard_cost_records_timecard_project_segment_id_index` (`timecard_project_segment_id`),
  CONSTRAINT `timecard_cost_records_receipt_file_id_foreign` FOREIGN KEY (`receipt_file_id`) REFERENCES `timecard_receipt_files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_incentives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_incentives` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `count` int DEFAULT NULL,
  `date_month` varchar(7) DEFAULT NULL,
  `file_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_missing_occurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_missing_occurrences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_record_id` bigint unsigned DEFAULT NULL,
  `report_date` date NOT NULL,
  `counted_date` date NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `pm_alerted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `timecard_missing_occurrences_user_id_report_date_unique` (`user_id`,`report_date`),
  KEY `timecard_missing_occurrences_shift_record_id_foreign` (`shift_record_id`),
  KEY `timecard_missing_occurrences_user_id_counted_date_index` (`user_id`,`counted_date`),
  KEY `timecard_missing_occurrences_pm_alerted_at_index` (`pm_alerted_at`),
  CONSTRAINT `timecard_missing_occurrences_shift_record_id_foreign` FOREIGN KEY (`shift_record_id`) REFERENCES `shift_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_missing_occurrences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_project_segment_details`;
/*!50001 DROP VIEW IF EXISTS `timecard_project_segment_details`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `timecard_project_segment_details` AS SELECT 
 1 AS `segment_id`,
 1 AS `timecard_record_id`,
 1 AS `day`,
 1 AS `user_id`,
 1 AS `user_name`,
 1 AS `project_id`,
 1 AS `project_name`,
 1 AS `segment_type`,
 1 AS `start_time`,
 1 AS `end_time`,
 1 AS `minutes`,
 1 AS `segment_status`,
 1 AS `daily_status`,
 1 AS `approval_source`,
 1 AS `approved_by`,
 1 AS `approved_by_name`,
 1 AS `created_at`,
 1 AS `updated_at`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `timecard_project_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_project_segments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timecard_record_id` bigint unsigned NOT NULL,
  `project_id` int NOT NULL,
  `segment_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'work',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `minutes` int NOT NULL DEFAULT '0',
  `details` json DEFAULT NULL,
  `detail_values` json DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timecard_project_segments_timecard_record_id_foreign` (`timecard_record_id`),
  KEY `timecard_project_segments_project_id_foreign` (`project_id`),
  KEY `timecard_project_segments_approved_by_foreign` (`approved_by`),
  KEY `timecard_project_segments_approval_source_index` (`approval_source`),
  CONSTRAINT `timecard_project_segments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_project_segments_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `project_records` (`id`),
  CONSTRAINT `timecard_project_segments_timecard_record_id_foreign` FOREIGN KEY (`timecard_record_id`) REFERENCES `timecard_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_receipt_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_receipt_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timecard_record_id` bigint unsigned DEFAULT NULL,
  `timecard_cost_record_id` int DEFAULT NULL,
  `draft_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `uploaded_by_user_id` bigint unsigned DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extension` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` bigint unsigned NOT NULL,
  `sha256` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `canonical_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preview_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paper_scan',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `uploaded_at` timestamp NOT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `scan_dpi` int DEFAULT NULL,
  `scan_color_depth` int DEFAULT NULL,
  `scan_color_mode` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_size` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_width_px` int DEFAULT NULL,
  `image_height_px` int DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by_user_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timecard_receipt_files_timecard_record_id_foreign` (`timecard_record_id`),
  KEY `timecard_receipt_files_uploaded_by_user_id_foreign` (`uploaded_by_user_id`),
  KEY `timecard_receipt_files_deleted_by_user_id_foreign` (`deleted_by_user_id`),
  KEY `timecard_receipt_files_timecard_cost_record_id_foreign` (`timecard_cost_record_id`),
  KEY `timecard_receipt_files_user_id_uploaded_at_index` (`user_id`,`uploaded_at`),
  KEY `timecard_receipt_files_status_uploaded_at_index` (`status`,`uploaded_at`),
  KEY `timecard_receipt_files_draft_uuid_index` (`draft_uuid`),
  KEY `timecard_receipt_files_sha256_index` (`sha256`),
  KEY `timecard_receipt_files_canonical_path_index` (`canonical_path`),
  KEY `timecard_receipt_files_source_type_index` (`source_type`),
  KEY `timecard_receipt_files_status_index` (`status`),
  KEY `timecard_receipt_files_uploaded_at_index` (`uploaded_at`),
  KEY `timecard_receipt_files_finalized_at_index` (`finalized_at`),
  KEY `timecard_receipt_files_is_deleted_index` (`is_deleted`),
  CONSTRAINT `timecard_receipt_files_deleted_by_user_id_foreign` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_receipt_files_timecard_cost_record_id_foreign` FOREIGN KEY (`timecard_cost_record_id`) REFERENCES `timecard_cost_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_receipt_files_timecard_record_id_foreign` FOREIGN KEY (`timecard_record_id`) REFERENCES `timecard_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_receipt_files_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timecard_receipt_files_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL COMMENT 'Timecard approved by user ID',
  `day` date DEFAULT NULL,
  `work_group_id` int DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `training_start_time` time DEFAULT NULL,
  `training_end_time` time DEFAULT NULL,
  `work_time_edit_flag` int DEFAULT '0',
  `edit_start_time` time DEFAULT NULL,
  `edit_end_time` time DEFAULT NULL,
  `late_time` text,
  `over_time` text,
  `work_time` int NOT NULL DEFAULT '0',
  `night_over_time` int DEFAULT '0',
  `break_time` int DEFAULT NULL,
  `stamp_flag` int DEFAULT NULL,
  `status_flag` int DEFAULT '0',
  `car_mileage` int NOT NULL DEFAULT '0',
  `car_used_project` int unsigned DEFAULT NULL,
  `gas_full_price` int NOT NULL DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `day` (`day`),
  KEY `user_id` (`user_id`),
  KEY `day_2` (`day`,`user_id`,`work_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timecard_vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timecard_vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `timecard_project_segment_id` bigint unsigned DEFAULT NULL,
  `vehicle` int DEFAULT NULL,
  `alcohol_before_time` time DEFAULT NULL,
  `alcohol_after_time` time DEFAULT NULL,
  `alcohol_before_value` float DEFAULT NULL,
  `alcohol_after_value` float NOT NULL,
  `confirm_before_user` int DEFAULT NULL,
  `confirm_after_user` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`,`user_id`,`confirm_before_user`,`confirm_after_user`),
  KEY `timecard_vehicles_project_id_index` (`project_id`),
  KEY `timecard_vehicles_timecard_project_segment_id_index` (`timecard_project_segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `todos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `todos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `todo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `update_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `update_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loggable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loggable_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` json DEFAULT NULL,
  `new_value` json DEFAULT NULL,
  `changes` json DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `incident_logs_incident_id_created_at_index` (`created_at`),
  KEY `incident_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `incident_logs_action_created_at_index` (`action`,`created_at`),
  KEY `incident_logs_user_id_index` (`user_id`),
  KEY `incident_logs_action_index` (`action`),
  KEY `incident_logs_field_index` (`field`),
  KEY `update_logs_loggable_type_loggable_id_index` (`loggable_type`,`loggable_id`),
  KEY `update_logs_loggable_type_loggable_id_created_at_index` (`loggable_type`,`loggable_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_albums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_albums` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `intro_flag` int NOT NULL DEFAULT '0',
  `mime_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `extension` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `leave_start` date DEFAULT NULL,
  `leave_end` date DEFAULT NULL,
  `memo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_last_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_last_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `last_knowledge` int DEFAULT NULL,
  `last_nice` int DEFAULT NULL,
  `last_challenge` int DEFAULT NULL,
  `last_post` int DEFAULT NULL,
  `deleted_flag` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_leave_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_leave_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `leave_start` date DEFAULT NULL,
  `leave_end` date DEFAULT NULL,
  `active` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_linked_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_linked_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `main_id` int DEFAULT NULL,
  `link_id` int DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_project_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_project_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `project_id` int NOT NULL,
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_read_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_read_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `readable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_read_histories_unique` (`readable_type`,`readable_id`,`user_id`),
  KEY `user_read_histories_readable_last_read_index` (`readable_type`,`readable_id`,`last_read_at`),
  KEY `user_read_histories_readable_type_index` (`readable_type`),
  KEY `user_read_histories_readable_id_index` (`readable_id`),
  KEY `user_read_histories_user_id_index` (`user_id`),
  KEY `user_read_histories_last_read_at_index` (`last_read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_use_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_use_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_kana` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `work_email` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `award_charge` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0',
  `employment_status` int DEFAULT '0',
  `icon_id` int DEFAULT NULL,
  `icon_bg` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_type` int DEFAULT '0',
  `position_id` int DEFAULT NULL,
  `old_position_id` int DEFAULT NULL,
  `office_id` int DEFAULT NULL,
  `phone_number` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `motto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `enjoy` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `awareness` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `intro` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `intro_flag` int DEFAULT '0',
  `work_authority` int DEFAULT '0',
  `work_type` int DEFAULT '0',
  `work_time_type` int DEFAULT '0',
  `work_time` int DEFAULT NULL,
  `work_time_day` int DEFAULT NULL,
  `retire` int DEFAULT '0',
  `retire_date` date DEFAULT NULL,
  `user_code` int DEFAULT NULL,
  `pay_day` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `push_board` int DEFAULT '1',
  `deleted_flag` int NOT NULL DEFAULT '0',
  `hide_flag` int DEFAULT '0',
  `partner_flag` int NOT NULL DEFAULT '0',
  `file_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ical_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` int DEFAULT '0',
  `recommend` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sign_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `footer_view` tinyint(1) DEFAULT '0',
  `linkable` tinyint(1) DEFAULT '0',
  `on_leave` tinyint NOT NULL DEFAULT '0',
  `general_position` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `joined_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `google_token` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `id` (`id`),
  KEY `icon_id` (`icon_id`),
  KEY `name` (`name`),
  KEY `idx_users_retire` (`retire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `variance_alert_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variance_alert_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_record_id` int NOT NULL,
  `period` date NOT NULL,
  `hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `variance_alert_logs_project_record_id_period_unique` (`project_record_id`,`period`),
  KEY `variance_alert_logs_hash_index` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `welcome_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `welcome_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `chunks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  CONSTRAINT `welcome_messages_chk_1` CHECK (json_valid(`chunks`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_group_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_group_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `authority` int NOT NULL DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`),
  KEY `authority` (`authority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_month_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_month_holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `month` int DEFAULT '0',
  `days` int DEFAULT '0',
  `deleted_flag` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_temps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_temps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `user_code` int DEFAULT NULL,
  `user_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `granted_days` int DEFAULT NULL,
  `planned_days` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_code` (`user_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `worksite_rents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worksite_rents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worksite_id` bigint unsigned NOT NULL,
  `segment` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `per_person_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(14,2) DEFAULT NULL,
  `headcount` decimal(8,2) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `source_record_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_updated_at` timestamp NULL DEFAULT NULL,
  `source_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_worksite_rent` (`worksite_id`,`segment`,`effective_from`),
  KEY `idx_worksite_rent_segment` (`worksite_id`,`segment`),
  CONSTRAINT `worksite_rents_worksite_id_foreign` FOREIGN KEY (`worksite_id`) REFERENCES `worksites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `worksites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worksites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_system` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kintone',
  `source_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `office_no` int unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `office_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `users_note` text COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `source_record_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_updated_at` timestamp NULL DEFAULT NULL,
  `source_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_worksites_source` (`source_system`,`source_key`),
  KEY `idx_worksites_name` (`name`),
  KEY `idx_worksites_office_no` (`office_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zoom_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zoom_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slot` tinyint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `host_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host_password` text COLLATE utf8mb4_unicode_ci,
  `account_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_secret` text COLLATE utf8mb4_unicode_ci,
  `webhook_secret` text COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zoom_accounts_slot_unique` (`slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP VIEW IF EXISTS `project_v_plan_months`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `project_v_plan_months` AS with recursive `seq` (`n`) as (select 1 AS `1` union all select (`seq`.`n` + 1) AS `n + 1` from `seq` where (`seq`.`n` < 12)) select ((`py`.`id` * 100) + `seq`.`n`) AS `plan_month_id`,`py`.`id` AS `plan_year_id`,`seq`.`n` AS `period_index`,year((`py`.`starts_on` + interval (`seq`.`n` - 1) month)) AS `calendar_year`,month((`py`.`starts_on` + interval (`seq`.`n` - 1) month)) AS `calendar_month` from (`project_plan_years` `py` join `seq`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `timecard_project_segment_details`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `timecard_project_segment_details` AS select `s`.`id` AS `segment_id`,`s`.`timecard_record_id` AS `timecard_record_id`,`t`.`day` AS `day`,`t`.`user_id` AS `user_id`,`u`.`name` AS `user_name`,`s`.`project_id` AS `project_id`,`p`.`name` AS `project_name`,`s`.`segment_type` AS `segment_type`,`s`.`start_time` AS `start_time`,`s`.`end_time` AS `end_time`,`s`.`minutes` AS `minutes`,`s`.`status` AS `segment_status`,`t`.`status_flag` AS `daily_status`,`s`.`approval_source` AS `approval_source`,`s`.`approved_by` AS `approved_by`,`approver`.`name` AS `approved_by_name`,`s`.`created_at` AS `created_at`,`s`.`updated_at` AS `updated_at` from ((((`timecard_project_segments` `s` join `timecard_records` `t` on((`t`.`id` = `s`.`timecard_record_id`))) join `users` `u` on((`u`.`id` = `t`.`user_id`))) left join `project_records` `p` on((`p`.`id` = `s`.`project_id`))) left join `users` `approver` on((`approver`.`id` = `s`.`approved_by`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2020_03_14_020621_create_todos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2020_05_28_014015_create_knowledge_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2020_05_29_004056_knowledges',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2020_05_29_005933_knowledges',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2020_05_29_014405_knowledge__records',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2020_05_29_015313_create_tests_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2020_06_08_015313_create_tests_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2020_06_09_015313_create_tests_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2020_06_10_015313_create_tests_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2020_06_16_015313_create_tests_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2020_06_16_015315_create_tests_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2020_07_03_015315_create_tests_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2020_07_15_015315_create_tests_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2020_07_31_015315_create_tests_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2020_09_03_015315_create_tests_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2020_09_23_015315_create_tests_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2020_11_29_015315_create_tests_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2020_11_29_015318_create_tests_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2020_11_29_015319_create_tests_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2019_12_14_000001_create_personal_access_tokens_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2023_09_14_101751_add_soft_deletes_on_all_tables_10',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2023_10_17_131243_create_salary_issue',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2024_01_22_140538_create_jobs_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_09_04_135502_create_regulation_records_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_09_08_112327_create_regulation_files_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_09_08_115113_modify_regulation_files_table_add_chat_supported_and_nullable_regulation_record_id',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_09_08_115335_make_vector_file_id_nullable_in_regulation_files_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_09_08_151707_add_project_to_carmileage',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_09_08_171146_add_refund_service_post_awards',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_09_09_083722_add_grantable_to_post_records',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_09_09_083938_post_grants',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_09_09_103148_add_departure_report_to_shift_records_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_09_11_105708_add_gas_full_price_to_timecard_records',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_09_02_152906_drive_node',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_09_04_102750_drive_node_acls',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_09_04_104240_add_ext_to_drive_nodes_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_09_04_114419_drive_download_logs',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_09_04_150913_add_visibility_to_drive_nodes_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_09_22_112147_add_project_id_to_drive_nodes',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_09_09_110706_project_finance_comments',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_09_09_144754_project_finance_comment_mentions',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_09_09_144811_project_finance_last_reads',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_09_10_093911_add_date_to_project_finance_comments',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_09_10_131326_add_timestamps_to_project_finance_last_read',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_09_24_094319_variance_alert_logs',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_09_27_020000_create_drive_activity_logs_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_09_20_142434_add_google_token_to_users_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_09_22_142826_create_oauth_credentials_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_09_23_093044_add_account_name_and_avatar_url_to_oauth_credentials_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_09_23_105312_add_calendar_ids_to_oauth_credentials_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_10_10_100001_create_support_conversations_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_10_10_100101_create_support_conversation_items_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_09_17_151420_project_expenses',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_09_17_151423_project_sales',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_09_26_154158_project_metrics',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_09_26_154605_project_metric_formulas',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_09_26_154741_project_metric_values',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_09_26_171630_add_scenario_to_project_metrics',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_09_26_181234_add_value_type_to_project_metrics',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_09_27_000000_create_project_metric_sub_metrics',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_10_14_000000_create_contact_batches_tables',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_10_14_010000_create_contact_batch_logs_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_10_14_020000_add_tracking_columns_to_contact_batches_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_10_15_000001_add_duplicate_fields_to_contact_records_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_10_15_000002_create_contact_record_user_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_10_15_000003_add_duplicate_fields_to_contact_batch_items_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_10_15_081444_add_url_to_contact_records',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_10_15_134630_add_contact_type_to_contact_batch',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_10_20_113849_add_private_memo_to_contact_record_user',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_10_20_161523_create_salary_issue_reports_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_10_21_113046_contact_record_comments',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_10_22_120000_create_project_cases_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_10_24_092343_add_project_goal_report_id_to_message_files_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_10_24_135109_create_project_member_report_notifications_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_10_28_152140_add_contact_record_id_to_message_files',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_10_29_113414_contact_comment_last_read',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_10_29_173220_create_user_project_settings_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_09_18_112242_project_metric_display_config',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_10_22_130000_update_project_cases_structure',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_11_05_152232_project_contracts',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_11_05_180327_add_timestamps_and_file_path_to_project_contracts',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_11_06_105745_add_columns_to_project_contracts',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_11_07_152929_add_training_time_to_attendance_records',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_11_10_144123_project_finance_comment_checks',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_11_11_110652_add_is_new_to_project_records',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_11_14_145028_add_reply_id_to_project_finance_comments',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_11_18_143657_create_lesson_exams_tables',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_11_18_143658_add_correct_explanation_to_lesson_exam_questions_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_09_09_160000_add_period_to_project_finance_comments',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_11_21_151452_add_donatable_to_post_records',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_11_24_094856_add_period_to_finance_last_read',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_11_24_100055_add_period_unique_to_finance_last_reads',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_11_24_100807_change_period_project_finance_last_reads',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_12_04_084554_custom_field_last_reads',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_11_20_120000_add_performance_config_to_project_records',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_12_09_135509_add_has_actual_function_to_project_records',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_12_10_105205_add_timecard_record_id_to_project_cases',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_12_18_000000_create_message_emote_users_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_12_19_000001_create_custom_field_emote_users_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_12_25_095740_add_confirmed_by_to_attendance_records',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_12_25_100949_add_approved_by_to_timecard_records',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_12_26_153156_add_status_to_custom_forms',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2023_06_07_000002_create_pulse_tables',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_12_01_120100_create_project_plan_tables',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_12_05_000000_add_formula_to_project_accounts',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_12_06_000000_create_project_plan_locks_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_01_07_000000_add_stakeholder_columns_to_project_goals_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_12_01_100000_project_resource_comments',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_01_20_114124_add_transitioned_at_to_project_records',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_01_19_125851_add_compatibility_number_and_review_to_project_members_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_01_22_000001_create_project_member_roles_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_01_22_162638_add_project_member_role_id_to_project_members_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_01_28_171216_add_work_conditions_to_project_member_roles_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_01_29_160941_add_assign_data_to_project_members_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_01_29_160941_add_assign_data_to_project_members_and_score_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_01_29_160942_add_assign_data_to_project_members_and_score_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_01_19_165813_create_push_subscriptions_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_01_27_000000_add_depends_on_to_custom_form_blocks',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_02_04_153106_add_temp_flag_and_monthly_goal_slot_to_evaluation_records_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_02_04_093500_add_necessary_columns_to_push_subscriptions',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_02_10_162848_create_status_logs_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_02_10_163633_add_user_id_to_status_logs_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_02_10_162850_create_status_logs_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_02_10_163634_add_user_id_to_status_logs_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_02_05_150259_add_alert_streak_to_evaluation_records',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_02_06_092720_add_processing_month_to_evaluation_records',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_02_09_150654_add_project_record_id_to_message_records',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_02_10_094324_add_status_to_project_records',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_02_10_120000_create_project_checkitems_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_02_10_170917_add_project_checkitem_report_id_to_message_files',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_02_11_165544_project_checkitems_reports',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_02_13_120000_add_external_user_to_asset_records_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_02_13_120100_add_external_user_columns_to_asset_requests_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_02_13_120200_create_asset_confirm_logs_table',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_02_13_120300_create_asset_confirm_log_use_files_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_02_17_133259_add_memo_column_to_asset_requests_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_02_13_143722_add_soft_deletes_to_necessary_tables',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_02_16_111610_add_check_request_deadline_to_message_records',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_02_19_173121_project_record_read_state',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_02_20_132931_add_contract_started_at_to_project_records',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_02_24_141625_project_specs',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_02_25_133652_project_spec_reference_files',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_02_27_095150_lesson_access',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_02_27_135550_lesson_access',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_03_02_104424_file_attachments',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_03_03_133907_add_emote_name_to_custom_field_emote_users',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_03_03_134646_change_emote_id_custom_field_emote_users',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_03_04_140621_add_plan_data_to_project_specs',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_03_05_085218_add_completed_at_to_project_records',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_03_06_104042_add_linked_by_to_project_checkitems',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_03_06_104242_add_linked_by_to_project_checkitems',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_03_09_112716_add_type_to_project_checkitems_reports',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_03_09_132426_add_type_to_project_record_read_states',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_03_09_132728_drop_unique_in_project_record_read_states',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_03_12_113049_add_parent_id_to_project_checkitems',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_03_17_120000_add_usage_to_custom_forms',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_03_17_130000_add_categories_to_custom_form_blocks',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_03_24_102500_add_is_renewable_to_project_records',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_03_25_100000_create_project_types_and_assignments',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_03_25_100100_create_project_checkitem_categories',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_03_25_100200_create_project_checkitem_templates_and_block_pivot',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_03_25_100300_add_template_relations_to_project_checkitems',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_03_26_000000_add_public_access_to_custom_forms_and_guest_survey_answers',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_03_26_120000_create_refresh_management_tables',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_03_27_090000_create_refresh_usage_allocations_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_03_31_095820_add_joined_date_to_users',88);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_03_31_120000_add_grant_fields_to_refresh_annual_reviews_table',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_04_02_084246_change_spec_data_in_project_specs',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_04_01_120001_create_stamps_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_04_08_120000_add_challenge_categories_to_post_records',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_04_08_130000_add_progress_fields_to_comment_records',93);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_04_09_140000_create_timecard_audit_events_table',94);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_04_09_140100_create_timecard_cost_ocr_runs_table',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_04_09_140200_add_receipt_tracking_to_timecard_cost_records_table',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_04_09_160000_create_timecard_audit_event_projections_table',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_03_27_000001_create_project_assign_status_histories_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_03_30_000000_create_project_assign_records_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_04_01_000004_add_project_assign_record_id_to_custom_form_blocks',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_04_01_000005_create_project_assign_actions_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_04_10_000000_add_confirmed_at_to_project_assign_records_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_04_13_120001_create_asset_categories_tables',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_04_13_130001_create_asset_category_item_fields_and_asset_record_field_values',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_04_14_090000_add_transport_fields_to_timecard_cost_records_table',98);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_04_15_104330_add_challenge_difficult_to_post_records',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_04_14_150443_add_project_member_role_id',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_04_16_000001_add_receipt_date_indexes_to_timecard_audit_event_projections_table',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_04_16_112556_add_visible_and_editable_to_asset_category_item_fields',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_04_17_000001_create_contact_batch_notifications_table',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2026_04_17_000002_add_dismissed_at_to_contact_batches_table',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2026_04_21_133042_add_mini_to_post_records',104);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2026_04_23_120000_add_get_posts_indexes_to_post_records',105);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2026_04_23_000001_update_project_cases_for_extra_fields',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2026_04_24_153056_widen_timecard_file_path_columns',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2026_04_24_153156_widen_timecard_file_path_columns',108);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2026_04_27_090000_add_leave_review_confirmation_to_refresh_annual_reviews_table',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2026_05_04_000001_create_project_profit_plan_tables',110);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2026_05_04_000002_add_planning_blocks_to_project_profit_plans',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2026_05_05_000001_drop_resource_columns_from_project_profit_plans',112);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2026_05_04_000003_add_calculation_settings_to_project_profit_plans',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2026_05_08_000001_create_cost_master_tables',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2026_05_08_000002_add_source_fields_to_cost_items',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2026_04_29_143237_add_ai_sync_tracking_to_regulation_files',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2026_04_29_153000_add_ai_sync_tracking_to_question_and_answer_records',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2026_05_08_105957_create_incident_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2026_05_08_110856_create_incident_reports',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2026_05_08_114728_create_incident_categories',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2026_05_08_115321_create_incident_punishments',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2026_05_12_090001_create_system_update_records_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2026_05_12_090100_create_system_update_details_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2026_05_12_091001_create_system_update_checks_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2026_05_14_103658_timecard_project_segments',115);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2026_05_15_000001_add_approval_fields_to_timecard_project_segments',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2026_05_15_120000_create_project_customer_reports_table',117);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2026_05_19_000001_add_details_to_timecard_project_segments',118);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2026_05_19_000002_add_segment_type_to_timecard_project_segments',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2026_05_20_000001_add_comment_to_timecard_project_segments',120);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2026_05_20_000002_create_project_goal_incident_reports_table',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2026_05_21_000001_add_detail_values_to_timecard_project_segments',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2026_05_21_000002_add_project_segments_to_shift_overtime_requests',122);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2026_05_25_181931_add_progress_to_post_use_files',123);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2026_05_26_000001_create_challenge_relays_table',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2026_05_26_000002_add_history_to_challenge_relays_table',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2026_05_26_000003_create_nice_follow_up_dismissals_table',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2026_05_26_000001_create_post_relays_table',126);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2026_05_15_105058_create_emergency_contact_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2026_05_18_000000_create_public_holidays_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2026_05_19_000000_add_status_to_emergency_contacts_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2026_05_19_010000_create_emergency_contact_actions_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2026_05_21_000000_create_incident_logs',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2026_05_25_000000_convert_incident_logs_to_update_logs',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2026_05_25_010000_create_app_comments_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2026_05_26_000000_create_incident_statuses_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2026_05_27_000000_add_followup_fields_to_incidents_table',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2026_05_29_000000_create_user_read_histories_table',128);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2026_06_03_000000_add_has_file_attachment_to_custom_form_block_elements',129);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2026_06_03_000001_create_partner_scout_tables',130);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2026_06_03_100001_create_partner_scout_tables',131);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2026_06_03_110001_create_partner_scout_tables',132);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2026_06_03_120001_create_partner_scout_tables',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2026_06_04_145336_remove_old_columns_from_project_cases_table',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2026_06_05_000001_add_kintone_source_metadata_to_cost_master_tables',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2026_06_05_000002_add_project_fields_to_timecard_vehicles',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2026_06_08_000001_create_paid_leave_policy_tables',136);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2026_06_08_000002_create_paid_leave_ledger_tables',137);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2026_06_05_000000_extend_incident_reports_and_create_incident_assignees',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2026_06_08_000000_add_reported_date_to_incidents_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2026_06_09_000001_add_project_fields_to_timecard_cost_records',139);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2026_06_10_000001_move_comment_progress_files_to_file_attachments',140);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2026_01_11_000001_create_agent_conversations_table',141);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2026_06_09_000000_create_incident_advice_table',141);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2026_06_12_000001_add_crm_fields_to_contact_records',142);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2026_06_12_000002_create_polymorphic_tags_tables',143);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2026_06_15_000001_create_project_management_tables',144);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2026_06_16_084432_timecard_report_missing_occurrences',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2026_06_16_120000_add_approval_source_to_timecard_project_segments',146);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2026_06_17_000000_create_actual_result_tables',147);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2026_06_18_000001_add_pm_alerted_at_to_timecard_missing_occurrences_table',148);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2026_06_12_000001_create_employee_change_applications_table',149);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2026_06_12_000002_create_employee_profile_change_details_table',149);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2026_06_12_000003_create_employee_leave_application_details_table',149);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2026_06_12_000004_create_employee_commute_change_details_table',149);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2026_06_17_1120446_add_new_table_planned_leave_change_requests',149);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2026_06_19_000001_add_dependent_my_number_to_employee_profile_change_details_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2026_06_19_000002_add_fuel_type_to_employee_commute_change_details_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2026_06_19_000003_add_one_way_fare_to_employee_commute_change_details_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2026_06_19_000004_add_rainy_commute_method_to_employee_commute_change_details_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2026_06_29_000001_add_calendar_record_to_project_management_actions',151);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2026_07_02_000001_add_model_version_to_project_profit_plans',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2026_07_02_000002_create_worksites_tables',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2026_07_02_000003_create_profit_plan_v2_line_tables',153);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2026_07_02_000004_add_unique_source_key_to_cost_items',154);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2026_07_02_000005_add_work_days_snapshot_to_profit_plan_member_months',155);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2026_07_02_000006_expand_profit_plan_member_qty_precision',155);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2026_07_03_000001_add_department_to_contact_records',156);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2026_07_03_000002_create_contact_tags_tables',157);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2026_07_03_000003_create_contact_record_type_table',158);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2026_07_03_000004_drop_contact_tags_tables',158);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2026_07_03_000005_add_type_ids_to_contact_batches',158);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2026_07_03_000006_create_contact_relationship_tables',159);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2026_07_03_000007_add_enrichment_status_to_contact_records',160);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2026_07_07_000001_create_post_relay_prizes_table',161);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2026_07_07_110511_add_rakuaward_to_post_records',162);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2026_07_07_130000_add_rakuaward_granted_at_to_post_records',163);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2026_07_07_140000_add_rakuaward_refunded_at_to_post_records',164);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2026_06_23_145156_create_project_kintone_contract_update_notifications_table',165);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2026_06_23_145921_add_type_to_project_kintone_contract_update_notifications_table',165);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2026_06_30_000001_create_flow_definitions_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2026_06_30_000002_create_flow_fields_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2026_06_30_000003_create_flow_statuses_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2026_06_30_000004_create_flow_status_field_rules_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2026_06_30_000005_create_flow_shares_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2026_06_30_000006_create_flow_records_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2026_06_30_000007_create_flow_record_values_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2026_06_30_000008_create_flow_record_assignees_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2026_06_30_000009_add_validation_to_flow_fields_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2026_06_30_000010_swap_flow_field_span_for_width',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2026_06_30_000011_add_scope_to_flow_definitions',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2026_06_30_000012_add_typed_columns_to_flow_record_values',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2026_06_30_000013_add_formula_to_flow_fields',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2026_06_30_000014_create_flow_views_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'2026_06_30_000016_create_flow_app_permission_tables',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2026_06_30_000017_create_flow_record_permission_tables',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2026_06_30_000018_create_flow_field_permissions_table',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2026_06_30_000019_add_updated_by_to_flow_records',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2026_07_01_000001_add_record_numbering_and_view_defaults_to_flow',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2026_07_01_000002_add_status_flow_actions',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2026_07_01_000003_add_source_to_flow_records',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2026_07_06_000001_add_color_id_to_flow_definitions',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2026_07_06_000002_add_icon_to_flow_definitions',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2026_07_06_000003_create_flow_portal_tables',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (321,'2026_07_02_000007_add_structured_line_fields_to_profit_plan',167);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (322,'2026_07_09_000001_add_unit_snapshot_to_profit_plan_member_months',168);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2026_07_10_000001_add_can_bulk_to_flow_app_permissions',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (324,'2026_07_10_000001_create_zoom_accounts_table',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (325,'2026_07_10_000002_add_ui_position_to_flow_statuses',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2026_07_10_000003_create_calendar_facilities_table',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2026_07_10_000003_create_flow_app_tools_table',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2026_07_11_000001_sync_calendar_facility_production_data',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (329,'2026_07_13_120000_create_gasoline_rates_table',170);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (331,'2026_07_14_000001_create_project_kgis_table',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2026_07_14_000002_create_project_kpis_table',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2026_07_14_000003_add_source_project_kpi_id_to_project_goals',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2026_07_14_000004_add_project_goal_step_id_to_task_records',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2026_07_14_000005_add_project_goal_id_to_project_cases',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2026_07_07_150000_add_source_to_post_relay_prizes',172);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2026_07_14_120000_create_incident_candidates_table',173);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2026_07_14_000001_add_color_to_flow_statuses',174);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2026_07_15_000001_expand_actual_result_department_real_margin',175);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (340,'2026_06_19_000001_create_lesson_theme_categories',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2026_06_19_000002_add_archive_to_lesson_themes',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2026_06_19_000003_add_previous_version_to_lesson_themes',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2026_06_20_000001_create_lesson_theme_ai_configs',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2026_06_20_000002_expand_lesson_theme_ai_configs_to_slots',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2026_06_22_000001_create_lesson_personal_materials_table',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2026_06_22_000002_add_feedback_to_lesson_personal_materials_table',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2026_07_07_000001_add_salary_issue_target_to_lesson_themes',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2026_07_07_000002_link_salary_issue_to_learning',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2026_07_08_000001_add_discussion_topic_to_lesson_portfolios',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2026_07_08_000002_add_content_versioning_to_learning',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2026_07_08_000003_add_axis_to_lesson_themes',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2026_07_08_000004_create_lesson_portfolio_deletion_logs',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2026_07_14_000001_create_lesson_material_versions',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2026_07_15_000001_add_contact_fields_to_message_files',177);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2026_07_15_000002_create_contact_record_histories_table',177);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (356,'2026_07_15_000003_create_contact_private_memos_table',178);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (357,'2026_07_15_000004_create_contract_review_jobs_table',179);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (358,'2026_07_07_160000_drop_rakuaward_from_post_records',180);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (359,'2026_07_15_000001_create_flow_audit_logs_table',180);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (360,'2026_07_14_000002_add_material_scope_to_lesson_exams',181);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (361,'2026_07_23_000001_add_source_columns_to_project_goals',182);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (362,'2026_07_23_100001_add_freeze_columns_to_project_goals',183);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (363,'2026_07_23_200001_drop_orphaned_project_kgi_kpi_tables',184);
