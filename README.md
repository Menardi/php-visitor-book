#PHP Visitor Book

This simple PHP script allows visitors to a page to leave a message. Messages are stored in an SQLite database, but with minimal adaptation could also use other types of SQL databases (such as MySQL and PostgreSQL).

##Installation

The script is designed to be included into an existing website. To do this, put the visitorbook.php file on your server and include it in the page you want it to be on.

    include 'visitorbook.php';

On first run, an SQLite database named _visitorbook.db_ will be created. If this name conflicts with existing files on your server, change DB_NAME in the configuration section of visitorbook.php.

The script will also run standalone without issue.

###reCaptcha

Use of reCaptcha is supported through the Google reCaptcha API. Simply add your public and private reCaptcha keys in the configuration section of visitorbook.php to enable it. Keys can be obtained from https://www.google.com/recaptcha/admin/create. Comment out or remove the two lines defining the keys to disable reCaptcha.
