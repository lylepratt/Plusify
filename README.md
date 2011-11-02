## Plusify ##
The beginnings of a simple, themeable home page that runs off of your public posts to Google Plus. All posts and comments are saved in a SQLite database, so using this also has the benfit of backing up all of your public Google Plus posts. You basically just stick the `index.php` in a web directory and it works.


## Author Comments ##
I normally do web stuff with Python these days, but I used PHP because most web hosts support it. I've only spent a few hours working on this, so don't hate if you don't like the code or product. Also, I'm no designer so you'll probably think the default theme is ugly.

## Example Blog ##
http://www.lylepratt.com

## INSTRUCTIONS ##
- Put `index.php` and `.htaccess` in your root web directory.
- Put the `theme` directory somewhere other than your web directory.
- Configure your settings in the `Configuration` section of the `Plusify` class.
- Make sure the directory that holds the `SQLite` file and the file itself is writeable by your web server.
- If you want to use the `clean_urls` configuration option and the included `.htaccess` file, you'll need `mod_rewrite` enabled.

## To Do ##
- Default theme is lame
- Squash bugs
- Make better
- You tell me

## MODIFIED MIT LICENSE ##

Permission is hereby granted to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, and/or distribute copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

If you sell this Software or Themes for this Software, you have to give the author (Lyle Pratt) a cut.

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
