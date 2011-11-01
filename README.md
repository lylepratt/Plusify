## Plusify ##
The beginnings of a simple, themeable home page that runs off of your public posts to Google Plus. All posts and comments are saved in a SQLITE database, so using this also has the added benfit of backing up all of your Google Plus posts.

## INSTRUCTIONS ##
- Put `index.php` and `.htaccess` in your root web directory.
- Put the `theme` directory one directory under your root web directory.
- Configure your settings in the `CONFIGURATION` section of the `Plusify` class.
- Make sure the directory that holds the `SQLITE` file is writeable by your web server.
- If you want to use the `clean_urls` configuration option, you'll need `mod_rewrite` enabled.
