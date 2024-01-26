/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS `events` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `events`;

CREATE TABLE IF NOT EXISTS `settings` (
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(16000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) /*!50100 TABLESPACE `innodb_system` */ ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`name`, `value`) VALUES
	('reservation_page_background_image', ''),
	('reservation_max', '50'),
	('primary_textfield_name_placeholder_hint', 'Hier den Namen eingeben'),
	('primary_textfield_email_placeholder_hint', 'Hier die E-Mail-Adresse eingeben. Diese wird auch im Anschluss Verifiziert.'),
	('reservation_additional_info', ''),
	('reservation_details', ''),
	('reservation_name', ''),
	('reservation_top_image', ''),
	('reservation_enabled', '1'),
	('reservation_undo_enabled', '1'),
	('reservation_page_background_color', 'transparent'),
	('reservation_page_font', 'Arial, sans-serif'),
	('reservation_page_name_background_color', 'transparent'),
	('reservation_top_image_background_color', 'transparent'),
	('reservation_form_label_background_color', 'rgba(255, 255, 255, .4)'),
	('reservation_page_font_size', '45px'),
	('reservation_subtitles_font_size', '25px'),
	('reservation_subtitles_background_color', 'rgba(255, 255, 255, .4)'),
	('reservation_messages_font_size', '18px'),
	('reservation_attendees_font_size', '18px'),
	('reservation_page_background_blend_mode', 'lighten, luminosity'),
	('reservation_page_background_backdrop_filter', 'blur(8px)'),
	('reservation_font_color', '#000000'),
	('reservation_background_color', 'rgba(255, 255, 255, .6)'),
	('reservation_submit_background_color', '#4caf50'),
	('reservation_submit_color', '#ffffff'),
	('reservation_undo_submit_background_color', '#fc0303'),
	('reservation_undo_submit_color', '#ffffff'),
	('admin_presharedkey', 'secure_adminkey'),
	('reservation_max_additional_info', ''),
	('reservation_no_data_additional_info', ''),
	('reservation_disabled_additional_info', ''),
	('reservation_additional_disclaimer', ''),
	('reservation_additional_disclaimer_title', 'Technische Infos'),
	('reservation_name_maxchar', '30'),
	('reservation_name_minchar', '3'),
	('reservation_name_allowunicode', '1'),
	('reservation_buttons_border', '1px solid black'),
	('reservation_top_image_max_width', '100%'),
	('reservation_top_image_max_height', 'auto'),
	('reservation_page_favicon', ''),
	('reservation_show_attendees_enabled', '1'),
	('reservation_show_reservation_limit_enabled', '1'),
	('reservation_name_blacklist_unicode_enable', '0'),
	('reservation_name_blacklist', ''),
	('reservation_email_whitelist_enable', '0'),
	('reservation_email_whitelist', 'empty'),
	('reservation_name_blacklist_unicode_base64', ''),
	('reservation_name_blacklist_enable', '1'),
	('reservation_email_required', '1'),
	('reservation_name_whitelist_regex_enable', '0'),
	('reservation_name_whitelist_regex', 'empty'),
	('reservation_email_whitelist_regex_enable', '0'),
	('reservation_email_whitelist_regex', 'empty'),
	('reservation_buttons_font_size', '16px'),
	('reservation_attendees_font_color', '#000000'),
	('reservation_attendees_background_color', 'rgba(255, 255, 255, .4)'),
	('reservation_additional_info_link', ''),
	('reservation_additional_info_link_text', ''),
	('reservation_details_info_link', ''),
	('reservation_details_info_link_text', ''),
	('reservation_token_enabled', '1'),
	('reservation_token', 'mysite_token'),
	('reservation_invalid_token_info', 'Falscher Token'),
	('reservation_show_additional_disclaimer_enabled', '0'),
	('reservation_show_details_info_link', '1'),
	('reservation_show_additional_info_link', '0'),
	('moderator_presharedkey', 'mysite_moderator_key'),
	('reservation_top_image_alt_description', ''),
	('reservation_appinfo_font_color', '#000000'),
	('reservation_additional_disclaimer_font_color', '#ffffff'),
	('reservation_additional_disclaimer_font_size', '18px'),
	('reservation_allow_get_submit', '0'),
	('reservation_embed_js1_script', ''),
	('reservation_embed_js1_enabled', '0'),
	('reservation_embed_div1_id', ''),
	('reservation_embed_external_css_enable', '0'),
	('reservation_embed_external_css_file', ''),
	('reservation_embed_canvas1_id', 'empty'),
	('reservation_embed_js2_script', ''),
	('reservation_embed_js2_enabled', '0'),
	('reservation_embed_canvas1_enable', '0'),
	('reservation_embed_js1_closed_tag_enabled', '0'),
	('reservation_embed_content_wrapped_div1_class', ''),
	('reservation_embed_content_wrapped_div1_enable', '0'),
	('reservation_embed_content_wrapped_div1_id', ''),
	('reservation_show_project_credits', '1'),
	('reservation_additional_info_link_noreferrer', '1'),
	('reservation_details_info_link_noreferrer', '1'),
	('reservation_page_main_font_color', '#66ff00'),
	('reservation_subtitles_font_color', '#000000'),
	('reservation_show_additional_popup_button', '1'),
	('reservation_additional_popup_button_caption', 'FAQ'),
	('reservation_additional_popup_button_content', ''),
	('reservation_additional_popup_button_font_color', '#ffffff'),
	('reservation_additional_popup_button_background_color', '#01c7fc'),
	('reservation_additional_popup_button_content_background_color', 'rgba(255, 255, 255, .8)'),
	('reservation_additional_popup_button_content_font_color', '#000000'),
	('reservation_show_checkbox_user_readconditions_enabled', '1'),
	('reservation_show_checkbox_user_readconditions_caption', 'Hiermit bestätige ich, dass ich das FAQ sowie die Datenschutzerklärung gelesen habe.'),
	('reservation_additional_popup_button_content_headline_font_color', '#000000'),
	('reservation_page_main_font_shadow', '#000000 2px 0 10px'),
	('reservation_additional_popup_button_content_headline_font_shadow', '#000000 2px 0 5px'),
	('reservation_mail_smtp_server_hostname', ''),
	('reservation_mail_smtp_server_port', '587'),
	('reservation_mail_smtp_from_address', ''),
	('reservation_mail_smtp_use_smtps', '1'),
	('reservation_mail_smtp_use_authentification', '1'),
	('reservation_mail_smtp_authentification_username', ''),
	('reservation_mail_smtp_authentification_password', ''),
	('reservation_mail_smtp_encryption_mechanism', '0'),
	('reservation_mail_smtp_reply_to_address', ''),
	('reservation_email_approvement_step1_enable', '1'),
	('reservation_email_approvement_step1_mail_to', '%UserMail%'),
	('reservation_email_approvement_step1_mail_body', 'Hallo %UserName%,<br> dies ist eine automatische Nachricht des Reservierungssystems %SiteTitle%.<br> <br><b>Bitte klicke auf folgenden Link, um deine E-Mail zu bestätigen: %ValidationURL_HTML%.</b><br> <br> Sollte der Link nicht funktionieren, bitte füge folgenden URL manuell in das Adressfeld deines Browsers ein:<br>%ValidationURL%.<br> <br>Falls du diese E-Mail nicht angefordert hast, kannst du diese Nachricht ignorieren.'),
	('reservation_email_approvement_step2_enable', '0'),
	('reservation_email_approvement_step2_mail_to', ''),
	('reservation_email_approvement_step2_mail_body', 'Name der Instanz: %SiteTitle%.<br>Benutzername: %UserName%<br>E-Mail: %UserMail%<br>IP: %RemoteIP%<br>Benutzer freigeben: %ValidationURL_HTML%'),
	('reservation_email_approvement_step1_mail_subject', '%SiteTitle% - Bitte bestätige deine E-Mail'),
	('reservation_email_approvement_step2_mail_subject', 'Überprüfung durch Moderator ausstehend für %UserName%'),
	('reservation_email_approvement_step1_popup_text', 'Wir haben an die angegebene E-Mail eine Nachricht mit einem Bestätigungs-Link geschickt. <br><b>Bitte prüfe dein Postfach sowie ggf. deinen Spam-Ordner.</b>'),
	('reservation_email_approvement_step2_popup_text', 'Deine E-Mail-Adresse wurde erfolgreich bestätigt. <br>Die Registrierung muss nun von einem Moderator oder Administrator abgeschlossen werden. Du erhältst eine E-Mail, sobald deine Registrierung vollständig abgeschlossen ist.'),
	('reservation_mail_smtp_sourceip_ratelimit_enable', '1'),
	('reservation_mail_smtp_sourceip_ratelimit', '10'),
	('reservation_mail_smtp_sourceip_ratelimit_header', 'HTTP_X_FORWARDED_FOR'),
	('reservation_email_approvement_step2_popup_header', 'E-Mail bestätigt - Freischaltung durch Moderator'),
	('reservation_email_approvement_step1_popup_header', 'E-Mail bestätigen'),
	('reservation_email_approvement_successful_notification_enable', '1'),
	('reservation_email_approvement_successful_notification_mail_to', '%UserMail%'),
	('reservation_email_approvement_successful_notification_mail_subject', '%SiteTitle% - Reservierung genehmigt'),
	('reservation_email_approvement_successful_notification_mail_body', 'Hallo %UserName%,<br> dies ist eine automatische Nachricht des Reservierungssystems %SiteTitle%.<br> <br><b>Du wurdest soeben mit der angegebenen E-Mail sowie Namen freigeschaltet und erscheinst nun auf der Reservierungsseite.</b><br> <br>Wenn du dich abmelden möchtest, kann du dies mit einem Klick auf diesen Link tun: %UndoReservationURL_HTML%.<br>Sollte der Link nicht funktionieren, bitte füge folgenden URL manuell in das Adressfeld deines Browsers ein:<br>%UndoReservationURL%<br><br><br> <br><small>Diese E-Mail wurde automatisch versendet, bitte antworte nicht auf diese Mail.<br>Die IP-Adresse, von welcher die Bestätigung der E-Mail angefordert wurde, lautet %RemoteIP%</small>'),
	('reservation_show_gdpr_disclaimer_enabled', '1'),
	('reservation_gdpr_disclaimer_button_font_color', '#000000'),
	('reservation_gdpr_disclaimer_button_background_color', '#ffdd00'),
	('reservation_gdpr_disclaimer_button_content', 'GDPR Content'),
	('reservation_show_technical_info', '1'),
	('reservation_additional_info_link_text_font_color', '#ff0000'),
	('reservation_management_link_text_font_color', '#ff0000'),
	('reservation_undo_via_mail_link_enabled', '1');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;