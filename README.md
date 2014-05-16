Reprint
=======

A tool for replicating the content from an RSS feed onto a different site.


Usage
-----

Move the `src` directory somewhere on your server, and put the content from `public` where you want the feed content copied. Then edit the `_config.php` file and set the details you wish to use.


### Template

The file specificed with the config option `template` will be used to render each post. `{{variable}}` syntax is used for variable insertion. The variable `{{data}}` will be replace with PHP code for an array containing the post's details. Otherwise, the following variables can be used:

- **`{{title}}`:** The post's title in plain text
- **`{{title_rendered}}`:**	The post's title with HTML formatting applied
- **`{{date}}`:** The post's date (in ISO 8601 format)
- **`{{summary}}`:** The post's summary in plain text (up to 150 characters)
- **`{{summary_rendered}}`:** The post's summary with HTML formatting applied (up to 150 characters)
- **`{{content}}`:** The content for the post in HTML

If the `{{data}}` variable is used, it will be replaced with syntax for a PHP array with the above keys (without the wrapping `{{}}`), with one difference: the `date` value will be an instance of [`\DateTime`](http://us3.php.net/manual/en/book.datetime.php).

The default template is a PHP file that then includes a global template, however a HTML template could be used for static site rendering.


### Reloading

The posts listing will be regenerated each time – from a cache – however the post content will only be regenerated on demand by loading the `_rebuild.php` page.



Development
-----------

The system is more-or-less "feature complete", however if you wish to contribute a feature or bugfix please open a pull request! There are PHPUnit tests that should be run, and if you add new features please add tests to cover them.

The dependencies are not managed through Composer for easier installation. I'm just as upset as you are, trust me.


Licence
-------

[MIT](LICENSE). © 2014 Adam Averay
