=== Current Weather ===
Contributors: mmattner
Donate link:
Plugin URI: http://mikemattner.com/
Author: Mike Mattner
Author URI: http://www.mikemattner.com
Tags: weather, sidebar widget
Requires at least: 2.8
Tested up to: 3.3.1
Stable tag: 1.5.4

Display current weather, using data from Yahoo! Weather API, as a widget or on any page or post using a shortcode.

== Description ==
This plugin uses data from the Yahoo! Weather API to display the current weather for a location of your choosing as well as the forecast for the current and next day.

The simplest way to insert the shortcode into your page or post is to use `[current_weather location="your location here"]` to get the default implementation, which would be your current conditions with forecast and location shown using standard units.

The full list of shortcode attributes is:
* location - Use a city, state; zip code; WOEID; etc.
* units - Either f, for standard, or c, for metric.
* show - True, if you want to show your location. Default is true.
* forecast - True, if you want to show the forecast. Default is true.
* condensed - True, if you want to show the condensed version. Default is false.

== Features ==
* Choose any location worldwide. The plugin will determine your location's WOEID for you.
* Use either standard or metric units.
* Show or hide the location.
* Show or hide the two day forecast.
* Condensed version showing icon, temperature, conditions, and location only.
* Custom title text.
* Multi widget.
* Simple shortcode.

== Installation ==

1. Unzip and upload the folder `/current-weather/` to the `/wp-content/plugins/` directory of your WordPress installation.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the shortcode or widget on your site.
4. Customize the `css` to match your site or use the default setup.

== Frequently Asked Questions ==

= Shortcode? =

This simplest way to insert this into your page or post is to use `[current_weather location="your location here"]` to get the default implementation, which would be your location with forecast and location shown using standard units.

The full list of attributes:
* location - Use a city, state; zip code; WOEID; etc.
* units - Either f, for standard, or c, for metric.
* show - True, if you want to show your location. Default is true.
* forecast - True, if you want to show the forecast. Default is true.
* condensed - True, if you want to show the condensed version. Default is false.

= What about the Yahoo! API request limitations? =

The xml data is cached for 15 minutes to eliminate the issue of bumping up against request limitations, though that means the data is not always as current as possible.

== Screenshots ==

== Changelog ==

= 1.5.4 =
* Fixed issue with the forecast not showing up.

= 1.5.3 =
* Fixed problems with the shortcode functionality.

= 1.5.2 =
* A few minor adjustments to styles and I've added an option for a more condensed version.

= 1.5 =
* Weather.com abandoned it's xml feed and switched to a paid API. Switched to use of the Yahoo! Weather API, but this reduced the forecast to a two day range.

= 1.0 =
* This is the first version

== Upgrade Notice ==

= 1.5.4 =