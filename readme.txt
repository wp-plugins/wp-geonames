=== WP GeoNames ===
Contributors: jacques malgrange
Donate link: http://www.boiteasite.fr/
Tags: city, geo, data, sql, table, geonames, gps, place
Requires at least: 3.0.1
Tested up to: 4.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to insert all or part of the global GeoNames database in your WordPress base.

== Description ==

This lightweight plugin makes it easy to install the millions of GEONAMES Data on your WordPress site.
It only works in ADMIN connection and therefore causes no slowing Site.
It allows :

* Install data from one or more file ;
* Choose column to install to avoid excessive enlargement of the base ;
* Choose type of data to install (city, park, road...) ;
* Remove all the data.

== Installation ==

*Install and Activate*

1. Unzip the downloaded wp-geonames zip file
2. Upload the `wp-geonames` folder and its contents into the `wp-content/plugins/` directory of your WordPress installation
3. Activate WP GeoNames from Plugins page

*Use*

You must use the WordPress tools to get the database. **WPDB is your friend**.
You can write the code directly in your template or in functions.php of your theme.

Name of the table : ($wpdb->prefix)geonames

Names of the columns :

* `idwpgn` (bigint)
* `geonameid` (bigint)
* `name` (varchar)
* `asciiname` (varchar)
* `alternatenames` (text)
* `latitude` (decimal)
* `longitude` (decimal)
* `feature_class` (char)
* `feature_code` (varchar)
* `country_code` (varchar)
* `cc2` (varchar)
* `admin1_code` (varchar)
* `admin2_code` (varchar)
* `admin3_code` (varchar)
* `admin4_code` (varchar)
* `population` (bigint)
* `elevation` (int)
* `dem` (smallint)
* `timezone` (varchar)
* `modification_date` (date)

Example : get GPS position for a specific city in a specific country :

`global $wpdb;
$s = $wpdb->get_row("SELECT latitude, longitude 
	FROM ".$wpdb->prefix."geonames 
	WHERE name='Paris' and country_code='FR' ");
echo $s->latitude . " - " . $s->longitude;`


Example : 10 most populous cities in Switzerland :

`global $wpdb;
$s = $wpdb->get_results("SELECT name, population 
	FROM ".$wpdb->prefix."geonames 
	WHERE country_code='CH' and feature_class='P' 
	ORDER BY population DESC 
	LIMIT 10");
foreach($s as $t)
	{
	echo $t->name. " : " . $t->population . "<br />";
	}`


Example : hotels within 40 km from Marbella (ES) :

`global $wpdb;
$p = $wpdb->get_row("SELECT latitude, longitude 
	FROM ".$wpdb->prefix."geonames 
	WHERE name='Marbella' and country_code='ES' ");
$dlat = 40 / 1.852 / 60;
$dlon = 40 / 1.852 / 60 / cos($p->latitude * 0.0174533);
$s = $wpdb->get_results("SELECT name, latitude, longitude
	FROM ".$wpdb->prefix."geonames 
	WHERE country_code='ES' and 
		feature_code='HTL' and 
		latitude<".($p->latitude+$dlat)." and
		latitude>".($p->latitude-$dlat)." and
		longitude<".($p->longitude+$dlon)." and
		longitude>".($p->longitude-$dlon)."
	LIMIT 100");
foreach($s as $t)
	{
	$d = (floor(sqrt(pow(($p->latitude-$t->latitude)*60*1.852,2)+pow(($p->longitude-$t->longitude)*60*1.852,2))));
	if($d<=40) echo $t->name. " : " . $d . " km<br />";
	}`


== Changelog ==

= 1.0 =
25/11/2014 - First stable version.
