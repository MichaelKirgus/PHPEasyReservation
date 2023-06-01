<?php
    // Global settings
    $app_version = "1.0.0";
    $http_schema = "https";
    $base_url = "FQDN with dir path/";
    $available_languages = array("en", "de");
    // $static_lang = "de";

    // Etablish connection to database
    // DO NOT USE root mysql user!
    $dbhost = 'Database server IP or host';
    $dbname = 'event';
    $dbuser = 'Database user for accessing database';
    $dbpass = 'Database password for accessing database';

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

        // Alle Reservierungen aus der Datenbank abrufen
        $stmt = $conn->query("SELECT * FROM reservations");
        $reservations = $stmt->fetchAll();

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
                echo getvaluefromsettings($settings_array, 'reservation_page_favicon');
                echo '">';
            }
        } else {
            echo '<title>' . getvaluefromsettings($settings_array, 'reservation_invalid_token_info') . '</title>';
        }
    ?>
    <style>
        body {
            padding: 20px;
            margin: 0;
            background-image: url('<?php echo getvaluefromsettings($settings_array, 'reservation_page_background_image'); ?>');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
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
        }

        h2 {
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_subtitles_font_size'); ?>;
            background-color: <?php echo getvaluefromsettings($settings_array, 'reservation_subtitles_background_color'); ?>;
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

        input[type="text"], input[type="email"], input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_border'); ?>;
            box-sizing: border-box;
            font-size: <?php echo getvaluefromsettings($settings_array, 'reservation_buttons_font_size'); ?>;
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
            color: red;
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
    <div class="content">
    <?php
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
                    $name = filter_var($name,FILTER_SANITIZE_STRING);
                    $email = filter_var($email,FILTER_SANITIZE_STRING);
                    $name_dbsafe = filter_var($name,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

                    // Check, if mail is not empty
                    if ($email != "") {
                        // Delete reservation entry from database only if e-mail is correct
                        $stmt = $conn->prepare("DELETE FROM reservations WHERE name = :name AND email = :email");
                        $stmt->bindParam(':name', $name_dbsafe);
                        $stmt->bindParam(':email', $email);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            echo '<p class="success_info-box fade-out">';
                            echo getstrfromtranslation($lang_array, 'feedback_reservation_undo_success');
                            echo '</p>';
                            $was_success = 1;
                        } else {
                            echo '<p class="fail_info-box">';
                            echo getstrfromtranslation($lang_array, 'feedback_reservation_undo_failed');
                            echo '</p>';
                        }
                    } else {
                        echo '<p class="fail_info-box">';
                        echo getstrfromtranslation($lang_array, 'feedback_reservation_undo_failed_no_mail');
                        echo '</p>';
                    }
                } else {
                    // Check if form was subitted
                    if (isset($_POST['submit']) && (getvaluefromsettings($settings_array, 'reservation_enabled') == 1 || $adminmode_enabled == 1) && !isset($_GET['delete'])) {
                        $name = $_POST['name'];
                        $email = $_POST['email'];

                        // Sanitize strings
                        $name = filter_var($name,FILTER_SANITIZE_STRING);
                        $email = filter_var($email,FILTER_SANITIZE_STRING);
                        $name_dbsafe = filter_var($name,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
                        $name_base64 = base64_encode($name);

                        // Check if user inputs are valid
                        $isvalidname = checkifnameisvalid($name, getvaluefromsettings($settings_array, 'reservation_name_maxchar'), getvaluefromsettings($settings_array, 'reservation_name_minchar'), getvaluefromsettings($settings_array, 'reservation_name_allowunicode'), getvaluefromsettings($settings_array, 'reservation_name_blacklist_enable'), getvaluefromsettings($settings_array, 'reservation_name_blacklist'), getvaluefromsettings($settings_array, 'reservation_name_blacklist_unicode_enable'), getvaluefromsettings($settings_array, 'reservation_name_blacklist_unicode_base64'), getvaluefromsettings($settings_array, 'reservation_name_whitelist_regex_enable'), getvaluefromsettings($settings_array, 'reservation_name_whitelist_regex'));
                        $isvalidmail = checkifmailisvalid($email, getvaluefromsettings($settings_array, 'reservation_email_required'), getvaluefromsettings($settings_array, 'reservation_email_whitelist_enable'), getvaluefromsettings($settings_array, 'reservation_email_whitelist'), getvaluefromsettings($settings_array, 'reservation_email_whitelist_regex_enable'), getvaluefromsettings($settings_array, 'reservation_email_whitelist_regex'));
                        if ($name != "" && $isvalidmail == 1 &&  $isvalidname == 1) {
                            // Check, if name already exists in database
                            $isduplicate = 0;
                            if (count($reservations) > 0) {
                                foreach ($reservations as $reservation) {
                                    if (strtolower($reservation['name']) == strtolower($name_dbsafe)) {
                                        $isduplicate = 1;
                                    }
                                }
                            }
                            if ($isduplicate == 0) {
                                //Check if reservation limit is reached, only continue if places are left
                                if (count($reservations) < $max_reservations || getvaluefromsettings($settings_array, 'reservation_max') == 0) {
                                    // Add reservation into database
                                    $stmt = $conn->prepare("INSERT INTO reservations (name, name_base64, email, date_added) VALUES (:name, :name_base64, :email, :date_added)");
                                    $stmt->bindParam(':name', $name_dbsafe);
                                    $stmt->bindParam(':name_base64', $name_base64);
                                    $stmt->bindParam(':email', $email);
                                    $stmt->bindParam(':date_added', date('Y-m-d H:i:s'));
                                    $stmt->execute();

                                    echo '<p class="success_info-box fade-out">';
                                    echo getstrfromtranslation($lang_array, 'feedback_reservation_success');
                                    echo '</p>';
                                    $was_success = 1;
                                } else {
                                    // Reservation is not allowed, maximium is reached.
                                    echo '<p class="fail_info-box">';
                                    echo getstrfromtranslation($lang_array, 'feedback_reservation_failed');
                                    echo '</p>';
                                }
                            } else {
                                echo '<p class="fail_info-box">';
                                echo getstrfromtranslation($lang_array, 'feedback_reservation_failed_name_duplicate');
                                echo '</p>';
                            }
                        } else {
                            if (isset($_POST['name']) && isset($_POST['email'])) {
                                if ($isvalidname == 0 || $isvalidmail == 0) {
                                    // The name does not met the defined min or max lenght or contains not allowed unicode chars
                                    echo '<p class="fail_info-box">';
                                    echo getstrfromtranslation($lang_array, 'feedback_reservation_constraints_not_met');
                                    echo '</p>';
                                } else {
                                    // Generic error. Show error message to user
                                    echo '<p class="fail_info-box">';
                                    echo getstrfromtranslation($lang_array, 'feedback_reservation_failed');
                                    echo '</p>';
                                }
                            }
                        }
                    }
                }

                // Check if admin-mode enabled and remove link was used
                if (isset($_GET['delete']) && isset($_GET['delname']) && ($adminmode_enabled == 1 || $moderatormode_enabled == 1)) {
                    // Sanitize strings
                    $name = filter_var($_GET['delname'],FILTER_SANITIZE_STRING);
                    $name_dbsafe = filter_var($name,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
                    $name_base64 = base64_encode($name);

                    // Delete reservation entry from database without checking e-mail
                    $stmt = $conn->prepare("DELETE FROM reservations WHERE name = :name");
                    $stmt->bindParam(':name', $name_dbsafe);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        echo '<p class="success_info-box fade-out">';
                        echo getstrfromtranslation($lang_array, 'feedback_reservation_undo_success');
                        echo '</p>';
                        $was_success = 1;
                    } else {
                        echo '<p class="fail_info-box">';
                        echo getstrfromtranslation($lang_array, 'feedback_reservation_undo_failed');
                        echo '</p>';
                    }
                }

                // Check if admin-mode enabled and all items need to me removed
                if (isset($_GET['cleanall']) && $adminmode_enabled == 1) {
                    if ($_GET['cleanall'] == 1) {
                        // Delete all rows in table
                        if (cleanallreservations($conn)) {
                            echo '<p class="success_info-box fade-out">';
                            echo getstrfromtranslation($lang_array, 'feedback_reservation_undo_success');
                            echo '</p>';
                            $was_success = 1;
                        } else {
                            echo '<p class="fail_info-box">';
                            echo getstrfromtranslation($lang_array, 'feedback_reservation_undo_failed');
                            echo '</p>';
                        }
                    }
                }

                // Check if settings changed
                if (isset($_GET['savesettings']) && $adminmode_enabled == 1) {
                    if ($_GET['savesettings'] == 1) {
                    // Update settings in database
                    saveadminsetting($conn,"reservation_name");
                    saveadminsetting($conn,"reservation_additional_info");
                    saveadminsetting($conn,"reservation_show_additional_info_link");
                    saveadminsetting($conn,"reservation_additional_info_link_text");
                    saveadminsetting($conn,"reservation_additional_info_link");
                    saveadminsetting($conn,"reservation_details");
                    saveadminsetting($conn,"reservation_show_details_info_link");
                    saveadminsetting($conn,"reservation_details_info_link_text");
                    saveadminsetting($conn,"reservation_details_info_link");

                    saveadminsetting($conn,"primary_textfield_name_placeholder_hint");
                    saveadminsetting($conn,"primary_textfield_email_placeholder_hint");

                    saveadminsetting($conn,"reservation_max_additional_info");
                    saveadminsetting($conn,"reservation_no_data_additional_info");
                    saveadminsetting($conn,"reservation_disabled_additional_info");
                    saveadminsetting($conn,"reservation_invalid_token_info");

                    saveadminsetting($conn,"reservation_show_additional_disclaimer_enabled");
                    saveadminsetting($conn,"reservation_additional_disclaimer_title");

                    saveadminsetting($conn,"reservation_name_maxchar");
                    saveadminsetting($conn,"reservation_name_minchar");
                    saveadminsetting($conn,"reservation_max");
                    saveadminsetting($conn,"reservation_enabled");
                    saveadminsetting($conn,"reservation_undo_enabled");
                    saveadminsetting($conn,"reservation_show_attendees_enabled");
                    saveadminsetting($conn,"reservation_show_reservation_limit_enabled");
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
                    saveadminsetting($conn,"reservation_token_enabled");
                    saveadminsetting($conn,"reservation_token");
                    saveadminsetting($conn,"reservation_allow_get_submit");

                    saveadminsetting($conn,"moderator_presharedkey");
                    saveadminsetting($conn,"admin_presharedkey");

                    saveadminsetting($conn,"reservation_top_image");
                    saveadminsetting($conn,"reservation_top_image_alt_description");
                    saveadminsetting($conn,"reservation_page_background_image");
                    saveadminsetting($conn,"reservation_page_favicon");

                    echo '<p class="success_info-box fade-out">';
                    echo getstrfromtranslation($lang_array, 'admin_setting_change_success');
                    echo '</p>';
                    $was_success = 1;
                    }
                }

                if ($was_success == 1) {
                    // Action successful..refresh page after 3 secounds
                    $was_success = 0;
                    echo '<meta http-equiv="refresh" content="3; URL=';
                    echo "'";
                    echo $url_clean;
                    echo "'";
                    echo ' /><br>';
                }
            } else {
                // Token is not valid, no nothing
            }
        } catch (PDOException $e) {
            echo "Fehler: " . $e->getMessage();
        }
    ?>
    <?php
        if ($token_valid == 1) {
            // Header and text
            echo '<img class="image_top" alt="' . getvaluefromsettings($settings_array, 'reservation_top_image_alt_description') . '" src="' . getvaluefromsettings($settings_array, 'reservation_top_image') . '">';
            echo '<h1>' . getvaluefromsettings($settings_array, 'reservation_name') . '</h1>';
            echo '<div class="info-box">' . getvaluefromsettings($settings_array, 'reservation_additional_info') . '<br>';
            if (getvaluefromsettings($settings_array, 'reservation_show_additional_info_link') == 1) {
                echo '<a href="';
                echo getvaluefromsettings($settings_array, 'reservation_additional_info_link');
                echo '" target="_blank">';
                echo getvaluefromsettings($settings_array, 'reservation_additional_info_link_text');
                echo '</a>';
            }
            echo '<br><br>';
            echo '<details tabindex="1"><summary>' . getstrfromtranslation($lang_array, 'summary_text') . '</summary>';
            echo getvaluefromsettings($settings_array, 'reservation_details');
            if (getvaluefromsettings($settings_array, 'reservation_show_details_info_link') == 1) {
                echo '<br><a href="';
                echo getvaluefromsettings($settings_array, 'reservation_details_info_link');
                echo '" target="_blank">';
                echo getvaluefromsettings($settings_array, 'reservation_details_info_link_text');
                echo '</a>';
            }
            echo '</details></div>';

            // Registration form
            // Check, if fields should be filled out from GET
            $field_name = "";
            $field_mail = "";
            if (isset($_GET['name'])) {
                // Name was provided, sanitize string
                $field_name = filter_var($_GET['name'],FILTER_SANITIZE_STRING);
            }
            if (isset($_GET['mail'])) {
                // Name was provided, sanitize string
                $field_mail = filter_var($_GET['mail'],FILTER_SANITIZE_STRING);
            }
            echo '<h2>' . getstrfromtranslation($lang_array, 'title_reservation_form') . '</h2>';
            echo '<form method="POST" action="' . $url . '">';
            echo '<label for="name">' . getstrfromtranslation($lang_array, 'primary_textfield_name') . '</label>';
            echo '<input type="text" name="name" id="name" tabindex="2" value="' . $field_name . '" placeholder="' . getvaluefromsettings($settings_array, 'primary_textfield_name_placeholder_hint') . '" required><br>';
            echo '<label for="email">' . getstrfromtranslation($lang_array, 'primary_textfield_mail') . '</label>';
            echo '<input type="email" name="email" id="email" tabindex="3" value="' . $field_mail . '" placeholder="' . getvaluefromsettings($settings_array, 'primary_textfield_email_placeholder_hint');
            if (getvaluefromsettings($settings_array, 'reservation_email_required') == 1) {
                echo '" required><br>';
            } else {
                echo '"><br>';
            }
            if (getvaluefromsettings($settings_array, 'reservation_enabled') == 1 || $adminmode_enabled == 1) {
                // Reservation is enabled
                if (count($reservations) < $max_reservations || getvaluefromsettings($settings_array, 'reservation_max') == 0) {
                    // Places are left, show button to allow reservation
                    echo '<input type="submit" name="submit" tabindex="4" value="';
                    echo getstrfromtranslation($lang_array, 'button_submit_reservation');
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
                    echo '<button type="submit" formaction="?delete=1" tabindex="5" class="button_undo">';
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
            echo '<p class="fail_info-box">';
            echo getvaluefromsettings($settings_array, 'reservation_invalid_token_info');
            echo '</p>';
        }
    ?>
    <?php
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
                $name_cleaned = filter_var($input_name,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

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

        function sanitize_admin_setting(string $admin_setting_input) {
            $admin_setting_input = htmlspecialchars($admin_setting_input, ENT_COMPAT | ENT_SUBSTITUTE);
            $admin_setting_input = str_ireplace("&lt;br&gt;", "<br>", $admin_setting_input);
            return $admin_setting_input;
        }

        function saveadminsetting($db_connection, string $admin_setting_tag) {
            try {
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

        function echo_admin_setting_field(array $lang_obj, array $settings_obj, string $settingstag, string $input_type, int $mininput, int $maxinput) {
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
                            $reservation_name = $reservation['name_base64'];
                            $reservation_name = base64_decode($reservation_name);
                            echo "<tr><td>";
                            echo $reservation['date_added'];
                            echo '</td><td>';
                            echo $reservation['email'];
                            echo '</td><td>';
                            echo $reservation_name;
                            echo '</td><td>';
                            echo '<a href="';
                            echo $url;
                            echo '&delete=1&delname=';
                            echo $reservation_name;
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
                                    $reservation_name = $reservation['name_base64'];
                                    $reservation_name = base64_decode($reservation_name);
                                    echo "<li>";
                                    echo $reservation_name;
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
                if (getvaluefromsettings($settings_array, 'reservation_additional_disclaimer') != "") {
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
                echo '<summary>' . getstrfromtranslation($lang_array, 'admin_details_text') . '</summary>';
                echo getstrfromtranslation($lang_array, 'admin_setting_general_information') . '<br>';
                echo '<form method="POST" action="' . $url_clean . '&savesettings=1">';
                
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_info', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_additional_info_link', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_info_link_text', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_info_link', 'url', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_details', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_details_info_link', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_details_info_link_text', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_details_info_link', 'url', 1, 1024);

                echo_admin_setting_field($lang_array, $settings_array, 'primary_textfield_name_placeholder_hint', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'primary_textfield_email_placeholder_hint', 'text', 1, 1024);

                echo_admin_setting_field($lang_array, $settings_array, 'reservation_max_additional_info', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_no_data_additional_info', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_disabled_additional_info', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_invalid_token_info', 'text', 1, 1024);

                echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_additional_disclaimer_enabled', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_additional_disclaimer_title', 'text', 1, 1024);

                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_maxchar', 'number', 1, 255);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_minchar', 'number', 1, 255);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_max', 'number', 0, 4096);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_enabled', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_undo_enabled', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_attendees_enabled', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_show_reservation_limit_enabled', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_blacklist_enable', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_blacklist', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_blacklist_unicode_enable', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_blacklist_unicode_base64', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_whitelist_regex_enable', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_name_whitelist_regex', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_required', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_whitelist_enable', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_whitelist', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_whitelist_regex_enable', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_email_whitelist_regex', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_token_enabled', 'number', 0, 1);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_token', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_allow_get_submit', 'number', 0, 1);

                echo_admin_setting_field($lang_array, $settings_array, 'moderator_presharedkey', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'admin_presharedkey', 'text', 1, 1024);

                echo_admin_setting_field($lang_array, $settings_array, 'reservation_top_image', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_top_image_alt_description', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_background_image', 'text', 1, 1024);
                echo_admin_setting_field($lang_array, $settings_array, 'reservation_page_favicon', 'text', 1, 1024);

                echo '<button type="submit" formaction="' . $url_clean . '&cleanall=1" tabindex="28" class="button_undo"><b>';
                echo getstrfromtranslation($lang_array, 'admin_button_remove_all_reservations') . '</b>';
                echo '</button><input type="submit" name="submit_settings" tabindex="30" value="' . getstrfromtranslation($lang_array, 'admin_setting_submit_button_text') . '">';
                echo '<button type="submit" formaction="' . $url_token_clean . '" tabindex="31" class="button_undo">';
                echo getstrfromtranslation($lang_array, 'admin_button_logout');
                echo '</form></details></div>';
            }

            echo '</div>';
        ?>
    <div class="appinfo-div"><?php echo getstrfromtranslation($lang_array, 'bottom_project_disclaimer'); ?><br><a href="https://github.com/MichaelKirgus/PHPEasyReservation" target="_blank"><?php echo getstrfromtranslation($lang_array, 'bottom_project_link'); ?></a><br><?php echo $app_version; ?></div>
    </body>
</html>