<?php
    // Include PHP Mailer (you can comment it out if you dont use e-mail validation)
    // Third-Party-Library, please see https://github.com/PHPMailer/PHPMailer.
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require './phpmailer/PHPMailer.php';
    require './phpmailer/SMTP.php';
    require './phpmailer/Exception.php';
    // End include PHP Mailer

    // Global settings
    $app_version = "1.3.0";
    $http_schema = "https";
    $base_url = "<base url>";
    $background_Image_dir = "assets/background";
    $top_image_dir = "assets/top_image";
    $favicon_dir = "assets/favicon";
    $available_languages = array("en", "de");
    // $static_lang = "de";

    // Etablish connection to database
    // DO NOT USE root mysql user!
    $dbhost = '<mysql hostname or ip>'; // Hostname of database
    $dbname = 'events';                 // Database name
    $dbuser = '<mysql user>';           // Database username
    $dbpass = '<mysql password>';       // Datebase password

    // Empty string and integer declarations, do NOT modify!
    // Settings should be set in database, not in code!
    $adminpw = "";
    $moderatorpw = "";
    $adminmode_enabled = 0;
    $moderatormode_enabled = 0;
    $token_valid = 0;

    // Get browser language form user HTTP header
    if (isset($static_lang)) {
    	// Lang is set static in global config skipping browser lang detection
    	$guilang = $static_lang;
    } else {
	// Lang is not static set, get browser lang list
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
            // Server returns lang header (browser)
            $langs = preferred_language($available_languages, $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
            // Extract best language for user
            $guilang = array_key_first($langs);
        } else {
            // Server returns no lang header (cli, wget), set to default en lang.
            $guilang = "en";
        }
    }

    // Extract current request URI
    $url =  $http_schema . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $url_clean = $http_schema . "://" . $base_url;
    $url_token_clean = $url_clean;

    // Add required headers for browser support
    header('Cache-Control: no-cache');
    header('X-Content-Type-Options: nosniff');

    try {
        $conn = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get lanng strings from database
        $langq = $conn->query("SELECT name, value FROM translations WHERE lang='$guilang'");
        $lang_array = $langq->fetchAll();

        // Get resavation settings from database
        $settingsq = $conn->query("SELECT name, value FROM settings");
        $settings_array = $settingsq->fetchAll();

        // Get all reservations
        $stmt = $conn->query("SELECT * FROM reservations");
        $reservations = $stmt->fetchAll();

        // Get all approvement sessions from database
        $stmt = $conn->query("SELECT * FROM approvements");
        $approvements = $stmt->fetchAll();

        // Get maximum allowed reservations
        $max_reservations = getvaluefromsettings($settings_array, 'reservation_max');

        // Get admin credentials
        $adminpw = getvaluefromsettings($settings_array, 'admin_presharedkey');
        $moderatorpw = getvaluefromsettings($settings_array, 'moderator_presharedkey');

        // Check if adminmode is entered
        if (isset($_GET['adminpw'])) {
            // Admin variable was provided via GET, check if preshared key equals key in database
            if ($_GET['adminpw'] == $adminpw) {
                // Preshared key equals key in Database, unlock admin-mode and bypass token validation
                $adminmode_enabled = 1;
                $token_valid = 1;

                // Add admin pre-shared-key to base url
                $url_clean = $url_clean . "?adminpw=" . $adminpw;
            }
        } else {
            if (isset($_GET['moderatorpw'])) {
                if ($_GET['moderatorpw'] == $moderatorpw) {
                // Preshared key equals key in Database, unlock moderation-mode and bypass token validation
                $moderatormode_enabled = 1;
                $token_valid = 1;

                // Add moderator pre-shared-key to base url
                $url_clean = $url_clean . "?moderatorpw=" . $moderatorpw;
                }
            }
        }

        // Get path for images used
        $background_Image_dir_comp = $background_Image_dir . '/' . getvaluefromsettings($settings_array, 'reservation_page_background_image');
        $top_image_comp = $top_image_dir . '/' . getvaluefromsettings($settings_array, 'reservation_top_image');
        $favicon_dir_comp = $favicon_dir . '/' . getvaluefromsettings($settings_array, 'reservation_page_favicon');

        if (getvaluefromsettings($settings_array, 'reservation_token_enabled') == 1) {
            $site_token = getvaluefromsettings($settings_array, 'reservation_token');
            $url_token_clean = $url_token_clean . "?t=" . $site_token;
            if (isset($_GET['t'])) {
                if ($_GET['t'] == $site_token) {
                    // Valid token, alter url clean
                    $token_valid = 1;
                    if ($adminmode_enabled != 1) {
                        // Onyl if admin mode is not enabled alter url (otherwise is not needed)
                        $url_clean = $url_clean . "?t=" . $site_token;
                    }
                }
            }
        } else {
            // Token validation not enabled, bypass
            $token_valid = 1;
        }

        // Check if attendee list download is requested
        if (isset($_GET['download_attendees']) && ($adminmode_enabled == 1 || $moderatormode_enabled == 1)) {
            if ($_GET['download_attendees'] == 1) {
                // Start download file
                array_csv_download($reservations);
            }
        }
    } catch (PDOException $e) {
        echo "Fehler: " . $e->getMessage();
    }
?>

<!DOCTYPE html>
    <?php
    echo '<html lang="' . $guilang . '" ><head>';
        echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        if ($token_valid == 1) {
            echo '<title>' . getvaluefromsettings($settings_array, 'reservation_name') . '</title>';
            if (getvaluefromsettings($settings_array, 'reservation_page_favicon') != "") {
                echo '<link rel="icon" type="image/x-icon" href="';
                echo $favicon_dir_comp;
                echo '">';
            }
        } else {
            echo '<title>' . getvaluefromsettings($settings_array, 'reservation_invalid_token_info') . '</title>';
        }

        // Check if custom css file should be embed
        if (getvaluefromsettings($settings_array, 'reservation_embed_external_css_enable') == 1){
            echo '<link rel="stylesheet" href="';
            echo getvaluefromsettings($settings_array, 'reservation_embed_external_css_file');
            echo '">';
        }
    ?>
    <style>
        body {
            padding: 20px;
            margin: 0;
            background-image: url('<?php echo $background_Image_dir_comp; ?>');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            background-origin: border-box;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_page_background_color'); ?>;
            -webkit-backdrop-filter: <?php echo getvaluefromsettings($settings_array, 'reservation_page_background_color'); ?>;
            backdrop-filter: <?php echo getvaluefromsettings($settings_array, 'reservation_page_background_backdrop_filter'); ?>;
            background-blend-mode: <?php echo getvaluefromsettings($settings_array, 'reservation_page_background_blend_mode'); ?>;
        }

        .content {
            font-family: <?php echo getvaluefromsettings($settings_array, 'reservation_page_font'); ?>;
            text-align: center;
        }

        h1, h2 {
            word-wrap: break-word;
            overflow-wrap: anywhere;
            margin-bottom: 20px;
        }

        h1 {
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_page_font_size'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_page_name_background_color'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_page_main_font_color'); ?>;
            text-shadow: <?php echo getvaluefromsettings($settings_array, 'reservation_page_main_font_shadow'); ?>;
        }

        h2 {
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_subtitles_font_size'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_subtitles_background_color'); ?>;
        }

        .overlay h1 {
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_popup_button_content_headline_font_color'); ?>;
            text-shadow: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_popup_button_content_headline_font_shadow'); ?>;
        }

        @media all and (max-device-width: 400px){
            h1 {
                font-size: 8vw;
            }
            h2 {
                font-size: 6vw;
            }
            .info-box {
                font-size: 4vw;
            }
            input::placeholder {
                font-size: 3vw;
            }
        }

        details {
            transition: 0.2s background linear;
        }

        details > summary {transition: color 1s;}
        details[open] > summary {color: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_info_link_text_font_color'); ?>;}

        form {
            margin-bottom: 30px;
            text-align: center;
        }

        label {
            display: block;
            padding: 5px;
            font-weight: bold;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_form_label_background_color'); ?>;
        }

        textarea {
            border-radius: 5px;
            border: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_border'); ?>;
            box-sizing: border-box;
            font-family: <?php echo getvaluefromsettings($settings_array, 'reservation_page_font'); ?>;
            width: 100%;
        }

        input[type="text"], input[type="email"], input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_border'); ?>;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_font_size'); ?>;
        }

        input[type="checkbox"] {
            margin: 20px;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_font_size'); ?>;
            -ms-transform: scale(2); /* IE */
            -moz-transform: scale(2); /* FF */
            -webkit-transform: scale(2); /* Safari and Chrome */
            -o-transform: scale(2); /* Opera */
            transform: scale(2);
        }

        input[type="submit"]:hover {
            filter: brightness(95%);
        }

        input[type="submit"] {
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_submit_background_color'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_submit_color'); ?>;
            cursor: pointer;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            margin-bottom: 10px;
        }

        a {
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_management_link_text_font_color'); ?>;
            text-decoration: none;
        }

        .a_additional_info {
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_info_link_text_font_color'); ?>;
            text-decoration: none;
        }

        .attendees_div {
            width: 100%;
            padding-top: 5px;
            padding-bottom: 5px;
            margin-left: auto;
            margin-right: auto;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_attendees_font_size'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_attendees_background_color'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_attendees_font_color'); ?>;
        }

        .image_top {
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_top_image_background_color'); ?>;
            max-width: <?php echo getvaluefromsettings($settings_array, 'reservation_top_image_max_width'); ?>;
            max-height: <?php echo getvaluefromsettings($settings_array, 'reservation_top_image_max_height'); ?>;
        }

        .button_undo {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_border'); ?>;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_font_size'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_undo_submit_background_color'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_undo_submit_color'); ?>;
            cursor: pointer;
        }

        .button_undo:hover {
            filter: brightness(95%);
        }

        .button_remove_reservation {
            width: 100%;
            display: block;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_border'); ?>;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_font_size'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_undo_submit_background_color'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_undo_submit_color'); ?>;
            cursor: pointer;
        }

        .button_remove_reservation:hover {
            filter: brightness(95%);
        }

        .info-box {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid black;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_messages_font_size'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_font_color'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_background_color'); ?>;
        }

        .success_info-box {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 2px solid #000;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_messages_font_size'); ?>;
            background-color: #4CAF50;
        }

        .fail_info-box {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 2px solid #000;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_appinfo_font_color'); ?>;
            background-color: #fc0303;
        }

        .additional_disclaimer-div {
            width: 100%;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_disclaimer_font_size'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_disclaimer_font_color'); ?>;
        }

        .appinfo-div {
            font-size: 10px;
            font-family: Arial, sans-serif;
            text-align: center;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_disclaimer_font_color'); ?>;
        }

        .forbidden {
            cursor: not-allowed;
        }

        .fade-out {
            -webkit-animation: fadeOut ease 3s;
            -moz-animation: fadeOut ease 3s;
            -o-animation: fadeOut ease 3s;
            -ms-animation: fadeOut ease 3s;
            animation: fadeOut ease 3s;
        }

        .button_popup {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_border'); ?>;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_font_size'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_popup_button_background_color'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_popup_button_font_color'); ?>;
            cursor: pointer;
            display: block;
        }

        .button_popup_gdpr_disclaimer {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_border'); ?>;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_font_size'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_gdpr_disclaimer_button_background_color'); ?>;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_gdpr_disclaimer_button_font_color'); ?>;
            cursor: pointer;
            display: block;
        }

        .button_popup_gdpr_disclaimer:hover {
            filter: brightness(95%);
        }

        .button_popup:hover {
            filter: brightness(95%);
        }

        .overlay {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            transition: opacity 500ms;
            visibility: hidden;
            opacity: 0;
            }
            .overlay:target {
            visibility: visible;
            opacity: 1;
            z-index: 2;
	        overflow-y: scroll;
	        -webkit-overflow-scrolling: touch;
        }

        .popup {
            margin: 70px auto;
            padding: 20px;
            background: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_popup_button_content_background_color'); ?>;
            border-radius: 5px;
            width: 90%;
            position: relative;
            transition: all 5s ease-in-out;
            z-index: 2;
	        overflow-y: scroll;
	        -webkit-overflow-scrolling: touch;
        }

        .popup h2 {
            margin-top: 0;
        }

        .popup .close {
            position: absolute;
            top: 18px;
            right: 30px;
            transition: all 200ms;
            font-size: 30px;
            font-weight: bold;
            text-decoration: none;
        }

        .popup .close:hover {
            filter: brightness(95%);
        }
            
        .popup .content_popup {
            overflow: auto;
            color: <?php echo getvaluefromsettings($settings_array, 'reservation_additional_popup_button_content_font_color'); ?>;
	        -webkit-overflow-scrolling: touch;
        }

        @keyframes fadeOut {
        0% {
            opacity:1;
        }
        100% {
            opacity:0;
        }
        }

        @-moz-keyframes fadeOut {
        0% {
            opacity:1;
        }
        100% {
            opacity:0;
        }
        }

        @-webkit-keyframes fadeOut {
        0% {
            opacity:1;
        }
        100% {
            opacity:0;
        }
        }

        @-o-keyframes fadeOut {
        0% {
            opacity:1;
        }
        100% {
            opacity:0;
        }
        }

        @-ms-keyframes fadeOut {
        0% {
            opacity:1;
        }
        100% {
            opacity:0;
        }
        }
</style>
</head>
<body>
    <div class="content" id="content">
    <?php
        // Check if custom javascript number 1 code should be embed
        if (getvaluefromsettings($settings_array, 'reservation_embed_js1_enabled') == 1){
            // Embed custom js code and load script
            echo '<div class="custom_div" id="';
            echo getvaluefromsettings($settings_array, 'reservation_embed_div1_id');
            echo '">';
            if (getvaluefromsettings($settings_array, 'reservation_embed_js1_closed_tag_enabled') == 1){
                echo '</div>';
            }
            echo '<script src="';
            echo getvaluefromsettings($settings_array, 'reservation_embed_js1_script');
            echo '"></script>';
            if (getvaluefromsettings($settings_array, 'reservation_embed_canvas1_enable') == 1){
                echo '<canvas class="';
                echo getvaluefromsettings($settings_array, 'reservation_embed_canvas1_id');;
                echo '"></canvas>';
            }
        }

        // Check if custom javascript number 2 code should be embed
        if (getvaluefromsettings($settings_array, 'reservation_embed_js2_enabled') == 1){
            // Embed custom js code and load script
            echo '<script src="';
            echo getvaluefromsettings($settings_array, 'reservation_embed_js2_script');
            echo '"></script>';
        }

        // Check if custom wrapped div should be inserted (for script handling)
        if (getvaluefromsettings($settings_array, 'reservation_embed_content_wrapped_div1_enable') == 1){
            // Embed custom div with id
            echo '<div class="';
            echo getvaluefromsettings($settings_array, 'reservation_embed_content_wrapped_div1_class');
            echo '" id="';
            echo getvaluefromsettings($settings_array, 'reservation_embed_content_wrapped_div1_id');
            echo '">';
        }

        if ($adminmode_enabled == 1) {
            // Show notification that admin-mode is enabled
            echo '<div class="info-box">';
            echo getstrfromtranslation($lang_array, 'notification_admin_mode_enabled');
            echo '</div>';
        }
        if ($moderatormode_enabled == 1) {
            // Show notification that moderation-mode is enabled
            echo '<div class="info-box">';
            echo getstrfromtranslation($lang_array, 'notification_moderator_mode_enabled');
            echo '</div>';
        }

        try {
            $was_success = 0;
            $validation_step0_required = 0;
            $validation_step1_required = 0;
            if ($token_valid == 1) {
                // Check if submit via URL (get) is allowed
                if (getvaluefromsettings($settings_array, 'reservation_allow_get_submit') == 1 && isset($_GET['name'])) {
                    $field_get_name = $_GET['name'];
                    $_POST['name'] = $field_get_name;
                    if (getvaluefromsettings($settings_array, 'reservation_email_required') == 1 && isset($_GET['mail'])) {
                        $field_get_mail = $_GET['mail'];
                        $_POST['email'] = $field_get_mail;
                    }
                    if (isset($_GET['register'])) {
                        $_POST['submit'] = 1;
                    } else {
                        if (isset($_GET['unregister'])) {
                            $_GET['delete'] = 1;
                        }
                    }
                }
                // Check, if user wants to undo reservation
                if (isset($_GET['delete']) && isset($_POST['email']) && isset($_POST['name']) && getvaluefromsettings($settings_array, 'reservation_undo_enabled') == 1) {
                    $email = $_POST['email'];
                    $name = $_POST['name'];

                    // Sanitize strings
                    $name = filter_string_polyfill($name);
                    $email = filter_var($email,FILTER_SANITIZE_EMAIL);
                    $name_dbsafe = filter_string_dbsafe($name);

                    // Check, if mail is not empty
                    if ($email != "") {
                        // Delete reservation entry from database only if e-mail is correct
                        if (remove_reservation_mail_and_name($conn, $name_dbsafe, $email)) {
                            echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_undo_success'));
                            $was_success = 1;
                        } else {
                            echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_undo_failed'));
                        }
                    } else {
                        echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_undo_failed_no_mail'));
                    }
                } else {
                    // Check if form was subitted
                    if (isset($_POST['submit']) && (getvaluefromsettings($settings_array, 'reservation_enabled') == 1 || $adminmode_enabled == 1) && !isset($_GET['delete'])) {
                        $name = $_POST['name'];
                        $email = $_POST['email'];
                        $user_accept_conditions = 0;
                        if (isset($_POST['user_accept_conditions'])) {
                            $user_accept_conditions = $_POST['user_accept_conditions'];
                        }

                        // Sanitize strings
                        $name = filter_string_polyfill($name);
                        $name_base64 = base64_encode($name);
                        $email = filter_var($email,FILTER_SANITIZE_EMAIL);
                        $user_accept_conditions = filter_boolstr($user_accept_conditions);
                        $name_dbsafe = filter_string_dbsafe($name);

                        // Check if user inputs are valid
                        $isvalidname = checkifnameisvalid($name, getvaluefromsettings($settings_array, 'reservation_name_maxchar'), getvaluefromsettings($settings_array, 'reservation_name_minchar'), getvaluefromsettings($settings_array, 'reservation_name_allowunicode'), getvaluefromsettings($settings_array, 'reservation_name_blacklist_enable'), getvaluefromsettings($settings_array, 'reservation_name_blacklist'), getvaluefromsettings($settings_array, 'reservation_name_blacklist_unicode_enable'), getvaluefromsettings($settings_array, 'reservation_name_blacklist_unicode_base64'), getvaluefromsettings($settings_array, 'reservation_name_whitelist_regex_enable'), getvaluefromsettings($settings_array, 'reservation_name_whitelist_regex'));
                        $isvalidmail = checkifmailisvalid($email, getvaluefromsettings($settings_array, 'reservation_email_required'), getvaluefromsettings($settings_array, 'reservation_email_whitelist_enable'), getvaluefromsettings($settings_array, 'reservation_email_whitelist'), getvaluefromsettings($settings_array, 'reservation_email_whitelist_regex_enable'), getvaluefromsettings($settings_array, 'reservation_email_whitelist_regex'));
                        // Allow no valid username if admin-mode is enabled (bypass).
                        // Only check if name is at least one char.
                        if ($adminmode_enabled == 1 && !$name_dbsafe = '') {
                            $isvalidname = 1;
                        }
                        // Allow no valid mail if admin-mode is enabled (bypass)
                        if ($adminmode_enabled == 1) {
                            $isvalidmail = 1;
                        }
                        
                        $isvaliduserconsent = 0;

                        // Check if user has to accept to terms (checkbox has to be checked)
                        if (getvaluefromsettings($settings_array, 'reservation_show_checkbox_user_readconditions_enabled') == 1) {
                            // Has user checked checkbox?
                            if ($user_accept_conditions == 1) {
                                $isvaliduserconsent = 1;
                            }
                            // If admin mode enabled, bypass check
                            if ($adminmode_enabled == 1) {
                                $isvaliduserconsent = 1;
                            }
                        } else {
                            // Not required, skipping test
                            $isvaliduserconsent = 1;
                        }
                        
                        if ($name != "" && $isvalidmail == 1 &&  $isvalidname == 1 &&  $isvaliduserconsent == 1) {
                            // Check, if name already exists in database
                            if (!array_contains($reservations, 'name', $name_dbsafe)) {
                                //Check if reservation limit is reached, only continue if places are left
                                if (count($reservations) < $max_reservations || getvaluefromsettings($settings_array, 'reservation_max') == 0) {
                                    // Generate usertoken
                                    $usertoken_tmp = uniqid('user_', true);
                                    // All constrains are fullfilled, check if email validation is enabled
                                    // Skip validation of mail if admin user mode is active
                                    if (getvaluefromsettings($settings_array, 'reservation_email_approvement_step1_enable') == 1 && $adminmode_enabled == 0) {
                                        // Check, if IP has reached rate limit
                                        $ratelimit_header = getvaluefromsettings($settings_array, 'reservation_mail_smtp_sourceip_ratelimit_header');
                                        $remoteip_tmp = $_SERVER[$ratelimit_header];
                                        if (getvaluefromsettings($settings_array, 'reservation_mail_smtp_sourceip_ratelimit_enable') == 0 || approvement_checkratelimit($conn, $approvements, $remoteip_tmp, 'step1_sourceip', 'step1_sourceip_count', getvaluefromsettings($settings_array, 'reservation_mail_smtp_sourceip_ratelimit'))) {
                                            // Rate limit for IP not reached, initiate approvement
                                            // Replace step 1 and step 2 variables and get real email to
                                            $step1mail_tmp = replace_vars_validation(getvaluefromsettings($settings_array, 'reservation_email_approvement_step1_mail_to'), $name_dbsafe, $email);
                                            $step2mail_tmp = replace_vars_validation(getvaluefromsettings($settings_array, 'reservation_email_approvement_step2_mail_to'), $name_dbsafe, $email);
                                            // Generate step 1 and step 2 token
                                            $step1_token_tmp = uniqid('00001_', true);
                                            $step2_token_tmp = uniqid('00002_', true);
                                            // Generate a ref link string for validation body
                                            $validation_url_tmp = $url_token_clean . '&validation_token=' . $step1_token_tmp;
                                            $validation_url_html_tmp = '<a href="' . $validation_url_tmp . '" title="' . getstrfromtranslation($lang_array, 'reservation_email_validation_href_link_title') . '">' . getstrfromtranslation($lang_array, 'reservation_email_validation_href_link_title') . '</a>';
                                            // Generate mail template from settings and replace known variables
                                            $mailbody_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_step1_mail_body'), $name_dbsafe, $email, $step1_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), $validation_url_tmp, $validation_url_html_tmp, '', '', $remoteip_tmp);
                                            $mailsubject_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_step1_mail_subject'), $name_dbsafe, $email, $step1_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), $validation_url_tmp, $validation_url_html_tmp, '', '', $remoteip_tmp);
                                            // Send mail
                                            if (send_mail_by_PHPMailer(getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_hostname'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_username'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_password'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_port'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_use_authentification'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_encryption_mechanism'), $step1mail_tmp, getvaluefromsettings($settings_array, 'reservation_mail_smtp_from_address'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_reply_to_address'), $mailsubject_tmp, $mailbody_tmp)) {
                                                if (approvement_step0($conn, $name_dbsafe, $name_base64, $email, $step1_token_tmp, $step2_token_tmp, $usertoken_tmp, $step1mail_tmp, $step2mail_tmp, date('Y-m-d H:i:s'), $_SERVER[$ratelimit_header])) {
                                                    $was_success = 1;
                                                    $validation_step0_required = 1;
                                                } else {
                                                    $was_success = 0;
                                                }
                                            } else {
                                                $was_success = 0;
                                            }
                                        }
                                    } else {
                                        // Add reservation into database
                                        if (add_reservation_to_db($conn, $name_dbsafe, $name_base64, $email, date('Y-m-d H:i:s'), $usertoken_tmp)) {
                                            $was_success = 1;
                                        } else {
                                            $was_success = 0;
                                        }
                                    }
                                    if ($was_success == 1) {
                                        if ($validation_step0_required == 1) {
                                            echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_step0_validation_info'));
                                        } else {
                                            echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_success'));
                                        }
                                    } else {
                                        // Something is wrong...
                                        echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed'));
                                    }
                                } else {
                                    // Reservation is not allowed, maximium is reached.
                                    echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed'));
                                }
                            } else {
                                echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed_name_duplicate'));
                            }
                        } else {
                            if (isset($_POST['name']) && isset($_POST['email'])) {
                                if ($isvalidname == 0 || $isvalidmail == 0) {
                                    // The name does not met the defined min or max lenght or contains not allowed unicode chars
                                    echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_constraints_not_met'));
                                } else {
                                    // Generic error. Show error message to user
                                    echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed'));
                                }
                            }
                        }
                    }

                    // Check if user wants to validate mail
                    if (isset($_GET['validation_token']) && getvaluefromsettings($settings_array, 'reservation_enabled') == 1 && !isset($_GET['delete'])) {
                        $validation_token_post = $_GET['validation_token'];
                        $validation_token_post = filter_var($validation_token_post,FILTER_SANITIZE_STRING);
                        // Pre-Check token
                        if (approvement_checkstr($validation_token_post)) {
                            // Check if the token is found on step 1 verification
                            if (approvement_checktoken($approvements, 0, $validation_token_post)) { 
                                // Token found and valid
                                // Get user info
                                $approvements_tmp = approvement_getuserinfo($approvements, $validation_token_post);
                                $email = $approvements_tmp['email'];
                                $name_dbsafe = $approvements_tmp['name'];
                                $name_base64 = $approvements_tmp['name_base64'];
                                $usertoken_tmp = $approvements_tmp['usertoken'];
                                $step1mail_tmp = $approvements_tmp['step1mail'];
                                $step2mail_tmp = $approvements_tmp['step2mail'];
                                $step1_token_tmp = $approvements_tmp['step1token'];
                                $step2_token_tmp = $approvements_tmp['step2token'];
                                $ratelimit_header = getvaluefromsettings($settings_array, 'reservation_mail_smtp_sourceip_ratelimit_header');
                                $remoteip_tmp = $_SERVER[$ratelimit_header];
                                if (getvaluefromsettings($settings_array, 'reservation_email_approvement_step2_enable') == 1) {
                                    // Check 2nd validation token
                                    if (approvement_checktoken($approvements, 2, $validation_token_post)) {
                                        // 2nd validation token found
                                        if (getvaluefromsettings($settings_array, 'reservation_mail_smtp_sourceip_ratelimit_enable') == 0 || approvement_checkratelimit($conn, $approvements, $remoteip_tmp, 'step2_sourceip', 'step2_sourceip_count', getvaluefromsettings($settings_array, 'reservation_mail_smtp_sourceip_ratelimit'))) {
                                            // Rate limit not reached
                                            // Update database
                                            approvement_step2_updatedb($conn, $step1_token_tmp);
                                            if (!array_contains($reservations, 'name', $name_dbsafe)) {
                                                // Add reservation into database
                                                if (add_reservation_to_db($conn, $approvements_tmp['name'], $approvements_tmp['name_base64'], $approvements_tmp['email'], date('Y-m-d H:i:s'), $approvements_tmp['usertoken'])) {
                                                    // Send notification if enabled
                                                    if (getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_enable') == 1) {
                                                        // Send mail
                                                        // Generate mail template from settings and replace known variables
                                                        $undo_reservation_url_tmp = $url_token_clean . '&usertoken=' . $usertoken_tmp . '&undo_reservation=1';
                                                        $undo_reservation_url_html_tmp = '<a href="' . $undo_reservation_url_tmp . '" title="' . getstrfromtranslation($lang_array, 'reservation_email_undo_reservation_href_link_title') . '">' . getstrfromtranslation($lang_array, 'reservation_email_undo_reservation_href_link_title') . '</a>';
                                                        $mail_notify_tmp = replace_vars_validation(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_to'), $name_dbsafe, $email);
                                                        $mailbody_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_body'), $name_dbsafe, $email, $step2_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), '', '', $undo_reservation_url_tmp, $undo_reservation_url_html_tmp, $remoteip_tmp);
                                                        $mailsubject_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_subject'), $name_dbsafe, $email, $step2_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), '', '', $undo_reservation_url_tmp, $undo_reservation_url_html_tmp, $remoteip_tmp);
                                                        if (send_mail_by_PHPMailer(getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_hostname'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_username'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_password'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_port'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_use_authentification'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_encryption_mechanism'), $mail_notify_tmp, getvaluefromsettings($settings_array, 'reservation_mail_smtp_from_address'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_reply_to_address'), $mailsubject_tmp, $mailbody_tmp)) {
                                                            // Clear database, workflow is finished
                                                            remove_approvement_step2($conn, $step2_token_tmp);
                                                            $was_success = 1;
                                                            echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_success'));
                                                        } else {
                                                            $was_success = 0;
                                                            echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed'));
                                                        }
                                                    } else {
                                                        $was_success = 1;
                                                        echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_success'));
                                                    }
                                                } else {
                                                    $was_success = 0;
                                                    echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_constraints_not_met'));
                                                }
                                            }
                                        }
                                    } else {
                                        // 1st validation token found
                                        if (getvaluefromsettings($settings_array, 'reservation_mail_smtp_sourceip_ratelimit_enable') == 0 || approvement_checkratelimit($conn, $approvements, $remoteip_tmp, 'step2_sourceip', 'step2_sourceip_count', getvaluefromsettings($settings_array, 'reservation_mail_smtp_sourceip_ratelimit'))) {
                                            // Rate limit not reached
                                            // Set validation state
                                            approvement_step1_updatedb($conn, $step1_token_tmp, date('Y-m-d H:i:s'));
                                            // Generate a ref link string for validation body
                                            $validation_url_tmp = $url_token_clean . '&validation_token=' . $step2_token_tmp;
                                            $validation_url_html_tmp = '<a href="' . $validation_url_tmp . '" title="' . getstrfromtranslation($lang_array, 'reservation_email_validation_href_link_title') . '">' . getstrfromtranslation($lang_array, 'reservation_email_validation_href_link_title') . '</a>';
                                            $undo_reservation_url_tmp = $url_token_clean . '&usertoken=' . $usertoken_tmp . '&undo_reservation=1';
                                            $undo_reservation_url_html_tmp = '<a href="' . $undo_reservation_url_tmp . '" title="' . getstrfromtranslation($lang_array, 'reservation_email_undo_reservation_href_link_title') . '">' . getstrfromtranslation($lang_array, 'reservation_email_undo_reservation_href_link_title') . '</a>';
                                            // Generate mail template from settings and replace known variables
                                            $mailbody_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_step2_mail_body'), $name_dbsafe, $email, $step2_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), $validation_url_tmp, $validation_url_html_tmp, $undo_reservation_url_tmp, $undo_reservation_url_html_tmp, $remoteip_tmp);
                                            $mailsubject_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_step2_mail_subject'), $name_dbsafe, $email, $step2_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), $validation_url_tmp, $validation_url_html_tmp, $undo_reservation_url_tmp, $undo_reservation_url_html_tmp, $remoteip_tmp);
                                            // Send mail
                                            if (send_mail_by_PHPMailer(getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_hostname'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_username'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_password'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_port'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_use_authentification'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_encryption_mechanism'), $step2mail_tmp, getvaluefromsettings($settings_array, 'reservation_mail_smtp_from_address'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_reply_to_address'), $mailsubject_tmp, $mailbody_tmp)) {
                                                $was_success = 1;
                                                $validation_step1_required = 1;
                                                echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_step1_validation_info'));
                                            } else {
                                                $was_success = 0;
                                            }
                                        } else {
                                            // Rate limit reached
                                            $was_success = 0;
                                            echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed_ratelimit'));
                                        }
                                    }
                                } else {
                                    // No update on state is needed, add user to reservation list. Get user info
                                    // Check, if name already exists in database
                                    if (!array_contains($reservations, 'name', $approvements_tmp['name'])) {
                                        // Add reservation into database
                                        if (add_reservation_to_db($conn, $approvements_tmp['name'], $approvements_tmp['name_base64'], $approvements_tmp['email'], date('Y-m-d H:i:s'), $approvements_tmp['usertoken'])) {
                                            // Remove approvement state from approvement table
                                            remove_approvement_step1($conn, $validation_token_post);

                                            if (getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_enable') == 1) {
                                                // Send mail
                                                // Generate mail template from settings and replace known variables
                                                $undo_reservation_url_tmp = $url_token_clean . '&usertoken=' . $usertoken_tmp . '&undo_reservation=1';
                                                $undo_reservation_url_html_tmp = '<a href="' . $undo_reservation_url_tmp . '" title="' . getstrfromtranslation($lang_array, 'reservation_email_undo_reservation_href_link_title') . '">' . getstrfromtranslation($lang_array, 'reservation_email_undo_reservation_href_link_title') . '</a>';
                                                $mail_notify_tmp = replace_vars_validation(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_to'), $name_dbsafe, $email);
                                                $mailbody_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_body'), $name_dbsafe, $email, $step2_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), '', '', $undo_reservation_url_tmp, $undo_reservation_url_html_tmp, $remoteip_tmp);
                                                $mailsubject_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_subject'), $name_dbsafe, $email, $step2_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), '', '', $undo_reservation_url_tmp, $undo_reservation_url_html_tmp, $remoteip_tmp);
                                                if (send_mail_by_PHPMailer(getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_hostname'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_username'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_password'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_port'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_use_authentification'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_encryption_mechanism'), $mail_notify_tmp, getvaluefromsettings($settings_array, 'reservation_mail_smtp_from_address'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_reply_to_address'), $mailsubject_tmp, $mailbody_tmp)) {
                                                    $was_success = 1;
                                                    echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_success'));
                                                } else {
                                                    $was_success = 0;
                                                    echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed'));
                                                }
                                            } else {
                                                $was_success = 1;
                                                echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_success'));
                                            }
                                        } else {
                                            $was_success = 0;
                                            echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed'));
                                        }
                                    } else {
                                        $was_success = 0;
                                        echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_constraints_not_met'));
                                    }
                                }
                            } else {
                                // Token has valid format, but was not found, show error
                                $was_success = 0;
                                echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed_unknown_token'));
                            }
                        } else {
                            // Malformed token, show error
                            $was_success = 0;
                            echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_failed_malformed_token'));
                        }
                    }
                }

                // Check, if user clicked on the undo reservation link in mail
                if (isset($_GET['undo_reservation']) && isset($_GET['usertoken']) && getvaluefromsettings($settings_array, 'reservation_undo_enabled') == 1) {
                    // Check, if is allowed to redu reservation by using the undo link
                    if (getvaluefromsettings($settings_array, 'reservation_undo_via_mail_link_enabled') == 1) {
                        // Sanitize strings
                        $post_usertoken_tmp = filter_string_polyfill($_GET['usertoken']);
                        $result_usertoken = get_username_dbsafe_from_reservations_by_usertoken($reservations, $post_usertoken_tmp);
                        if (!$result_usertoken == ''){
                            // User token was found, removing user from reservation
                            if (remove_reservation($conn, $result_usertoken)) {
                                echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_undo_success'));
                                $was_success = 1;
                            } else {
                                echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_undo_failed'));
                            }
                        }
                    } else {
                        echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_undo_disabled_by_admin'));
                    }
                }

                // Check if admin-mode enabled and remove link was used
                if (isset($_GET['delete']) && isset($_GET['delname']) && ($adminmode_enabled == 1 || $moderatormode_enabled == 1)) {
                    // Sanitize strings
                    $name = filter_string_polyfill($_GET['delname']);
                    $name_dbsafe = filter_string_dbsafe($name);

                    // Delete reservation entry from database without checking e-mail
                    if (remove_reservation($conn, $name_dbsafe)) {
                        echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_undo_success'));
                        $was_success = 1;
                    } else {
                        echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_undo_failed'));
                    }
                }

                // Check if approve items should be removed (by admin or moderator)
                if (isset($_GET['delete']) && isset($_GET['usertoken']) && ($adminmode_enabled == 1 || $moderatormode_enabled == 1)) {
                    // Sanitize strings
                    $usertoken_fromget = filter_string_polyfill($_GET['usertoken']);

                    // Delete approvement entry from database
                    if (remove_approvement_by_usertoken($conn, $usertoken_fromget)) {
                        echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_admin_approvement_remove_success'));
                        $was_success = 1;
                    } else {
                        echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_admin_approvement_remove_failed'));
                    }
                }

                // Check if user approve session should be accepted (by admin or moderator)
                if (isset($_GET['approve']) && isset($_GET['usertoken']) && ($adminmode_enabled == 1 || $moderatormode_enabled == 1)) {
                    // Sanitize strings
                    $usertoken_fromget = filter_string_polyfill($_GET['usertoken']);

                    // Add approvment item to reservation table and remove approvement item.
                    if (override_approvement_by_usertoken($conn, $approvements, $settings_array, $lang_array, $url_token_clean, $usertoken_fromget)) {
                        echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_admin_approvement_override_success'));
                        $was_success = 1;
                    } else {
                        echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_admin_approvement_override_failed'));
                    }
                }

                // Check if admin-mode enabled and all items need to me removed
                if (isset($_GET['cleanall']) && $adminmode_enabled == 1) {
                    if ($_GET['cleanall'] == 1) {
                        // Delete all rows in table
                        if (cleanallreservations($conn)) {
                            echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_reservation_clearall_success'));
                            $was_success = 1;
                        } else {
                            echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_reservation_clearall_failed'));
                        }
                    }
                }

                // Check if admin-mode enabled and all email validation records should cleared.
                if (isset($_GET['cleanvalidations']) && $adminmode_enabled == 1) {
                    if ($_GET['cleanvalidations'] == 1) {
                        // Delete all rows in table
                        if (cleanallapprovements($conn)) {
                            echo_notification_banner('success_info-box fade-out', getstrfromtranslation($lang_array, 'feedback_validation_clearall_success'));
                            $was_success = 1;
                        } else {
                            echo_notification_banner('fail_info-box', getstrfromtranslation($lang_array, 'feedback_validation_clearall_failed'));
                        }
                    }
                }

                // Check if settings changed
                if (isset($_GET['savesettings']) && $adminmode_enabled == 1) {
                    if ($_GET['savesettings'] == 1) {
                    saveadminsetting_all($conn);
                    echo '<p class="success_info-box fade-out">';
                    echo getstrfromtranslation($lang_array, 'admin_setting_change_success');
                    echo '</p>';
                    $was_success = 1;
                    }
                }

                if ($was_success == 1) {
                    // Handle refresh page after 3 seconds
                    // Check needed action
                    echo '<meta http-equiv="refresh" content="3; URL=';
                    echo "'";
                    if ($validation_step0_required == 1 && $validation_step1_required == 0) {
                        echo $url_token_clean . '#popup_modal_step1_validation';
                    }
                    if ($validation_step0_required == 0 && $validation_step1_required == 1) {
                        echo $url_token_clean . '#popup_modal_step2_validation';
                    }
                    if ($validation_step0_required == 0 && $validation_step1_required == 0) {
                        echo $url_clean;
                    }
                    echo "'";
                    echo ' /><br>';

                    $was_success = 0;
                }
            } else {
                // Token is not valid, no nothing
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    ?>
    <?php
        if ($token_valid == 1) {
            // Header and text
            echo '<img class="image_top" alt="' . getvaluefromsettings($settings_array, 'reservation_top_image_alt_description') . '" src="' . $top_image_comp . '">';
            echo '<h1>' . getvaluefromsettings($settings_array, 'reservation_name') . '</h1>';
            echo '<div class="info-box">' . getvaluefromsettings($settings_array, 'reservation_additional_info') . '<br>';
            if (getvaluefromsettings($settings_array, 'reservation_show_additional_info_link') == 1) {
                echo '<a class="a_additional_info" href="';
                echo getvaluefromsettings($settings_array, 'reservation_additional_info_link');
                echo '" target="_blank"';
                if (getvaluefromsettings($settings_array, 'reservation_additional_info_link_noreferrer') == 1){
                    echo ' rel="noreferrer"';
                }
                echo '>';
                echo getvaluefromsettings($settings_array, 'reservation_additional_info_link_text');
                echo '</a>';
            }
            echo '<br><br>';
            echo '<details tabindex="1"><summary>' . getstrfromtranslation($lang_array, 'summary_text') . '</summary>';
            echo getvaluefromsettings($settings_array, 'reservation_details');
            if (getvaluefromsettings($settings_array, 'reservation_show_details_info_link') == 1) {
                echo '<br><a class="a_additional_info" href="';
                echo getvaluefromsettings($settings_array, 'reservation_details_info_link');
                echo '" target="_blank"';
                if (getvaluefromsettings($settings_array, 'reservation_details_info_link_noreferrer') == 1){
                    echo ' rel="noreferrer"';
                }
                echo '>';
                echo getvaluefromsettings($settings_array, 'reservation_details_info_link_text');
                echo '</a>';
            }
            echo '</details></div>';

            // Check, if additional Pop-Up button should be visible
            if (getvaluefromsettings($settings_array, 'reservation_show_additional_popup_button') == 1) {
                echo '<a class="button_popup" id="button_popup" href="#popup_modal">';
                echo getvaluefromsettings($settings_array, 'reservation_additional_popup_button_caption');
                echo '</a>';
                echo_modal_popup('popup_modal', getvaluefromsettings($settings_array, 'reservation_additional_popup_button_caption'), getvaluefromsettings($settings_array, 'reservation_additional_popup_button_content'));
            }

            // Add GDPR button to show GDPR information (if activated)
            if (getvaluefromsettings($settings_array, 'reservation_show_gdpr_disclaimer_enabled') == 1) {
                echo '<a class="button_popup_gdpr_disclaimer" id="button_popup" href="#popup_modal_gdpr_disclaimer">';
                echo getstrfromtranslation($lang_array, 'button_gdpr_text');
                echo '</a>';
                echo_modal_popup('popup_modal_gdpr_disclaimer', getstrfromtranslation($lang_array, 'button_gdpr_text'), getvaluefromsettings($settings_array, 'reservation_gdpr_disclaimer_button_content'));
            }

            // Add email validation step 1 + 2 pop-up divs (if activated)
            if (getvaluefromsettings($settings_array, 'reservation_email_approvement_step1_enable') == 1) {
                echo_modal_popup('popup_modal_step1_validation', getvaluefromsettings($settings_array, 'reservation_email_approvement_step1_popup_header'), getvaluefromsettings($settings_array, 'reservation_email_approvement_step1_popup_text'));
            }
            if (getvaluefromsettings($settings_array, 'reservation_email_approvement_step2_enable') == 1) {
                echo_modal_popup('popup_modal_step2_validation', getvaluefromsettings($settings_array, 'reservation_email_approvement_step2_popup_header'), getvaluefromsettings($settings_array, 'reservation_email_approvement_step2_popup_text'));
            }

            // Registration form
            // Check, if fields should be filled out from GET
            $field_name = "";
            $field_mail = "";
            $field_user_accept_conditions = "";
            if (isset($_GET['name'])) {
                // Name was provided, sanitize string
                $field_name = filter_string_polyfill($_GET['name']);
            }
            if (isset($_GET['mail'])) {
                // Mail was provided, sanitize string
                $field_mail = filter_var($_GET['mail'],FILTER_SANITIZE_EMAIL);
            }
            if (isset($_GET['user_accept_conditions'])) {
                // User accept condition state was provided, sanitize string
                $field_user_accept_conditions = filter_boolstr($_GET['user_accept_conditions']);
            }
            echo '<h2>' . getstrfromtranslation($lang_array, 'title_reservation_form') . '</h2>';
            echo '<form method="POST" action="' . $url . '">';
            echo '<label for="name">' . getstrfromtranslation($lang_array, 'primary_textfield_name') . '</label>';
            echo '<input type="text" name="name" id="name" autocomplete="name" tabindex="2" value="' . $field_name . '" placeholder="' . getvaluefromsettings($settings_array, 'primary_textfield_name_placeholder_hint') . '" required><br>';
            echo '<label for="email">' . getstrfromtranslation($lang_array, 'primary_textfield_mail') . '</label>';
            echo '<input type="email" name="email" id="email" autocomplete="email" tabindex="3" value="' . $field_mail . '" placeholder="' . getvaluefromsettings($settings_array, 'primary_textfield_email_placeholder_hint');
            if (getvaluefromsettings($settings_array, 'reservation_email_required') == 1 && $adminmode_enabled == 0) {
                echo '" required><br>';
            } else {
                echo '"><br>';
            }
            if (getvaluefromsettings($settings_array, 'reservation_enabled') == 1 || $adminmode_enabled == 1) {
                // Reservation is enabled
                if (count($reservations) < $max_reservations || getvaluefromsettings($settings_array, 'reservation_max') == 0) {
                    // Add checkbox for user agreement
                    if (getvaluefromsettings($settings_array, 'reservation_show_checkbox_user_readconditions_enabled') == 1 && $adminmode_enabled == 0) {
                        echo '<label for="user_accept_conditions">' . getvaluefromsettings($settings_array, 'reservation_show_checkbox_user_readconditions_caption') . '</label>';
                        echo '<input type="checkbox" name="user_accept_conditions" id="user_accept_conditions" tabindex="4" value="1" required><br>';
                    }

                    // Places are left, show button to allow reservation
                    echo '<input type="submit" name="submit" tabindex="5" value="';
                    if (getvaluefromsettings($settings_array, 'reservation_email_approvement_step1_enable') == 1 && $adminmode_enabled == 0) {
                        echo getstrfromtranslation($lang_array, 'button_submit_reservation_with_approvement');
                    } else {
                        echo getstrfromtranslation($lang_array, 'button_submit_reservation');
                    }
                    echo '">';
                } else {
                    // Show notice that no one can register
                    echo '<div class="info-box">';
                    echo getstrfromtranslation($lang_array, 'feedback_reservation_limit');
                    echo '</div>';
                    if (getvaluefromsettings($settings_array, 'reservation_max_additional_info') != "empty") {
                        // Show custom notice
                        echo '<div class="info-box">';
                        echo getvaluefromsettings($settings_array, 'reservation_max_additional_info');
                        echo '</div>';
                    }
                }
            } else {
                // Reservation is not enabled (read-only-mode)
                echo '<div class="info-box forbidden">';
                echo getstrfromtranslation($lang_array, 'feedback_reservation_disabled');
                echo '</div>';
                if (getvaluefromsettings($settings_array, 'reservation_disabled_additional_info') != "empty") {
                    // Show custom notice
                    echo '<div class="info-box">';
                    echo getvaluefromsettings($settings_array, 'reservation_disabled_additional_info');
                    echo '</div>';
                }
            }

            if (getvaluefromsettings($settings_array, 'reservation_undo_enabled') == 1 || $adminmode_enabled == 1) {
                // Allow undo reservation
                if (getvaluefromsettings($settings_array, 'reservation_token_enabled') == 1) {
                    echo '<button type="submit" formaction="' .  $url_clean . '&delete=1" tabindex="5" class="button_undo">';
                    echo getstrfromtranslation($lang_array, 'button_remove_reservation');
                    echo '</button>';
                } else {
                    echo '<button type="submit" formaction="?delete=1" tabindex="6" class="button_undo">';
                    echo getstrfromtranslation($lang_array, 'button_remove_reservation');
                    echo '</button>';
                }
            } else {
                // Show notice that undo reservation is not allowed
                echo '<div class="info-box forbidden">';
                echo getstrfromtranslation($lang_array, 'feedback_undo_reservation_disabled');
                echo '</div>';
            }
            echo '</form>';
        } else {
            // Token is not valid, show custom info message
            echo_notification_banner('fail_info-box', getvaluefromsettings($settings_array, 'reservation_invalid_token_info'));
        }
    ?>
    <?php
        // Functions to echo user feedback (used at multiple parts)
        function echo_notification_banner(string $pclass, string $message) {
            echo '<p class="' . $pclass . '">';
            echo $message;
            echo '</p>';
        }

        function echo_modal_popup(string $id, string $header, string $content) {
            echo '<div id="' . $id . '" class="overlay">';
            echo '<div class="popup">';
            echo '<h1>' . $header . '</h1>';
            echo '<a class="close" href="#">&times;</a>';
            echo '<div class="content_popup">';
            echo $content;
            echo '</div></div></div>';
        }

        // Functions
        function preferred_language(array $available_languages, $http_accept_language) {

        $available_languages = array_flip($available_languages);

        $langs;
        preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~', strtolower($http_accept_language), $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            list($a, $b) = explode('-', $match[1]) + array('', '');
            $value = isset($match[2]) ? (float) $match[2] : 1.0;
            if(isset($available_languages[$match[1]])) {
                $langs[$match[1]] = $value;
                continue;
            }

            if(isset($available_languages[$a])) {
                $langs[$a] = $value - 0.1;
            }
        }
        arsort($langs);
        return $langs;
        }

        function getstrfromtranslation(array $lang_obj, string $strval) {
            try {
                foreach ($lang_obj as $lang_item) {
                    if (strtolower($lang_item['name']) == strtolower($strval)) {
                        return $lang_item['value'];
                    }
                }
                return "No translation found for " . $strval;

            } catch (\Throwable $th) {
                return "Error searching translation for " . $strval;
            } 
        }

        function getvaluefromsettings(array $settings_obj, string $strval) {
            try {
            foreach ($settings_obj as $settings_item) {
                if (strtolower($settings_item['name']) == strtolower($strval)) {
                    return $settings_item['value'];
                }
            }
    
            } catch (\Throwable $th) {
            return "";
            } 
        }

        function filter_string_polyfill(string $string): string {
            $str = preg_replace('/\x00|<[^>]*>?/', '', $string);
            return str_replace(["'", '"'], ['&#39;', '&#34;'], $str);
        }

        function filter_string_dbsafe(string $string): string {
            return filter_var(preg_replace("/\s/", "", $string),FILTER_SANITIZE_ENCODED,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK);
        }

        function filter_boolstr(string $string): int {
            return filter_var(trim($string),FILTER_SANITIZE_NUMBER_INT);
        }

        function saveadminsetting_all($conn) {
            // Update settings in database
            saveadminsetting($conn,"reservation_name");
            saveadminsetting($conn,"reservation_additional_info");
            saveadminsetting($conn,"reservation_show_additional_info_link");
            saveadminsetting($conn,"reservation_additional_info_link_text");
            saveadminsetting($conn,"reservation_additional_info_link");
            saveadminsetting($conn,"reservation_additional_info_link_noreferrer");
            saveadminsetting($conn,"reservation_details");
            saveadminsetting($conn,"reservation_show_details_info_link");
            saveadminsetting($conn,"reservation_details_info_link_text");
            saveadminsetting($conn,"reservation_details_info_link");
            saveadminsetting($conn,"reservation_details_info_link_noreferrer");
            saveadminsetting($conn,"reservation_show_additional_popup_button");
            saveadminsetting($conn,"reservation_additional_popup_button_caption");
            saveadminsetting($conn,"reservation_additional_popup_button_content");

            saveadminsetting($conn,"primary_textfield_name_placeholder_hint");
            saveadminsetting($conn,"primary_textfield_email_placeholder_hint");

            saveadminsetting($conn,"reservation_max_additional_info");
            saveadminsetting($conn,"reservation_no_data_additional_info");
            saveadminsetting($conn,"reservation_disabled_additional_info");
            saveadminsetting($conn,"reservation_invalid_token_info");

            saveadminsetting($conn,"reservation_show_additional_disclaimer_enabled");
            saveadminsetting($conn,"reservation_show_gdpr_disclaimer_enabled");
            saveadminsetting($conn,"reservation_gdpr_disclaimer_button_content");
            saveadminsetting($conn,"reservation_additional_disclaimer_title");
            saveadminsetting($conn,"reservation_show_project_credits");

            saveadminsetting($conn,"reservation_name_maxchar");
            saveadminsetting($conn,"reservation_name_minchar");
            saveadminsetting($conn,"reservation_max");
            saveadminsetting($conn,"reservation_enabled");
            saveadminsetting($conn,"reservation_undo_enabled");
            saveadminsetting($conn,"reservation_undo_via_mail_link_enabled");
            saveadminsetting($conn,"reservation_show_attendees_enabled");
            saveadminsetting($conn,"reservation_show_reservation_limit_enabled");
            saveadminsetting($conn,"reservation_show_checkbox_user_readconditions_enabled");
            saveadminsetting($conn,"reservation_show_checkbox_user_readconditions_caption");
            saveadminsetting($conn,"reservation_name_blacklist_enable");
            saveadminsetting($conn,"reservation_name_blacklist");
            saveadminsetting($conn,"reservation_name_blacklist_unicode_enable");
            saveadminsetting($conn,"reservation_name_blacklist_unicode_base64");
            saveadminsetting($conn,"reservation_name_whitelist_regex_enable");
            saveadminsetting($conn,"reservation_name_whitelist_regex");
            saveadminsetting($conn,"reservation_email_required");
            saveadminsetting($conn,"reservation_email_whitelist_enable");
            saveadminsetting($conn,"reservation_email_whitelist");
            saveadminsetting($conn,"reservation_email_whitelist_regex_enable");
            saveadminsetting($conn,"reservation_email_whitelist_regex");
            saveadminsetting($conn,"reservation_email_approvement_step1_enable");
            saveadminsetting($conn,"reservation_email_approvement_step1_mail_to");
            saveadminsetting($conn,"reservation_email_approvement_step1_mail_subject");
            saveadminsetting($conn,"reservation_email_approvement_step1_mail_body");
            saveadminsetting($conn,"reservation_email_approvement_step1_popup_header");
            saveadminsetting($conn,"reservation_email_approvement_step1_popup_text");
            saveadminsetting($conn,"reservation_email_approvement_step2_enable");
            saveadminsetting($conn,"reservation_email_approvement_step2_mail_to");
            saveadminsetting($conn,"reservation_email_approvement_step2_mail_subject");
            saveadminsetting($conn,"reservation_email_approvement_step2_mail_body");
            saveadminsetting($conn,"reservation_email_approvement_step2_popup_header");
            saveadminsetting($conn,"reservation_email_approvement_step2_popup_text");
            saveadminsetting($conn,"reservation_email_approvement_successful_notification_enable");
            saveadminsetting($conn,"reservation_email_approvement_successful_notification_mail_to");
            saveadminsetting($conn,"reservation_email_approvement_successful_notification_mail_subject");
            saveadminsetting($conn,"reservation_email_approvement_successful_notification_mail_body");

            saveadminsetting($conn,"reservation_token_enabled");
            saveadminsetting($conn,"reservation_token");
            saveadminsetting($conn,"reservation_allow_get_submit");

            saveadminsetting($conn,"moderator_presharedkey");
            saveadminsetting($conn,"admin_presharedkey");

            saveadminsetting($conn,"reservation_mail_smtp_server_hostname");
            saveadminsetting($conn,"reservation_mail_smtp_server_port");
            saveadminsetting($conn,"reservation_mail_smtp_from_address");
            saveadminsetting($conn,"reservation_mail_smtp_reply_to_address");
            saveadminsetting($conn,"reservation_mail_smtp_use_smtps");
            saveadminsetting($conn,"reservation_mail_smtp_encryption_mechanism");
            saveadminsetting($conn,"reservation_mail_smtp_use_authentification");
            saveadminsetting($conn,"reservation_mail_smtp_authentification_username");
            saveadminsetting($conn,"reservation_mail_smtp_authentification_password");
            saveadminsetting($conn,"reservation_mail_smtp_sourceip_ratelimit_header");
            saveadminsetting($conn,"reservation_mail_smtp_sourceip_ratelimit_enable");
            saveadminsetting($conn,"reservation_mail_smtp_sourceip_ratelimit");

            saveadminsetting($conn,"reservation_top_image");
            saveadminsetting($conn,"reservation_top_image_alt_description");
            saveadminsetting($conn,"reservation_page_background_image");
            saveadminsetting($conn,"reservation_page_favicon");
            saveadminsetting($conn,"reservation_font_color");
            saveadminsetting($conn,"reservation_page_main_font_color"); 
            saveadminsetting($conn,"reservation_attendees_font_color");
            saveadminsetting($conn,"reservation_appinfo_font_color");
            saveadminsetting($conn,"reservation_additional_disclaimer_font_color");
            saveadminsetting($conn,"reservation_additional_popup_button_font_color");
            saveadminsetting($conn,"reservation_additional_popup_button_background_color");
            saveadminsetting($conn,"reservation_submit_background_color");
            saveadminsetting($conn,"reservation_submit_color");
            saveadminsetting($conn,"reservation_undo_submit_color");
            saveadminsetting($conn,"reservation_undo_submit_background_color");
            saveadminsetting($conn,"reservation_additional_popup_button_content_headline_font_color");
            saveadminsetting($conn,"reservation_additional_popup_button_content_headline_font_shadow");
            saveadminsetting($conn,"reservation_additional_popup_button_content_font_color");
            saveadminsetting($conn,"reservation_additional_info_link_text_font_color");
            saveadminsetting($conn,"reservation_management_link_text_font_color");
            saveadminsetting($conn,"reservation_subtitles_font_color");
            saveadminsetting($conn,"reservation_attendees_background_color");
            saveadminsetting($conn,"reservation_background_color");
            saveadminsetting($conn,"reservation_page_main_font_shadow");
            saveadminsetting($conn,"reservation_page_background_blend_mode");
            saveadminsetting($conn,"reservation_page_background_backdrop_filter");
            saveadminsetting($conn,"reservation_form_label_background_color");
            saveadminsetting($conn,"reservation_subtitles_background_color");
            saveadminsetting($conn,"reservation_page_background_color");
            saveadminsetting($conn,"reservation_page_name_background_color");
            saveadminsetting($conn,"reservation_top_image_background_color");
            saveadminsetting($conn,"reservation_additional_popup_button_content_background_color");
            saveadminsetting($conn,"reservation_gdpr_disclaimer_button_font_color");
            saveadminsetting($conn,"reservation_gdpr_disclaimer_button_background_color");

            saveadminsetting($conn,"reservation_embed_js1_enabled");
            saveadminsetting($conn,"reservation_embed_js1_script");
            saveadminsetting($conn,"reservation_embed_div1_id");
            saveadminsetting($conn,"reservation_embed_js1_closed_tag_enabled");
            saveadminsetting($conn,"reservation_embed_js2_enabled");
            saveadminsetting($conn,"reservation_embed_js2_script");
            saveadminsetting($conn,"reservation_embed_content_wrapped_div1_enable");
            saveadminsetting($conn,"reservation_embed_content_wrapped_div1_class");
            saveadminsetting($conn,"reservation_embed_content_wrapped_div1_id");
            saveadminsetting($conn,"reservation_embed_canvas1_enable");
            saveadminsetting($conn,"reservation_embed_canvas1_id");
            saveadminsetting($conn,"reservation_embed_external_css_enable");
            saveadminsetting($conn,"reservation_embed_external_css_file");
        }

        function echo_admin_setting_all($lang_array, $settings_array, $top_image_dir, $background_Image_dir, $favicon_dir) {
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_info', 'textarea', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_additional_info_link', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_info_link_text', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_info_link', 'url', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_info_link_noreferrer', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_details', 'textarea', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_details_info_link', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_details_info_link_text', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_details_info_link', 'url', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_details_info_link_noreferrer', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_additional_popup_button', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_popup_button_caption', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_popup_button_content', 'textarea', 1, 16000);

            echo_admin_setting_field($lang_array, $settings_array, 'primary_textfield_name_placeholder_hint', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'primary_textfield_email_placeholder_hint', 'text', 1, 2048);

            echo_admin_setting_field($lang_array, $settings_array, 'reservation_max_additional_info', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_no_data_additional_info', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_disabled_additional_info', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_invalid_token_info', 'text', 1, 2048);

            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_additional_disclaimer_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_gdpr_disclaimer_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_gdpr_disclaimer_button_content', 'textarea', 1, 16000);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_disclaimer_title', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_project_credits', 'number', 0, 1);

            echo '<br><details tabindex="7">';
            echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_advanced') . '</summary><br>';
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_maxchar', 'number', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_minchar', 'number', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_max', 'number', 0, 4096);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_undo_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_undo_via_mail_link_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_attendees_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_reservation_limit_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_checkbox_user_readconditions_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_checkbox_user_readconditions_caption', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_blacklist_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_blacklist', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_blacklist_unicode_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_blacklist_unicode_base64', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_whitelist_regex_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_whitelist_regex', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_required', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_whitelist_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_whitelist', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_whitelist_regex_enable', 'number', 0, 1);
            echo '<br><details tabindex="8">';
            echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_advanced_user_validation') . '</summary><br>';
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step1_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step1_mail_to', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step1_mail_subject', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step1_mail_body', 'textarea', 1, 4096);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step1_popup_header', 'text', 1, 4096);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step1_popup_text', 'textarea', 1, 4096);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step2_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step2_mail_to', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step2_mail_subject', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step2_mail_body', 'textarea', 1, 4096);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step2_popup_header', 'text', 1, 4096);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_step2_popup_text', 'textarea', 1, 4096);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_successful_notification_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_successful_notification_mail_to', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_successful_notification_mail_subject', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_approvement_successful_notification_mail_body', 'textarea', 1, 4096);
            echo '</details><br>';

            echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_whitelist_regex', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_token_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_token', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_allow_get_submit', 'number', 0, 1);
            echo '</details><br>';

            echo '<br><details tabindex="9">';
            echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_presharedkeys') . '</summary><br>';
            echo_admin_setting_field($lang_array, $settings_array, 'moderator_presharedkey', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'admin_presharedkey', 'text', 1, 2048);
            echo '</details><br>';

            echo '<br><details tabindex="10">';
            echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_email') . '</summary><br>';
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_server_hostname', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_server_port', 'number', 1, 49151);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_from_address', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_reply_to_address', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_use_smtps', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_encryption_mechanism', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_use_authentification', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_authentification_username', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_authentification_password', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_sourceip_ratelimit_header', 'text', 1, 1024);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_sourceip_ratelimit_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_mail_smtp_sourceip_ratelimit', 'number', 0, 1024);
            echo '</details><br>';

            echo '<br><details tabindex="11">';
            echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_design') . '</summary><br>';
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_top_image', 'option', 1, 2048, $top_image_dir);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_top_image_alt_description', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_background_image', 'option', 1, 2048, $background_Image_dir);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_favicon', 'option', 1, 2048, $favicon_dir);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_main_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_attendees_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_appinfo_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_disclaimer_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_popup_button_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_popup_button_background_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_submit_background_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_submit_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_undo_submit_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_undo_submit_background_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_popup_button_content_headline_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_popup_button_content_headline_font_shadow', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_popup_button_content_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_info_link_text_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_management_link_text_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_subtitles_font_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_attendees_background_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_background_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_main_font_shadow', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_background_blend_mode', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_background_backdrop_filter', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_form_label_background_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_subtitles_background_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_background_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_name_background_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_top_image_background_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_popup_button_content_background_color', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_gdpr_disclaimer_button_font_color', 'color', 1, 255);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_gdpr_disclaimer_button_background_color', 'color', 1, 255);
            echo '</details><br>';

            echo '<br><details tabindex="12">';
            echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_external_scripts') . '</summary><br>';
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_js1_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_js1_script', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_div1_id', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_js1_closed_tag_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_js2_enabled', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_js2_script', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_content_wrapped_div1_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_content_wrapped_div1_class', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_content_wrapped_div1_id', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_canvas1_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_canvas1_id', 'text', 1, 2048);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_external_css_enable', 'number', 0, 1);
            echo_admin_setting_field($lang_array, $settings_array, 'reservation_embed_external_css_file', 'text', 1, 2048);
            echo '</details><br>';
        }

        function checkifmailisvalid(string $input_name, int $email_required, int $whitelist_enabled, string $whitelist, int $whitelist_regex_enabled, string $whitelist_regex) {
            $result_email_required = 0;
            $result_whitelist = 0;
            $result_whitlist_regex = 0;

            if ($email_required == 1) {
                if ($input_name != "" && filter_var($input_name, FILTER_VALIDATE_EMAIL)) {
                    $result_email_required = 1;
                }
            } else {
                $result_email_required = 1;
            }

            if ($whitelist_enabled == 1) {
                $whitelist_plain = explode(",", $restrictblacklist_plain);
                foreach ($whitelist_plain as $whitelist_plain_item) {
                    if (str_contains(strtolower($input_name),strtolower($whitelist_plain_item)) == true) {
                        $result_whitelist = 1;
                    }
                }
            } else {
                $result_whitelist = 1;
            }

            if ($whitelist_regex_enabled == 0) {
                $result_whitlist_regex = 1;
            } else {
                if (preg_match($whitelist_regex, $input_name, $match_noused)) {
                    $result_whitlist_regex = 1;
                }
            }

            if ($result_email_required == 1 && $result_whitelist == 1 && $result_whitlist_regex == 1) {
                return 1;
            } else {
                return 0;
            }
        }

        function checkifnameisvalid(string $input_name, int $maxlenght, int $minlenght, int $allowunicode, int $restrictblacklist_plain_enabled, string $restrictblacklist_plain, int $restrictblacklist_base64_enabled, string $restrictblacklist_base64, int $whitelist_regex_enabled, string $whitelist_regex) {
            $result_min = 0;
            $result_max = 0;
            $result_uni = 0;
            $result_blocklist_plain = 0;
            $result_blocklist_base64 = 0;
            $result_whitlist_regex = 0;

            if ($minlenght == 0) {
                $result_min = 1;
            } else {
                if (strlen($input_name) >= $minlenght) {
                    $result_min = 1;
                }
            }
            if ($maxlenght == 0) {
                $result_max = 1;
            } else {
                if (strlen($input_name) < $maxlenght) {
                    $result_max = 1;
                }
            }
            if ($allowunicode == 1) {
                $result_uni = 1;
            } else {
                $name_cleaned = filter_string_polyfill($input_name);

                if ($name_cleaned == $input_name) {
                    $$result_uni = 1;
                }
            }

            if ($restrictblacklist_plain_enabled == 0) {
                $result_blocklist_plain = 1;
            } else {
                $blocklist_plain_listed = 0;
                $restrictblacklist_plain = explode(",", $restrictblacklist_plain);
                foreach ($restrictblacklist_plain as $restrictblacklist_plain_item) {
                    if (str_contains(strtolower($input_name),strtolower($restrictblacklist_plain_item)) == true) {
                        $blocklist_plain_listed = 1;
                    }
                }
                if ($blocklist_plain_listed == 0) {
                    $result_blocklist_plain = 1;
                }
            }

            if ($restrictblacklist_base64_enabled == 0) {
                $result_blocklist_base64 = 1;
            } else {
                $blocklist_base64_listed = 0;
                $restrictblacklist_base64 = explode(",", $restrictblacklist_base64);
                foreach ($restrictblacklist_base64 as $restrictblacklist_base64_item) {
                    $restrictblacklist_base64_item = base64_decode($restrictblacklist_base64_item);
                    if (str_contains($input_name,$restrictblacklist_base64_item) == true) {
                        $blocklist_base64_listed = 1;
                    }
                }
                if ($blocklist_base64_listed == 0) {
                    $result_blocklist_base64 = 1;
                }
            }

            if ($whitelist_regex_enabled == 0) {
                $result_whitlist_regex = 1;
            } else {
                if (preg_match($whitelist_regex, $input_name, $match_noused)) {
                    $result_whitlist_regex = 1;
                }
            }

            if ($result_min == 1 && $result_max == 1 && $result_uni == 1 && $result_blocklist_plain == 1 && $result_blocklist_base64 == 1 && $result_whitlist_regex == 1) {
                return 1;
            } else {
                return 0;
            }
        }

        function array_contains(array $obj1, string $obj1selector, string $checkstr) {
            if (count($obj1) > 0) {
                foreach ($obj1 as $testobj) {
                    if (strtolower($testobj[$obj1selector]) == strtolower($checkstr)) {
                        return true;
                    }
                }
            } else {
                return false;
            }
            return false;
        }

        function approvement_checkstr(string $token) {
            if (mb_strlen($token) == 29) {
                if (str_contains($token, '_') && str_contains($token, '.')) {
                    // Seems to be a valid token
                    return true;
                }
            }
            return false;
        }

        function approvement_checkratelimit($db_connection, array $approvements_obj, string $remote_ip, string $stepvalip, string $stepvalipcount, int $max_rate) {
            $remoteip_found = 0;
            $ratelimit_reached = 0;
            $currcount = 0;
            if (count($approvements_obj) > 0) {
                foreach ($approvements_obj as $approvement) {
                    if ($approvement[$stepvalip] == $remote_ip) {
                        $currcount = $approvement[$stepvalipcount];
                        if (($currcount + 1) > $max_rate) {
                            $ratelimit_reached = 1;
                        }
                        $remoteip_found = 1;
                    }
                }
                if ($remoteip_found == 1) {
                    try {
                        $stmt = $db_connection->prepare("UPDATE approvements SET " . $stepvalipcount . "=:" . $stepvalipcount . ", " . $stepvalipcount . "=:" . $stepvalipcount . " WHERE " . $stepvalip . "=:" . $stepvalip);
                        $currcount = $currcount + 1;
                        $stmt->bindParam(':' . $stepvalip, $remote_ip);
                        $stmt->bindParam(':' . $stepvalipcount, $currcount);
                        $stmt->execute();
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                        return false;
                    }
                } 
                if ($ratelimit_reached == 1) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }

        function approvement_checktoken(array $approvements_obj, int $step, string $token) {
            $found_token = 0;
            if (count($approvements_obj) > 0) {
                switch ($step) {
                    case 0:
                        foreach ($approvements_obj as $approvement) {
                            if (($approvement['step1token'] == $token) && ($approvement['step1validated'] == '0')) {
                                $found_token = 1;
                            }
                            if (($approvement['step2token'] == $token) && ($approvement['step2validated'] == '0') && ($approvement['step1validated'] == '1')) {
                                $found_token = 1;
                            }
                        }
                        break;
                    case 1:
                        foreach ($approvements_obj as $approvement) {
                            if (($approvement['step1token'] == $token) && ($approvement['step1validated'] == '0')) {
                                $found_token = 1;
                            }
                        }
                        break;
                    case 2:
                        foreach ($approvements_obj as $approvement) {
                            if (($approvement['step2token'] == $token) && ($approvement['step2validated'] == '0') && ($approvement['step1validated'] == '1')) {
                                $found_token = 1;
                            }
                        }
                        break;
                }
            }
            if ($found_token == 1) {
                return true;
            } else {
                return false;
            }
        }

        function approvement_getuserinfo(array $approvements_obj, string $token) {
            if (count($approvements_obj) > 0) {
                foreach ($approvements_obj as $approvement) {
                    if (($approvement['step1token'] == $token) || ($approvement['step2token'] == $token)) {
                        return $approvement;
                    }
                }    
            } else {
                return false;
            }
        }

        function approvement_step0($db_connection, string $name_dbsafe, string $name_base64, string $email, string $step1token, string $step2token, string $usertoken, string $step1mail, string $step2mail, string $step1start, string $step1_sourceip) {
            try {
                $stmt = $db_connection->prepare("INSERT INTO approvements (name, name_base64, email, step1token, step2token, usertoken, step1mail, step2mail, step1start, step1_sourceip, step1validated, step2validated) VALUES (:name, :name_base64, :email, :step1token, :step2token, :usertoken, :step1mail, :step2mail, :step1start, :step1_sourceip, :step1validated, :step2validated)");
                $stmt->bindParam(':name', $name_dbsafe);
                $stmt->bindParam(':name_base64', $name_base64);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':step1token', $step1token);
                $stmt->bindParam(':step2token', $step2token);
                $stmt->bindParam(':usertoken', $usertoken);
                $stmt->bindParam(':step1mail', $step1mail);
                $stmt->bindParam(':step2mail', $step2mail);
                $stmt->bindParam(':step1start', $step1start);
                $stmt->bindParam(':step1_sourceip', $step1_sourceip);

                $step1validated = 0;
                $step2validated = 0;

                $stmt->bindParam(':step1validated', $step1validated);
                $stmt->bindParam(':step2validated', $step2validated);
                $stmt->execute();

                return true;
            } catch (PDOException $e) {
                echo $e->getMessage();
                return false;
            }
        }

        function approvement_step1_updatedb($db_connection, string $step1token, string $step2start) {
            try {
                $stmt = $db_connection->prepare("UPDATE approvements SET step2start=:step2start, step1validated=:step1validated WHERE step1token=:step1token LIMIT 1");
                $stmt->bindParam(':step1token', $step1token);
                $stmt->bindParam(':step2start', $step2start);

                $step1success = 1;

                $stmt->bindParam(':step1validated', $step1success);
                $query_execute = $stmt->execute();
                return $query_execute;

                return true;
            } catch (PDOException $e) {
                echo $e->getMessage();
                return false;
            }
        }

        function approvement_step2_updatedb($db_connection, string $step1token) {
            try {
                $stmt = $db_connection->prepare("UPDATE approvements SET step2validated=:step2validated WHERE step1token=:step1token LIMIT 1");
                $stmt->bindParam(':step1token', $step1token);

                $step2success = 1;

                $stmt->bindParam(':step2validated', $step2success);
                $query_execute = $stmt->execute();
                return $query_execute;

                return true;
            } catch (PDOException $e) {
                echo $e->getMessage();
                return false;
            }
        }

        function add_reservation_to_db($db_connection, string $name_dbsafe, string $name_base64, string $email, string $date_added, string $usertoken) {
            try {
                $stmt = $db_connection->prepare("INSERT INTO reservations (name, name_base64, usertoken, email, date_added) VALUES (:name, :name_base64, :usertoken, :email, :date_added)");
                $stmt->bindParam(':name', $name_dbsafe);
                $stmt->bindParam(':name_base64', $name_base64);
                $stmt->bindParam(':usertoken', $usertoken);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':date_added', $date_added);
                $stmt->execute();

                return true;
            } catch (PDOException $e) {
                return false;
            }
        }

        function remove_reservation($db_connection, string $name_dbsafe) {
            try {
                $stmt = $db_connection->prepare("DELETE FROM reservations WHERE name = :name");
                $stmt->bindParam(':name', $name_dbsafe);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return true;
                } else {
                    return false;
                }
            } catch (PDOException $e) {
                return false;
            }
        }

        function remove_approvement_step1($db_connection, string $step1token) {
            try {
                $stmt = $db_connection->prepare("DELETE FROM approvements WHERE step1token = :step1token");
                $stmt->bindParam(':step1token', $step1token);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return true;
                } else {
                    return false;
                }
            } catch (PDOException $e) {
                return false;
            }
        }

        function remove_approvement_step2($db_connection, string $step2token) {
            try {
                $stmt = $db_connection->prepare("DELETE FROM approvements WHERE step2token = :step2token");
                $stmt->bindParam(':step2token', $step2token);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return true;
                } else {
                    return false;
                }
            } catch (PDOException $e) {
                return false;
            }
        }

        function remove_approvement_by_usertoken($db_connection, string $usertoken) {
            try {
                $stmt = $db_connection->prepare("DELETE FROM approvements WHERE usertoken = :usertoken");
                $stmt->bindParam(':usertoken', $usertoken);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return true;
                } else {
                    return false;
                }
            } catch (PDOException $e) {
                return false;
            }
        }

        function override_approvement_by_usertoken($db_connection, $approvements_obj, $settings_array, $lang_array, $url_token_clean, string $usertoken) {
            $approvements_tmp = NULL;
            foreach ($approvements_obj as $approvement) {
                if ($approvement['usertoken'] == $usertoken) {
                    $approvements_tmp = $approvement;
                }
            }
            if (!$approvements_tmp == NULL) {
                $email = $approvements_tmp['email'];
                $name_dbsafe = $approvements_tmp['name'];
                $name_base64 = $approvements_tmp['name_base64'];
                $usertoken_tmp = $approvements_tmp['usertoken'];
                $step1mail_tmp = $approvements_tmp['step1mail'];
                $step2mail_tmp = $approvements_tmp['step2mail'];
                $step1_token_tmp = $approvements_tmp['step1token'];
                $step2_token_tmp = $approvements_tmp['step2token'];
                $date_added = date('Y-m-d H:i:s');
                $remoteip_tmp = 'Administrator';

                add_reservation_to_db($db_connection, $name_dbsafe, $name_base64, $email, $date_added, $usertoken);
                remove_approvement_by_usertoken($db_connection, $usertoken);

                // Send notification if enabled
                if (getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_enable') == 1) {
                    $undo_reservation_url_tmp = $url_token_clean . '&usertoken=' . $usertoken_tmp . '&undo_reservation=1';
                    $undo_reservation_url_html_tmp = '<a href="' . $undo_reservation_url_tmp . '" title="' . getstrfromtranslation($lang_array, 'reservation_email_undo_reservation_href_link_title') . '">' . getstrfromtranslation($lang_array, 'reservation_email_undo_reservation_href_link_title') . '</a>';
                    $mail_notify_tmp = replace_vars_validation(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_to'), $name_dbsafe, $email);
                    $mailbody_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_body'), $name_dbsafe, $email, $step2_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), '', '', $undo_reservation_url_tmp, $undo_reservation_url_html_tmp, $remoteip_tmp);
                    $mailsubject_tmp = replace_vars_email_body(getvaluefromsettings($settings_array, 'reservation_email_approvement_successful_notification_mail_subject'), $name_dbsafe, $email, $step2_token_tmp, getvaluefromsettings($settings_array, 'reservation_name'), '', '', $undo_reservation_url_tmp, $undo_reservation_url_html_tmp, $remoteip_tmp);
                    if (!send_mail_by_PHPMailer(getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_hostname'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_username'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_authentification_password'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_server_port'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_use_authentification'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_encryption_mechanism'), $mail_notify_tmp, getvaluefromsettings($settings_array, 'reservation_mail_smtp_from_address'), getvaluefromsettings($settings_array, 'reservation_mail_smtp_reply_to_address'), $mailsubject_tmp, $mailbody_tmp)) {
                        return false;
                    }
                }

                return true;
            } else {
                return false;
            }
        }

        function remove_reservation_mail_and_name($db_connection, string $name_dbsafe, string $email) {
            try {
                $stmt = $db_connection->prepare("DELETE FROM reservations WHERE name = :name AND email = :email");
                $stmt->bindParam(':name', $name_dbsafe);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    return true;
                } else {
                    return false;
                }
            } catch (PDOException $e) {
                return false;
            }
        }

        function replace_vars_validation(string $sourcestr, string $name_dbsafe, string $email) {
            $sourcestr = str_ireplace("%UserMail%", $email, $sourcestr);
            $sourcestr = str_ireplace("%UserName%", $name_dbsafe, $sourcestr);

            return $sourcestr;
        }

        function replace_vars_email_body(string $sourcestr, string $name_dbsafe, string $email, string $validation_token, string $site_title, string $validation_url, string $validation_url_html, string $undo_reservation_url, string $undo_reservation_url_html, string $remoteip) {
            $sourcestr = str_ireplace("%UserMail%", $email, $sourcestr);
            $sourcestr = str_ireplace("%UserName%", $name_dbsafe, $sourcestr);
            $sourcestr = str_ireplace("%ValidationToken%", $validation_token, $sourcestr);
            $sourcestr = str_ireplace("%SiteTitle%", $site_title, $sourcestr);
            $sourcestr = str_ireplace("%ValidationURL%", $validation_url, $sourcestr);
            $sourcestr = str_ireplace("%ValidationURL_HTML%", $validation_url_html, $sourcestr);
            $sourcestr = str_ireplace("%UndoReservationURL%", $undo_reservation_url, $sourcestr);
            $sourcestr = str_ireplace("%UndoReservationURL_HTML%", $undo_reservation_url_html, $sourcestr);

            $sourcestr = str_ireplace("%RemoteIP%", $remoteip, $sourcestr);

            return $sourcestr;
        }

        function sanitize_admin_setting(string $admin_setting_input): string {
            $admin_setting_input = htmlspecialchars($admin_setting_input, ENT_COMPAT | ENT_SUBSTITUTE);
            $admin_setting_input = str_ireplace("&lt;br&gt;", "<br>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;p&gt;", "<p>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;/p&gt;", "</p>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;h1&gt;", "<h1>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;/h1&gt;", "</h1>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;h2&gt;", "<h2>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;/h2&gt;", "</h2>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;h3&gt;", "<h3>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;/h3&gt;", "</h3>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;b&gt;", "<b>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;/b&gt;", "</b>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;small&gt;", "<small>", $admin_setting_input);
            $admin_setting_input = str_ireplace("&lt;/small&gt;", "</small>", $admin_setting_input);

            return $admin_setting_input;
        }

        function saveadminsetting($db_connection, string $admin_setting_tag) {
            try {
                if (!isset($_POST['admin_' . $admin_setting_tag])) {
                    return false;
                }
                if ($_POST['admin_' . $admin_setting_tag] == '') {
                    return false;
                }
                $tmp_admin_setting_value = sanitize_admin_setting($_POST['admin_' . $admin_setting_tag]);
                $stmt = $db_connection->prepare("UPDATE settings SET name=:admin_setting_tag, value=:admin_setting_value WHERE name=:admin_setting_tag LIMIT 1");
                $stmt->bindParam(':admin_setting_tag', $admin_setting_tag);
                $stmt->bindParam(':admin_setting_value', $tmp_admin_setting_value);
                $query_execute = $stmt->execute();
                return $query_execute;
            } catch (PDOException $e) {
                return false;
            }
        }

        function cleanallreservations($db_connection) {
            try {
                $stmt = $db_connection->prepare("TRUNCATE TABLE reservations");
                $stmt->execute();
                return true;
            } catch (PDOException $e) {
                return false;
            }
        }

        function cleanallapprovements($db_connection) {
            try {
                $stmt = $db_connection->prepare("TRUNCATE TABLE approvements");
                $stmt->execute();
                return true;
            } catch (PDOException $e) {
                return false;
            }
        }

        function array_csv_download($array, $filename = "export.csv", $delimiter=";") {
            header('Content-Type: application/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '";');
            // clean output buffer
            ob_end_clean();
            $handle = fopen( 'php://output', 'w' );
            // use keys as column titles
            fputcsv( $handle, array_keys( $array['0'] ), $delimiter );
            foreach ( $array as $value ) {
                fputcsv( $handle, $value, $delimiter );
            }
            fclose( $handle );
            // flush buffer
            ob_flush();
            // use exit to get rid of unexpected output afterward
            exit();
        }

        function send_mail_by_PHPMailer($smtp_server, $smtp_server_auth_username, $smtp_server_auth_password, $smtp_server_port, $smtp_server_use_auth, $smtp_server_encryption_mechanism, $mail_to, $mail_from, $mail_reply_to, $mail_subject, $mail_message){
            // SEND MAIL by PHP MAILER
            $mail = new PHPMailer();
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP(); // Use SMTP protocol
            $mail->Host = $smtp_server; // Specify  SMTP server
            if ($smtp_server_use_auth == 1) {
                $mail->SMTPAuth = true; // Auth. SMTP
                $mail->Username = $smtp_server_auth_username; // Mail who send by PHPMailer
                $mail->Password = $smtp_server_auth_password; // your pass mail box
            } else {
                $mail->SMTPAuth = false; // No authentification
            }
            if ($smtp_server_encryption_mechanism == 1) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Accept SSL
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Accept STARTTLS
            }
            $mail->Port = $smtp_server_port; // port of your out server
            $mail->setFrom($mail_from); // Mail to send at
            $mail->addAddress($mail_to); // Add sender
            $mail->addReplyTo($mail_reply_to); // Adress to reply
            $mail->isHTML(true); // use HTML message
            $mail->Subject = $mail_subject;
            $mail->Body = $mail_message;
  
            // SEND
            if( !$mail->send() ){
                // render error if it is
                $tab = array('error' => 'Mailer Error: '.$mail->ErrorInfo );
                echo json_encode($tab);
                exit;
            }
            else{
                // return true if message is send
                return true;
            }
        }

        function get_username_dbsafe_from_reservations_by_usertoken(array $reservations_array, string $find_usertoken) {
            foreach ($reservations_array as $reservation) {
                if($reservation['usertoken'] == $find_usertoken){
                    // Found token, return dbsafe username
                    return $reservation['name'];
                }
            }
            // If function can not find specified usertoken, the token is not valid or validation was not successful.
            return '';
        }

        function echo_admin_setting_field(array $lang_obj, array $settings_obj, string $settingstag, string $input_type, int $mininput, int $maxinput, $list_dirname = NULL) {
            echo '<label for="admin_' . $settingstag . '">' . getstrfromtranslation($lang_obj, 'admin_setting_' . $settingstag) . '</label>';
            switch ($input_type) {
                case "text":
                    echo '<input type="' . $input_type .'" name="admin_' . $settingstag . '" id="admin_' . $settingstag . '" maxlength="' . $maxinput . '" minlength="' . $mininput . '" value="' . getvaluefromsettings($settings_obj, $settingstag) . '">';
                    break;
                case "number":
                    echo '<input type="' . $input_type .'" name="admin_' . $settingstag . '" id="admin_' . $settingstag . '" min="' . $mininput . '" max="' .  $maxinput . '" value="' . getvaluefromsettings($settings_obj, $settingstag) . '">';
                    break;
                case "url":
                    echo '<input type="' . $input_type .'" name="admin_' . $settingstag . '" id="admin_' . $settingstag . '" maxlength="' . $maxinput . '" minlength="' . $mininput . '" size=40 value="' . getvaluefromsettings($settings_obj, $settingstag) . '">';
                    break;
                case "color":
                    echo '<input type="' . $input_type .'" name="admin_' . $settingstag . '" id="admin_' . $settingstag . '" value="' . getvaluefromsettings($settings_obj, $settingstag) . '">';
                    break;
                case "option":
                    echo '<input type="text" list="list_' . $settingstag . '" name="admin_' . $settingstag . '" id="admin_' . $settingstag . '" maxlength="' . $maxinput . '" minlength="' . $mininput . '" placeholder="' . getvaluefromsettings($settings_obj, $settingstag) . '">';
                    echo '<datalist id="list_' . $settingstag . '">';
                    if (!$list_dirname == NULL) {
                        // Enumerate files in given dir
                        $files = glob($list_dirname . '/*');
                        foreach ($files as $file) {
                            $relativefilename = pathinfo($file, PATHINFO_BASENAME);
                            echo '<option value="' . $relativefilename . '">' . $relativefilename . '</option>'; 
                        }
                        echo '</datalist>';
                    }
                    break;
                case "textarea":
                    echo '<textarea name="admin_' . $settingstag . '" rows="3" cols="50">';
                    echo getvaluefromsettings($settings_obj, $settingstag);
                    echo '</textarea>';
                    break;
            }
            echo '<br>';
        }

        //Show attendees list
            if ($token_valid == 1) {
                echo '<h2>' . getstrfromtranslation($lang_array, 'title_attendees_form') . '</h2>';
                echo '<div class="attendees_div">';
                try {
                    if ($adminmode_enabled == 1 || $moderatormode_enabled == 1) {
                        echo '<table class="attendees_div"><tr><th>' . getstrfromtranslation($lang_array, 'admin_setting_table_header_registered') . '</th><th>' . getstrfromtranslation($lang_array, 'admin_setting_table_header_mail') . '</th><th>' . getstrfromtranslation($lang_array, 'admin_setting_table_header_name') . '</th><th>' . getstrfromtranslation($lang_array, 'admin_setting_table_header_actions') . '</th></tr>';
                        foreach ($reservations as $reservation) {
                            echo "<tr><td>";
                            echo $reservation['date_added'];
                            echo '</td><td>';
                            echo $reservation['email'];
                            echo '</td><td>';
                            echo base64_decode($reservation['name_base64']);
                            echo '</td><td>';
                            echo '<a href="';
                            echo $url;
                            echo '&delete=1&delname=';
                            echo $reservation['name'];
                            echo '"> ';
                            echo getstrfromtranslation($lang_array, 'admin_remove_item_text');
                            echo '</a>';
                            echo "</tr>";
                        }
                        echo '</table>';

                        // Show download attendees list download button
                        echo '<form method="POST" action="' . $url_clean . '&download_attendees=1">';
                        echo '<br><button type="submit" formaction="' . $url_clean . '&download_attendees=1" class="button_undo">';
                        echo getstrfromtranslation($lang_array, 'admin_button_download_attendees_list') . '</button>';
                        if ($moderatormode_enabled == 1) {
                            // If moderation mode is active, show logout button
                            echo '<button type="submit" formaction="' . $url_token_clean . '" class="button_undo">';
                            echo getstrfromtranslation($lang_array, 'admin_button_logout') . '</button>';
                        }
                        echo '</form>';
                    } else {
                        if (getvaluefromsettings($settings_array, 'reservation_show_attendees_enabled') == 1) {
                            if (count($reservations) > 0) {
                                echo "<ul>";
                                foreach ($reservations as $reservation) {
                                    echo "<li>";
                                    echo base64_decode($reservation['name_base64']);
                                    echo "</li>";
                                }
                                echo "</ul>";
                            } else {
                                echo '<div class="info-box">';
                                echo getstrfromtranslation($lang_array, 'no_reservation_found');
                                echo '</div>';
                                if (getvaluefromsettings($settings_array, 'reservation_no_data_additional_info') != "") {
                                    // Show custom notice
                                    echo '<div class="info-box">';
                                    echo getvaluefromsettings($settings_array, 'reservation_no_data_additional_info');
                                    echo '</div>';
                                }
                            }
                        }
                    }
                } catch (PDOException $e) {
                    echo getstrfromtranslation($lang_array, 'internal_error') . $e->getMessage();
                }
                echo '</div>';

                if (getvaluefromsettings($settings_array, 'reservation_show_reservation_limit_enabled') == 1) {
                    if (getvaluefromsettings($settings_array, 'reservation_max') != 0) {
                    echo '<div class="info-box">';
                    echo count($reservations);
                    echo ' ';
                    echo getstrfromtranslation($lang_array, 'reservation_counter_part1');
                    echo ' ';
                    echo getvaluefromsettings($settings_array, 'reservation_max');
                    echo ' ';
                    echo getstrfromtranslation($lang_array, 'reservation_counter_part2');
                    echo '</div>';
                    }
                }

                // Show open user validation sessions
                if (($adminmode_enabled == 1 || $moderatormode_enabled == 1) && count($approvements) > 0) {
                    echo '<div class="info-box">';
                    echo '<details tabindex="13">';
                    echo '<summary>' . getstrfromtranslation($lang_array, 'title_validation_sessions_form') . '</summary>';
                    echo '<div class="attendees_div">';
                    echo '<table class="attendees_div"><tr><th>' . getstrfromtranslation($lang_array, 'admin_setting_table_header_validation_start') . '</th><th>' . getstrfromtranslation($lang_array, 'admin_setting_table_header_mail') . '</th><th>' . getstrfromtranslation($lang_array, 'admin_setting_table_header_name') . '</th><th>' . getstrfromtranslation($lang_array, 'admin_setting_table_header_actions') . '</th></tr>';
                    foreach ($approvements as $approvement_session) {
                        echo "<tr><td>";
                        echo $approvement_session['step1start'];
                        echo '</td><td>';
                        echo $approvement_session['email'];
                        echo '</td><td>';
                        echo base64_decode($approvement_session['name_base64']);
                        echo '</td><td>';
                        echo '<a href="';
                        echo $url;
                        echo '&delete=1&usertoken=';
                        echo $approvement_session['usertoken'];
                        echo '"> ';
                        echo getstrfromtranslation($lang_array, 'admin_remove_item_text');
                        echo '</a>';
                        echo '<a href="';
                        echo $url;
                        echo '&approve=1&usertoken=';
                        echo $approvement_session['usertoken'];
                        echo '"> ';
                        echo getstrfromtranslation($lang_array, 'admin_approve_override_item_text');
                        echo '</a>';
                        echo "</tr>";
                    }
                    echo '</table></details></div>';
                }

                if (getvaluefromsettings($settings_array, 'reservation_show_additional_disclaimer_enabled') == 1) {
                    // Show custom notice
                    echo '<div class="additional_disclaimer-div">';
                    echo '<details tabindex="5">';
                    echo '<summary>';
                    echo getvaluefromsettings($settings_array, 'reservation_additional_disclaimer_title');
                    echo '</summary>';
                    echo getvaluefromsettings($settings_array, 'reservation_additional_disclaimer');
                    echo '</details>';
                    echo '</div>';
                }
            }

            // Close connection
            $conn = null;

            if ($adminmode_enabled == 1) {
                // Show settings form
                echo '<br><div class="info-box">';
                echo '<details tabindex="6">';
                echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_text') . '</summary><br>';
                echo getstrfromtranslation($lang_array, 'admin_setting_general_information') . '<br>';
                echo '<form method="POST" action="' . $url_clean . '&savesettings=1">';
                
                echo_admin_setting_all($lang_array, $settings_array, $top_image_dir, $background_Image_dir, $favicon_dir);

                echo '<br><details tabindex="13">';
                echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_danger_zone') . '</summary><br>';
                echo '<a class="button_remove_reservation" id="remove_reservations" href="' . $url_clean . '&cleanall=1"><b>';
                echo getstrfromtranslation($lang_array, 'admin_button_remove_all_reservations') . '</b>';
                echo '</a>';
                echo '<a class="button_remove_reservation" id="remove_all_validations" href="' . $url_clean . '&cleanvalidations=1">';
                echo getstrfromtranslation($lang_array, 'admin_button_remove_all_validations');
                echo '</a>';

                echo '</details><br>';
                echo '</button><input type="submit" name="submit_settings" tabindex="30" value="' . getstrfromtranslation($lang_array, 'admin_setting_submit_button_text') . '">';
                echo '<button type="submit" formaction="' . $url_token_clean . '" tabindex="31" class="button_undo">';
                echo getstrfromtranslation($lang_array, 'admin_button_logout');
                echo '</form></details></div>';
            }

            echo '</div>';

            // If additional wrapped div was inserted, close div
            if (getvaluefromsettings($settings_array, 'reservation_embed_content_wrapped_div1_enable') == 1){
                echo '</div>';
            }

            // If custom javascript code was embedded, close div
            if (getvaluefromsettings($settings_array, 'reservation_embed_js1_enabled') == 1 && getvaluefromsettings($settings_array, 'reservation_embed_js1_closed_tag_enabled') == 0){
                echo '</div>';
            }

            // Insert project credits if enabled
            if (getvaluefromsettings($settings_array, 'reservation_show_project_credits') == 1){
                echo '<div class="appinfo-div">';
                echo getstrfromtranslation($lang_array, 'bottom_project_disclaimer');
                echo '<br><a href="https://github.com/MichaelKirgus/PHPEasyReservation" target="_blank">';
                echo getstrfromtranslation($lang_array, 'bottom_project_link');
                echo '</a><br>' . $app_version . '</div>';
            }
        ?>
</body>
</html>