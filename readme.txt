=== Sneakily Hide WP Versions ===
Contributors: ruminativewp
Donate link: https://ruminativewp.com/products/sneakily-hide-wp-versions-plugin
Tags: hide generator, hide version, remove version, remove generator, sneakily hide wp versions
Requires at least: 5.0
Tested up to: 5.8.1
Requires PHP: 7.0
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hides the WordPress version everywhere it's displayed, including feed generator tags, static asset URLs, and load-styles.php and load-scripts.php.

== Description ==

Hides the WordPress version everywhere it’s displayed, including feed generator tags, static asset URLs, load-styles.php and load-scripts.php, and in /wp-admin/install.php and /wp-admin/upgrade.php. Some plugins hide the WordPress version, but not everywhere, and that’s as good as not hiding it at all. This plugin hides all occurrences of the WordPress core version.

This version hiding doesn't break the cache-busting that WordPress does using the version number in static assets URLs.

== Features ==

* Hides WordPress version in static asset (CSS, JS) URLs
* Hides WP version in feed XML
* Hides WP version in admin pages and static asset loaders (load-scripts, load-styles)
* Doesn’t break the cache-busting on WordPress core assets when WordPress upgrades

Multisite is not supported yet, but support is intended for a future release.

== Supported Platforms ==

This plugin works with WordPress 5 and PHP 7+. It’s best to use the Apache web server, with .htaccess support. Nginx works ok too, but you need to update the web server configuration manually. Microsoft IIS is not officially supported, for now.

== Requirements ==

* WordPress 5 or above
* PHP 7 or above
* Apache or Nginx web servers (Apache 2.4.10 and above, or Nginx 1.11.6 and above)
* If using Apache, either .htaccess support enabled AND permalinks enabled *, or the ability to edit the web server configuration and restart the web server
* If using Nginx, the ability to edit the web server configuration and restart the web server

== Installation ==

1. Ensure your setup meets the Requirements, above
2. Install from the Plugin Directory or manually download and unpack the zip file
3. Activate plugin using WordPress admin
4. Depending on your configuration, you might need to update the web server configuration (.htaccess / Apache config / nginx config) and restart your web server

== Support ==

Support isn't currently available, but if you've followed instructions and read the FAQ and it still isn't working on your platform, bug reports are appreciated, either by using the forum or by [direct contact](https://ruminativewp.com/pages/contact). If there's a bug it will probably be fixed in a short while.

== Frequently Asked Questions ==

= Why bother hiding the WordPress version? =
If somebody wants to hack your site, knowing the WordPress version you’re running saves a lot of time. This plugin denies potential attackers that information.

= Isn’t this Security Through Obscurity? =
First: a few comments about security through obscurity. As the WordPress Hardening guide points out, this is correctly, usually seen as a security anti-feature: if something is secure, it shouldn’t matter what an attacker knows about it. This principle works well in certain situations: cryptography, open source code, anything where the implementation is public. However, it doesn’t work well in situations where implementations are not public: for example, the behind-the-scenes implementation, infrastructure and source code of your own website. Unless you intend to expose all that for public review – something true in a very limited number of cases – then “what configuration your WordPress site has” isn’t something that should be publicly knowable.

Given your website configuration is being kept private already, it only helps an attacker, and potentially hurts you, to expose information that doesn’t benefit your business. Again, the WordPress Hardening Guide says “However, there are areas in WordPress where obscuring information might help with security.” Obscuring the WordPress version number(s) is arguably one of those areas: one of the most common types of attacks against WordPress is “sending specially crafted HTTP requests [that exploit] … specific vulnerabilities.” Often these specific vulnerabilities rely on knowing the WordPress version, or the version of other plugins or themes. And information gathering, finding out the details of target websites and systems in advance of an attack, is part of penetration testing methodology for a reason: actual attackers use it. You don’t want them using it against your site.

Another way to look at this is: what is your threat model? What threats to your website and business are realistic and worth defending against? Vulnerabilities in WordPress core are threats worth taking seriously, because even if they’re fixed in a later release, there’s still a window of opportunity for attackers, and they’ll want to know if your site is up to date – by checking the WordPress version.

So is this security through obscurity? No. The security of your WordPress site comes from what’s elsewhere: keep your core and plugins up to date, using security plugins, using a Web Application Firewall, and all the other security best practices.

Our plugin hides information, making it unavailable to attackers, and making their life more difficult: an important part of having a secure website.

= Does Hide WP Version work correctly when WordPress updates to a new version? =
Yes.

= Why do my CSS and Javascript URLs still have a version at the end? =
Hide WP Version generates a random number and uses that as a fake WordPress version number. This is because WordPress uses the version number on the end of URLs for “cache busting” – forcing browsers to load a new version of a file. Generating and using a fake version number hides the real version number without breaking this aspect of WordPress’s functionality.

= Can I change the fake version number? =
Yes, but not to anything you choose. To update the fake version number, log in to your WordPress site as an Administrator and select Ruminative WP > Hide WP Version from the menu. There’s a button to bump the fake version.

= Can I use a specific value for the fake version number? =
No, because what happens when WordPress core is upgraded to a new version and this value needs to change? For simple values like “1” this could work, but then why bother specifying a custom value? If it’s not a simple value like “1”, but something like “pomegranate”, what should the new value be? You’d need to update the value manually every time WordPress core updates, and that’s annoying. Let the plugin handle this.

= Is this much code really necessary to hide WordPress versions? Isn’t it excessive? =
Respectively – yes, it’s necessary, because WordPress attaches extra meaning to version numbers, in its handling of static assets; and “it depends,” if you want to secure your site as much as possible, then it might not be excessive.

= Does this block the sending of my version to WordPress.org? =
No, currently this isn’t blocked, because WordPress.org knowing your WP version isn’t generally a security risk.

== Changelog ==

= 1.0.4 =
* Combine everything into 1 file to avoid rwp_ prefix
* Remove brain_base64.txt for now to save bandwidth
* Avoid using function_exists everywhere (use different way of allowing customisation)
* Escape more things
* Never return incorrect / empty fake version from shwv_get_current_fake_wp_version()
* Ensure decimal point in generated fake versions, incl after force-increase / _bump_()
* Use argument swapping
* Add reminder to remove rules in nginx
* Use mu-plugin to hide version in /wp-admin/upgrade.php and /wp-admin/install.php
* Unblock both upgrade.php and install.php since version hiding works there now
* Common function to create settings page URL
* Deactivation handles mu-plugin removal cleanly
* Split fs ops/display form on deactivation too

= 1.0.3 =
* Use virtual pages instead of direct load-styles.php / load-scripts.php
* Don't write abspath.php

= 1.0.2 =
* Check again if abspath.php needed in shwv_write_abspath_file()

= 1.0.1 =
* Rename shwv_possibly_write_abspath() -> shwv_setup_wp_filesystem()

= 1.0.0 =
* Fix bug where notices aren't displayed on activation
* Clarify and make consistent API usage from rwp_functions
* Escape things that are output
* Use translation functions
* Add testing document/checklist
* Separate nginx and apache rewriting rules
* Display nginx rewriting rules on plugin activation
* Display htaccess rules on plugin activation, if not writable
* Style web server config rules properly
* Fix incorrect error message in rwp_handle_unwritable_file()
* Hide Nginx version by default in displayed rules
* Attempt to hide the Server header web server version, if possible
* Update testing template to be more rigorous
* load-styles.php works with WP 5.8
* Add third color-code to RWP notices
* Update nginx message and error display on activation
* Use custom error handler for rwp_delete_with_markers()
* Display web server / PHP / Apache version on settings page
* Remove notice addition if install.php creation fails
* Handle a few more cases in setting Server header
* Add translatable strings to shwv.pot
* Display fake WP version in settings page
* Check for mod_rewrite if using Apache
* Use Tools menu instead of top-level admin menu if no other RWP plugins present & activated
* Don't duplicate URL prefix if WP installed in subdirectory, when rewriting load-script/load-styles
* Remove web server & PHP version headers in load-scripts/load-styles; update comments in load-scripts/load-styles
* load-scripts and load-styles find ABSPATH more intelligently
* Check if get_home_path() exists before use (JSON API)
* Add reminder on settings page about caches
* Make ABSPATH determination more robust in custom load-* scripts
* Look for wp-config.php a directory higher than ABSPATH in load-*
* If certain errors happen during activation, activate anyway + display the errors so they can be fixed
* Don't reinvent the wheel - use WP core function for web server detection
* Check capabilities in more places (docs recommended https://developer.wordpress.org/reference/functions/update_option/)
* Remove shwv_swap_files() call
* Prevent rewriting load-* scripts until abspath.php in place, if abspath.php is needed
* Don't hard-code URL to post from SHWV settings page
* abspath.php creation via Filesystem API works
* Refactor get creds into write-files and display-forms, at different points in WP load
* Make options handling better wrt default false
* Only display clear-caches message when activation complete
* Don't assume SERVER_SOFTWARE and SERVER_SIGNATURE keys in $_SERVER
* Remove unused code in rwp_functions.php
* shwv_cap_check() becomes rwp_cap_check()
* Wrap got_mod_rewrite() as well as get_home_path()
* Handle WP < 5.6 upgrade of core / removing pluging rules from .htaccess
* Also display permalink message if using index permalinks
* Add note about manual upgrades to settings page
* Rewrite to a customised upgrade.php to avoid upgrading problems with blocking
* Combine functions to get rewrite URLs
* Find ABSPATH in upgrade.php similar to load-*
* Replace ie.css version in upgrade.php

= 0.1.2 =
* Move rwp_detect_web_server to rwp_functions.php
* Display message to clear cache on activation
* Show a message when fake version increased
* Try not to display conflicting error messages if version bump fails
* Don't write fake version file, use a mechanism that's more reliable in load-styles / load-scripts
* Use a separate function for writing custom install.php
* Code style updates to be consitent with WP standards
* Add code to edit files between markers instead of overwriting entirely, use it when generating install.php

= 0.1.0 =
* Rename to "Sneakily Hide WP Versions"

= 0.0.5 =
* Add an error message system that can persist between page loads
* Fix bugs with that error message system and replacement load-scripts / load-styles
* Change logging to be consistent (hwv_debug_log())
* Admin menus for the plugin with icon
* Make the "fake" upgrade trigger not a URL trigger, instead use a link in the admin
* Update / remove comments
* Find ABSPATH if it's not defined in load-styles.php and load-scripts.php
* Remove fake_version.php on deactivation

= 0.0.4 =
* Use a fake WP version in static asset URLs, so cache busting still works, but the real version is hidden
* Include &amp;=ver in URLs to edit
* Replace version in admin load-styles URLs

= 0.0.3 =
* Don't use .htaccess-based mechanism to block URLs, since it's risky

= 0.0.2 =
* Hide <generator> tags and other instances of the WP version in feeds
* Block access to /readme.html and /license.txt to stop fingerprinting (and version grabbing from readme
  in older WP)

= 0.0.1 =
* Hide the <meta name="generator"> tag in HTML source

== Upgrade Notice ==

= 1.0.4 = 
Initial Plugin Directory release

