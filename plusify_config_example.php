<?php

$SETTINGS_API_URL = "https://www.googleapis.com/plus/v1";  //Don't change this
$SETTINGS_API_KEY = "YOUR GOOGLE API KEY";  //You can get one at https://code.google.com/apis/console/
$SETTINGS_GOOGLE_ID = "YOUR GOOGLE + ID";  //The big number in your Google+ profile URL
$SETTINGS_CLEAN_URLS = true;  //You need mod_rewrite enabled to enable this
$SETTINGS_TEMPLATE_DIR = "../theme/";  //Theme files go in this directory
$SETTINGS_ROOT_URL = "/";  //Use this if you want put this at something like yoursite.com/blog/. In that case it should be /blog/
$SETTINGS_SQLITE_FILE = "../plusify.sql";  //Make sure it and its parent directory is writable by your web server 
$SETTINGS_TIME_BETWEEN_UPDATES = 14400;  //Seconds between checks to the api for updates. It only checks when a page is loaded.

?>