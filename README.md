# PHPEasyReservation
Easy and user-friendly PHP web application for event registration.

## Goal
Good, but lightweight tools for managing reservations for events are very rare.<br>
This project wants to fill the gap between "big beefy" self-hosted solutions and online services from cloud providers.<br>
By default there is no need for javascript or other external resources (except PHPMailer, which is optional if you want to send mails).

## Installation
You need an MySQL-Instance with version 8.0 or higher and an web server with PHP version 8.0 or higher.<br>
Even it is possible to use the application via HTTP, it is not recommended. Please use an valid SSL certificate and enable or better force to HTTPS.<br>
Import the files from folder "sql" into the database.<br>
Create an new user and grant that user the permission for the database.<br>
**Do NOT use the root mysql user!!!**<br>
Copy the "index.php" and the folders "assets" and "phpmailer" (with files and sub-directories) to the root-directory in your webserver.<br>
Edit the following lines in "index.php":

```
$http_schema = "https"; (only edit, if you want to use http instead of https)
$base_url = "Your base URL, for example https://reservation.example.com/";
```

```
$dbhost = 'Database host or IP';                // Hostname of database
$dbname = 'events';                             // Database name
$dbuser = 'Database user';                      // Database username
$dbpass = 'Database password';                  // Datebase password
```

Now you should be able to open the site via the URL:<br>
(https://reservation.example.com/?adminpw=secure_adminkey)<br>
At the bottom of the page it is now possible to open the settings menu and change all settings.<br>
At default settings you need to put the background picture, top picture and favicon in the corresponding folders:

```
$background_Image_dir = "assets/background";
$top_image_dir = "assets/top_image";
$favicon_dir = "assets/favicon";
```

If you put images in the corresponding folder, its possible to select the image via the settings menu.<br>
The setting only contains the image filename itself, not the absolute path.

## Used SQL tables
**settings**<br>
All settings for the application<br><br>
**translations**<br>
All transaltions<br><br>
**reservations**<br>
All user generated reservations with all important information<br><br>
**approvements**<br>
Tracking for user validation (mail validation and administrator approvement steps)

## Reverse Proxy configuration
There is no general special configuration needed for using this application with an reverse proxy.<br>
However, you want to use the SMTP rate limiting option its essential to set the header that contains the real source IP adress.<br>
This defaults to "HTTP_X_FORWARDED_FOR", but can be changed in the settings menu or via the setting "reservation_mail_smtp_sourceip_ratelimit_header" in the database.<br>
Please make sure that the used reverse proxy apply this header to the request.

## Usage
The "normal" user can access the site via an access token, which can be set via the settings menu or via the setting "reservation_token".<br>
For example, an valid user registration URL would be (https://reservation.example.com/?t=myverysecrettoken).<br>
There is also an option for setting an moderator key, which can delete reservations an view some more informations.<br>
You can set the moderator key via the settings menu oder via the setting "moderator_presharedkey".<br>
For example, an valid moderator URL would be (https://reservation.example.com/?moderatorpw=mymoderatortoken).

**The administrator key is set default to "secure_adminkey", and SHOULD BE CHANGED via settings menu or the setting "admin_presharedkey" in the database.**<br>
**All users that known the administrator key can change all settings, delete reservations and also change the keys itself!**

## Translations
Currently this project has only translations for English and German.<br>
The used translation depends on the users browser prefered language setting.<br>
The priority of the used translation can be changed here:
```
$available_languages = array("en", "de");
// $static_lang = "de";
```
It is possible to force an available translation, comment-out the 2nd line and set your prefered language code.<br>
Its also possible to add additional translations.

## License
This project is licensed under the GNU LESSER GENERAL PUBLIC LICENSE Version 2.1.

## Credits
This project uses PHPMailer for sending mails:<br>
[PHPMailer](https://github.com/PHPMailer/PHPMailer)