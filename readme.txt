=== WP CSV ===
Contributors: cpkwebsolutions
Donate link: http://cpkwebsolutions.com/donate
Tags: csv, import, export, bulk, easy, all, importer, exporter, posts, pages, tags, custom, images
Requires at least: 3.3
Tested up to: 3.6.1
Stable tag: 1.4.2

A powerful, yet simple, CSV importer and exporter for Wordpress posts, pages, and custom post types. 

== Description ==

Most WP features are fully supported, including custom post types and custom taxonomies.  Learn more <a href='http://cpkwebsolutions.com/wp-csv'>here</a>.

== Installation ==

Refer to the <a href='http://cpkwebsolutions.com/wp-csv/quick-start-guide'>Quick Start Guide</a>.

== Frequently Asked Questions ==

<a href='http://cpkwebsolutions.com/wp-csv/faq'>Frequently Asked Questions</a> are stored on our main website.

== Screenshots ==

No screenshots available.

== Changelog ==

= 1.4.2 =
* Code cleanup
* Fixed post_author bug (non-existant user ids will now export blank)
= 1.4.1 =
* Fixed minor export bug
= 1.4.0 =
* Added support for custom taxonomies (NOTE: Old export files are not compatible since the column heading names have changed)
* Added a check for iconv support
* Tweak to reduce memory footprint (experimental)
= 1.3.8 =
* Added a custom post type filter for export (thanks to Phillip Temple for the idea and for submitting the code)
= 1.3.7 =
* Added error checking and helpful messages when the wrong data is put into the Author field.
* Improved validation of comma separated category lists
= 1.3.6 =
* Added support for post_author field.
= 1.3.5 =
* Fixed: Error 'creating default object from empty value'.
= 1.3.4 =
* Enhancement: Plugin will now automatically create a backup folder in one of 4 locations (in order of preference) and add an .htaccess file to prevent unauthorized download.
= 1.3.3 =
* Fixed: Another session bug
= 1.3.2 =
* Fixed: Session bug preventing download of CSVs
* Fixed: Version string not being updated
* Added: Automatic search and/or creation of a safe download folder
= 1.3.1 =
* Fixed: mysqli_real_escape_string issue
= 1.3 =
* Fixed: minor incompatibility with WP 3.5
= 1.2 =
* Fixed: minor incompatibility with PHP 5.4
* Fixed: small improvement to the download mechanism
= 1.1 =
* Made csv file path configurable
= 1.0 =
* Initial upload

== Upgrade Notice ==

1.4.0 - Custom taxonomy support added (NOTE: Old export files are not compatible.  Make sure you export after upgrading and only import the newly exported files.)
