CREATE DATABASE IF NOT EXISTS `events` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `events`;

CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_base64` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) /*!50100 TABLESPACE `innodb_system` */ ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`name`, `value`) VALUES
	('reservation_page_background_image', 'mybackground.jpg'),
	('reservation_max', '25'),
	('primary_textfield_name_placeholder_hint', 'name hint'),
	('primary_textfield_email_placeholder_hint', 'mail hint'),
	('reservation_additional_info', 'text'),
	('reservation_details', 'text'),
	('reservation_name', 'text'),
	('reservation_top_image', 'mytop.jpg'),
	('reservation_enabled', '1'),
	('reservation_undo_enabled', '1'),
	('reservation_page_background_color', 'transparent'),
	('reservation_page_font', 'Arial, sans-serif'),
	('reservation_page_name_background_color', 'rgba(255, 255, 255, .3)'),
	('reservation_top_image_background_color', 'transparent'),
	('reservation_form_label_background_color', 'rgba(255, 255, 255, .3)'),
	('reservation_page_font_size', '45px'),
	('reservation_subtitles_font_size', '25px'),
	('reservation_subtitles_background_color', 'rgba(255, 255, 255, .3)'),
	('reservation_messages_font_size', '18px'),
	('reservation_attendees_font_size', '18px'),
	('reservation_page_background_blend_mode', 'lighten, luminosity'),
	('reservation_page_background_backdrop_filter', 'blur(8px)'),
	('reservation_font_color', 'black'),
	('reservation_background_color', 'rgba(255, 255, 255, .5)'),
	('reservation_submit_background_color', '#4CAF50'),
	('reservation_submit_color', 'white'),
	('reservation_undo_submit_background_color', '#fc0303'),
	('reservation_undo_submit_color', 'white'),
	('admin_presharedkey', 'secretkey'),
	('reservation_max_additional_info', 'full text info'),
	('reservation_no_data_additional_info', 'nobody registered text'),
	('reservation_disabled_additional_info', 'text'),
	('reservation_additional_disclaimer', 'disclaimer'),
	('reservation_additional_disclaimer_title', 'infos'),
	('reservation_name_maxchar', '30'),
	('reservation_name_minchar', '4'),
	('reservation_name_allowunicode', '1'),
	('reservation_buttons_border', '1px solid black'),
	('reservation_top_image_max_width', '100%'),
	('reservation_top_image_max_height', 'auto'),
	('reservation_page_favicon', 'myfavicon.ico'),
	('reservation_show_attendees_enabled', '1'),
	('reservation_show_reservation_limit_enabled', '1'),
	('reservation_name_blacklist_unicode_enable', '1'),
	('reservation_name_blacklist', 'this,is,list'),
	('reservation_email_whitelist_enable', '0'),
	('reservation_email_whitelist', 'empty'),
	('reservation_name_blacklist_unicode_base64', 'somebase64,somebase64'),
	('reservation_name_blacklist_enable', '1'),
	('reservation_email_required', '1'),
	('reservation_name_whitelist_regex_enable', '0'),
	('reservation_name_whitelist_regex', 'empty'),
	('reservation_email_whitelist_regex_enable', '0'),
	('reservation_email_whitelist_regex', 'empty'),
	('reservation_buttons_font_size', '16px'),
	('reservation_attendees_font_color', 'black'),
	('reservation_attendees_background_color', 'rgba(255, 255, 255, .3)'),
	('reservation_additional_info_link', 'mylink'),
	('reservation_additional_info_link_text', 'linktext'),
	('reservation_details_info_link', 'mylink'),
	('reservation_details_info_link_text', 'linktext'),
	('reservation_token_enabled', '1'),
	('reservation_token', 'mytoken'),
	('reservation_invalid_token_info', 'error text'),
	('reservation_show_additional_disclaimer_enabled', '1'),
	('reservation_show_details_info_link', '1'),
	('reservation_show_additional_info_link', '0'),
	('moderator_presharedkey', 'secretkey'),
	('reservation_top_image_alt_description', 'image description'),
	('reservation_appinfo_font_color', 'white'),
	('reservation_additional_disclaimer_font_color', 'black'),
	('reservation_additional_disclaimer_font_size', '18px'),
	('reservation_allow_get_submit', '0');