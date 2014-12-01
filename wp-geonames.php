<?php
/*
Plugin Name: WP GeoNames
Author: Jacques Malgrange
Description: Allows you to insert all or part of the global GeoNames database in your WordPress base.
Version: 1.1
Author URI: http://www.boiteasite.fr
*/
add_action('wp_ajax_nopriv_wpgeonamesAjax', 'wpgeonamesAjax');
add_action('wp_ajax_wpgeonamesAjax', 'wpgeonamesAjax');
register_activation_hook ( __FILE__, 'wpGeonames_creation_table');
function wpGeonames_creation_table()
	{
	/*
	****** http://download.geonames.org/export/dump/readme.txt *********
	geonameid		: integer id of record in geonames database
	name			: name of geographical point (utf8) varchar(200)
	asciiname			: name of geographical point in plain ascii characters, varchar(200)
	alternatenames	: alternatenames, comma separated, ascii names automatically transliterated, convenience attribute from alternatename table, varchar(10000)
	latitude			: latitude in decimal degrees (wgs84)
	longitude			: longitude in decimal degrees (wgs84)
	feature class		: see http://www.geonames.org/export/codes.html, char(1)
	feature code		: see http://www.geonames.org/export/codes.html, varchar(10)
	country code		: ISO-3166 2-letter country code, 2 characters
	cc2				: alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters
	admin1 code		: fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20)
	admin2 code		: code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80) 
	admin3 code		: code for third level administrative division, varchar(20)
	admin4 code		: code for fourth level administrative division, varchar(20)
	population		: bigint (8 byte int) 
	elevation			: in meters, integer
	dem				: digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat.
	timezone			: the timezone id (see file timeZone.txt) varchar(40)
	modification date	: date of last modification in yyyy-MM-dd format
	*/
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); // dbDelta()
	global $wpdb;
	//
	if(!empty($wpdb->charset)) $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if(!empty($wpdb->collate)) $charset_collate .= " COLLATE $wpdb->collate";
	$nom = $wpdb->prefix . 'geonames';
	if($wpdb->get_var("SHOW TABLES LIKE '$db_table_name'")!=$nom)
		{
		$sql = "CREATE TABLE ".$nom." (
			`idwpgn` bigint(20) unsigned NOT NULL auto_increment,
			`geonameid` bigint(20) unsigned NOT NULL UNIQUE,
			`name` varchar(200) NOT NULL,
			`asciiname` varchar(200) NOT NULL,
			`alternatenames` text,
			`latitude` decimal(10,5) NOT NULL,
			`longitude` decimal(10,5) NOT NULL,
			`feature_class` char(1) NOT NULL,
			`feature_code` varchar(10) NOT NULL,
			`country_code` varchar(2) NOT NULL,
			`cc2` varchar(60) NOT NULL,
			`admin1_code` varchar(20) NOT NULL,
			`admin2_code` varchar(80) NOT NULL,
			`admin3_code` varchar(20) NOT NULL,
			`admin4_code` varchar(20) NOT NULL,
			`population` bigint unsigned NOT NULL,
			`elevation` int NOT NULL,
			`dem` smallint unsigned NOT NULL,
			`timezone` varchar(40) NOT NULL,
			`modification_date` date NOT NULL,
			PRIMARY KEY (`idwpgn`)
			) $charset_collate;";
		dbDelta($sql);
		}
	}
//
if(is_admin())
	{
	load_plugin_textdomain('wpGeonames', false, dirname(plugin_basename( __FILE__ )).'/lang/'); // language
	add_action('admin_menu','wpGeonames');
	}
function wpGeonames()
	{
	add_options_page('WP GeoNames Options', 'WP GeoNames', 'manage_options', 'wpGeonames-options', 'wpGeonames_admin');
	}
function wpGeonames_admin()
	{
	if(!is_admin()) die();
	global $wpdb;
	$url = 'http://download.geonames.org/export/dump/';
	if (isset($_POST['wpGeonamesAdd'])) echo '<p style="font-weight:700;color:#D54E21;">'.wpGeonames_addZip($url,$_POST).'</p>';
	else if (isset($_POST['wpGeonamesClear'])) echo '<p style="font-weight:700;color:#D54E21;">'.wpGeonames_clear().'</p>';
	$page = file_get_contents($url);
	$q = preg_split("/href/i", $page); $r1 = array();
	foreach($q as $r)
		{
		if(strpos($r,".zip")!==false) $r1[] = substr($r,2,strpos($r,".zip")+2);
		}
	?>
	
	<div class='wrap'>
		<div class='icon32' id='icon-options-general'><br/></div>
		<div style="max-width:800px;">
			<a style="float:right;" href="http://www.geonames.org/"><img src="<?php echo plugins_url('wp-geonames/images/geonames.png'); ?>" alt="GeoNames" title="GeoNames" /></a>
		</div>
		<h2>WP GeoNames</h2>
		<p>
		<?php _e('This plugin allows to insert into the database the millions of places available free of charge on the GeoNames website.', 'wpGeonames'); ?>
		</p>
		<?php
		$q = $wpdb->get_results("SELECT DISTINCT country_code FROM ".$wpdb->prefix."geonames ORDER BY country_code");
		$cc = '';
		if($q)
			{
			foreach($q as $r)
				{
				$cc .= $r->country_code.' (<span style="color:#D54E21;">'.$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."geonames WHERE country_code='".$r->country_code."' ").'</span>)&nbsp;&nbsp;';
				}
			}
		echo '<p>'.__('Number of data in this database', 'wpGeonames').' : <span style="font-weight:700;color:#D54E21;">'.$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."geonames").'</span></p>';
		echo '<p>'.__('List of countries in this database', 'wpGeonames').' : <span style="font-weight:700;font-size:11px;">'.$cc.'</span></p>';
		?>
		<hr />
		<form method="post" name="wpGeonames_options1" action="options-general.php?page=wpGeonames-options">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label><?php _e('Add data to WordPress', 'wpGeonames'); ?></label></th>
					<td>
						<select name="wpGeonamesAdd" id="wpGeonamesAdd">
						<?php
						foreach($r1 as $r2)
							{
							echo '<option value="'.$r2.'">'.$r2.'</option>';
							} ?>
						</select>
					</td>
					<td></td>
					</td><td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php _e('Choose columns to insert', 'wpGeonames'); ?></label></th>
					<td style="width:250px;">
						<input type="checkbox" name="wpGeo0" value="1" checked disabled /><span style="color:#bb2;"><?php _e('ID', 'wpGeonames'); ?></span><br>
						<input type="checkbox" name="wpGeo1" value="1" checked disabled /><span style="color:#bb2;"><?php _e('Name', 'wpGeonames'); ?></span><br>
						<input type="checkbox" name="wpGeo2" value="1" checked disabled /><span style="color:#bb2;"><?php _e('Ascii Name', 'wpGeonames'); ?></span><br>
						<input type="checkbox" name="wpGeo3" value="1" checked><?php _e('Alternate Names', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo4" value="1" checked><?php _e('Latitude', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo5" value="1" checked><?php _e('Longitude', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo6" value="1" checked disabled><span style="color:#bb2;"><?php _e('Feature Class', 'wpGeonames'); ?></span><br>
					</td><td>
						<input type="checkbox" name="wpGeo7" value="1" checked disabled><span style="color:#bb2;"><?php _e('Feature Code', 'wpGeonames'); ?></span><br>
						<input type="checkbox" name="wpGeo8" value="1" checked disabled /><span style="color:#bb2;"><?php _e('Country Code', 'wpGeonames'); ?></span><br>
						<input type="checkbox" name="wpGeo9" value="1" checked><?php _e('Country Code2', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo10" value="1" checked><?php _e('Admin1 Code', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo11" value="1" checked><?php _e('Admin2 Code', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo12" value="1" checked><?php _e('Admin3 Code', 'wpGeonames'); ?><br>
					</td><td>
						<input type="checkbox" name="wpGeo13" value="1" checked><?php _e('Admin4 Code', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo14" value="1" checked><?php _e('Population', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo15" value="1" checked><?php _e('Elevation', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo16" value="1" checked><?php _e('Digital Elevation Model', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo17" value="1" checked><?php _e('Timezone,', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeo18" value="1" checked><?php _e('Modification Date', 'wpGeonames'); ?><br>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label><?php _e('Choose type of data to insert', 'wpGeonames'); ?></label></th>
					<td style="width:250px;">
						<input type="checkbox" name="wpGeoA" value="1" checked /><?php _e('A : country, state, region', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeoH" value="1" checked /><?php _e('H : stream, lake', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeoL" value="1" checked /><?php _e('L : parks,area', 'wpGeonames'); ?><br>
					</td><td>
						<input type="checkbox" name="wpGeoP" value="1" checked /><?php _e('P : city, village', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeoR" value="1" checked /><?php _e('R : road, railroad', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeoS" value="1" checked /><?php _e('S : spot, building, farm', 'wpGeonames'); ?><br>
					</td><td>
						<input type="checkbox" name="wpGeoT" value="1" checked /><?php _e('T : mountain,hill,rock', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeoU" value="1" checked /><?php _e('U : undersea', 'wpGeonames'); ?><br>
						<input type="checkbox" name="wpGeoV" value="1" checked /><?php _e('V : forest,heath', 'wpGeonames'); ?><br>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Add','wpGeonames') ?>" />
			</p>
		</form>
		<hr />
		<form method="post" name="wpGeonames_options2" action="options-general.php?page=wpGeonames-options">
			<input type="hidden" name="wpGeonamesClear" value="1" />
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Clear this table (TRUNCATE)','wpGeonames') ?>" />
			</p>
		</form>
		<?php _e('To know how to use the data, look at the readme.txt file.', 'wpGeonames'); ?>
	</div>
	<?php
	}
function wpGeonames_addZip($url,$f)
	{
	if(!is_admin()) die();
	$upl = wp_upload_dir();
	if (!is_dir($upl['basedir'].'/zip/')) mkdir($upl['basedir'].'/zip/');
	// 1. Get ZIP from URL - Copy to uploads/zip/ folder
	if (!copy($url.$f['wpGeonamesAdd'], $upl['basedir'].'/zip/'.$f['wpGeonamesAdd'])) { _e('Failure in the download of the zip.','wpGeonames'); die(); }
	// 2. Extract ZIP in uploads/zip/
	$zip = new ZipArchive;
	if ($zip->open($upl['basedir'].'/zip/'.$f['wpGeonamesAdd'])===TRUE)
		{
		$zip->extractTo($upl['basedir'].'/zip/');
		$zip->close();
		}
	else { _e('Failure in the extraction of the zip.','wpGeonames'); die(); }
	// 3. Read file and put data in array
	$data = file_get_contents($upl['basedir'].'/zip/'.substr($f['wpGeonamesAdd'],0,strlen($f['wpGeonamesAdd'])-4).'.txt');
	$data = str_replace("\r\n", "\n", $data);
	$d = explode("\n", $data);
	// 4. Store data in DB
	$e = array(); $g = ''; $c = 0;
	$fe = array();
	if(isset($f['wpGeoA'])) $fe[] = "A";
	if(isset($f['wpGeoH'])) $fe[] = "H";
	if(isset($f['wpGeoL'])) $fe[] = "L";
	if(isset($f['wpGeoP'])) $fe[] = "P";
	if(isset($f['wpGeoR'])) $fe[] = "R";
	if(isset($f['wpGeoS'])) $fe[] = "S";
	if(isset($f['wpGeoT'])) $fe[] = "T";
	if(isset($f['wpGeoU'])) $fe[] = "U";
	if(isset($f['wpGeoV'])) $fe[] = "V";
	foreach($d as $k=>$v)
		{
		$b = 0;
		$v = str_replace("'","''",$v);
		$v = str_replace('"',' ',$v);
		$e = explode("\t", $v);
		if($e[0] && in_array($e[6], $fe))
			{
			++$c;
			$g .= '("'.$e[0].'","'.$e[1].'","'.$e[2].'","'.(isset($f['wpGeo3'])?$e[3]:'').'","'.(isset($f['wpGeo4'])?$e[4]:'').'","'.(isset($f['wpGeo5'])?$e[5]:'').'","'.$e[6].'","'.$e[7].'","'.$e[8].'","'.(isset($f['wpGeo9'])?$e[9]:'').'","'.(isset($f['wpGeo10'])?$e[10]:'').'","'.(isset($f['wpGeo11'])?$e[11]:'').'","'.(isset($f['wpGeo12'])?$e[12]:'').'","'.(isset($f['wpGeo13'])?$e[13]:'').'","'.(isset($f['wpGeo14'])?$e[14]:'').'","'.(isset($f['wpGeo15'])?$e[15]:'').'","'.(isset($f['wpGeo16'])?$e[16]:'').'","'.(isset($f['wpGeo17'])?$e[17]:'').'","'.(isset($f['wpGeo18'])?$e[18]:'').'"),';
			}
		if($c>5000)
			{
			wpGeonames_addDb($g);
			$c = 0; $g = '';
			}
		}
	wpGeonames_addDb($g);
	@unlink($upl['basedir'].'/zip/'.substr($f['wpGeonamesAdd'],0,strlen($f['wpGeonamesAdd'])-4).'.txt');
	@unlink($upl['basedir'].'/zip/'.$f['wpGeonamesAdd']);
	return __('Done, data are in base.', 'wpGeonames');
	}
function wpGeonames_addDb($g)
	{
	if(!is_admin()) die();
	global $wpdb;
	$wpdb->query("INSERT IGNORE INTO ".$wpdb->prefix."geonames
		(geonameid,
		name,
		asciiname,
		alternatenames,
		latitude,
		longitude,
		feature_class,
		feature_code,
		country_code,
		cc2,
		admin1_code,
		admin2_code,
		admin3_code,
		admin4_code,
		population,
		elevation,
		dem,
		timezone,
		modification_date) 
		VALUES".substr($g,0,strlen($g)-1));
	}
function wpGeonames_clear()
	{
	if(!is_admin()) die();
	global $wpdb;
	$q = $wpdb->query("TRUNCATE TABLE ".$wpdb->prefix."geonames");
	if($q) return __('Done, table is empty.', 'wpGeonames');
	else return __('Failed !', 'wpGeonames');
	}
?>