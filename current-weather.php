<?php
/**
 * Plugin Name: Current Weather
 * Plugin URI: http://mikemattner.com
 * Description: Displays current weather using Yahoo! Weather API.
 * Version: 1.5
 * Author: Mike Mattner
 * Author URI: http://www.mikemattner.com/
 * Tags: weather, sidebar widget
 * License: GPL
 
=====================================================================================
Copyright (C) 2011 Mike Mattner

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
=====================================================================================
*/

/*
  * Yahoo! Weather API provides this as a sample; our query is based on this.
  * ------------------------------------------------------------------------------------------
  * For the Weather RSS feed there are two parameters:
  *   -w for WOEID.
  *   -u for degrees units (Fahrenheit or Celsius).
  * The WOEID parameter w is required. Use this parameter to indicate the location for the weather forecast as a WOEID.
  *
  * http://weather.yahooapis.com/forecastrss?w=<?php $woeid ?>&u=<?php $units ?>
  *
  * To Get the WOEID access this:
  *
  * http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20geo.places%20where%20text%3D%22[ YOUR LOCATION ]%22&format=xml
  *
*/

define( 'PLUGIN_PATH', dirname( __FILE__ ) );
define( 'IMAGE_PATH', WP_PLUGIN_URL . '/current-weather/assets/images/');
define( 'CSS_DIR', PLUGIN_PATH . '/assets/css/' );
define( 'DEFAULT_CSS', 'weather-default.css' );
define( 'WIDGET_CSS', 'weather.css' );

require("includes/class.wp_current_weather.php"); //Widgets

class WPCW_Weather {

    static $instance;
	
    public function __construct() {
		self::$instance = $this;
		$this->init();
	}

	public function init() {
	    isset($_REQUEST['_wp_wpcw_nonce']) ? add_action('admin_init',array($this,'wpcw_options_save')) : null;
        
		add_filter( 'plugin_action_links', array($this,'wpcw_plugin_action_links'), 10, 2 );
		
		add_action( 'widgets_init', array($this,'init_wpcw') );
		add_action( 'wp_head', array($this,'wpgcw_css') );
		add_action( 'admin_menu',array($this,'wpcw_options_menu') );
        
		register_activation_hook(__FILE__, array($this,'wpcw_activate'));
		
		add_shortcode( 'current_weather', array($this,'wpcw_shortcode') );
		
	}
	
	//Activate
	public function wpcw_activate() {
		$this->wpgcw_default_css();
	}
	
	//Add settings option on plugins page
	public function wpcw_plugin_action_links($links, $file) {
	    $plugin_file = basename(__FILE__);
	    if (basename($file) == $plugin_file) {
		    $settings_link = '<a href="options-general.php?page=current-weather-options">'.__('Settings', 'current-weather').'</a>';
		    array_unshift($links, $settings_link);
	    }
	    return $links;
    }

	// Register 'Current Weather' widget
	public function init_wpcw() { 
    	 return register_widget('wp_current_weather'); 
	}
    
	//Create Default Widget CSS
	public function wpgcw_default_css() {
	   if ( !get_option('wpcw_custom_css') ) {
	      $default_css = CSS_DIR . DEFAULT_CSS;
	      $new_css     = CSS_DIR . WIDGET_CSS;
	      ob_start();
	      @include( $default_css );
	      $css = ob_get_contents();
	      ob_end_clean();
	      
		  $css = stripslashes ( $css );
		  
	      update_option('wpcw_css', $css);
	      file_put_contents($new_css, $css);
	   }
	   
	}
	
	//Create Custom Widget CSS
	public function wpgcw_custom_css($css) {
	   $new_css = CSS_DIR . WIDGET_CSS;
	   $css     = stripslashes ( $css );
	   file_put_contents($new_css, $css);
	}
	
	//Custom Widget CSS
	public function wpgcw_css() {
	     if ( get_option('wpcw_custom_css') == 'true' ){
		   $filename = WIDGET_CSS; 
		 } else {
		   $filename = DEFAULT_CSS;
		 }
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'. WP_PLUGIN_URL . '/current-weather/assets/css/'.$filename.'"/>';	
	}

	
	/* ================================ */
	/* === CURRENT WEATHER SETTINGS === */
	/* ================================ */

	/*
	 * Current Weather Admin Options Save
	 */
	public function wpcw_options_save() {
		if(wp_verify_nonce($_REQUEST['_wp_wpcw_nonce'],'current-weather')) {
			if ( isset($_POST['submit']) ) {
				( function_exists('current_user_can') && !current_user_can('manage_options') ) ? die(__('Cheatin&#8217; uh?', 'current-weather')) : null;
				//isset($_POST['wpcw_app_id'])     ? update_option('wpcw_app_id', strip_tags($_POST['wpcw_app_id']))  : update_option('wpcw_app_id', '');
				
				isset($_POST['wpcw_custom_css']) ? update_option('wpcw_custom_css', 'true')                         : update_option('wpcw_custom_css', 'false');
				isset($_POST['wpcw_css'])        ? update_option('wpcw_css', stripslashes ( strip_tags($_POST['wpcw_css'] ) ))        : update_option('wpcw_css', '');
                
				if (isset($_POST['wpcw_css'])) {
				  $this->wpgcw_custom_css($_POST['wpcw_css']);
				}
				
			}
		}
	}

	/*
	 * Current Weather Options Page
	 */
	public function wpcw_options_page() {
	   	/*if (get_option('wpcw_app_id') == '') { ?>
				<div id="message" class="error fade"><p><a href="https://developer.apps.yahoo.com/wsregapp/">Sign up here</a> to get your Yahoo! Application ID. This is required to use this plugin.</strong></p></div>
		<?php }*/
		if ( !empty($_POST) ) { ?>
			<div id="message" class="updated fade"><p><strong><?php _e('Options saved.', 'current-weather') ?></strong></p></div>
		<?php } ?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e('Current Weather Options', 'current-weather'); ?></h2>
			<form action="" method="post" id="wpcw-options">
			    <?php /*<h3><?php _e('Yahoo! Developer Network Application ID','current-weather'); ?></h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="wpcw_app_id"><?php _e('Application ID', 'current-weather'); ?></label></th>
							<td>
								<input type="text" class="regular-text" value="<?php if ( get_option('wpcw_app_id') != '' ) echo get_option('wpcw_app_id'); ?>" id="wpcw_app_id" name="wpcw_app_id"/>
							</td>
						</tr>
					</tbody>
				</table> */ ?>
				<h3><?php _e('Style Settings','current-weather'); ?></h3>
				<table class="form-table">
					<tbody>
					    <tr>
							<th scope="row"><?php _e('Use Custom CSS?', 'current-weather'); ?></th>
							<td><label><input name="wpcw_custom_css" id="wpcw_custom_css" value="true" type="checkbox" <?php if ( get_option('wpcw_custom_css') == 'true' ) echo ' checked="checked" '; ?> /> &mdash; <?php _e('Check if you want to use custom css.', 'current-weather'); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><label for="wpcw_css"><?php _e('Custom CSS', 'current-weather'); ?></label></th>
							<td>
								<textarea style="background:#F9F9F9;font-family: Consolas,Monaco,monospace;font-size: 12px; outline: medium none; width:80%; height:400px;" id="wpcw_css" name="wpcw_css" cols="10" rows="8"><?php if ( get_option('wpcw_css') != '' ) echo get_option('wpcw_css'); ?></textarea>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<?php wp_nonce_field('current-weather','_wp_wpcw_nonce'); ?>
					<?php submit_button( __('Save Changes', 'current-weather'), 'button-primary', 'submit', false ); ?>
				</p>
			</form>
			
		</div>
	<?php
	}
	
	/*
	 * Add Options Page to Settings menu
	 */
	public function wpcw_options_menu() {
		if(function_exists('add_submenu_page')) {
			add_options_page(__('Current Weather', 'current-weather'), __('Current Weather', 'current-weather'), 'manage_options', 'current-weather-options', array($this,'wpcw_options_page'));
		}
	}
        
	/**
      * Add function to load weather shortcode.
      * @since 0.4
    */
    public function wpcw_shortcode($atts, $content = null) {
	    extract(shortcode_atts(array(
	    	"location" => '10011',
		    "units" => 'f',
			"show" => true,
			"forecast" => true
		    ), $atts)
	    );
	    $wp_current_weather = new wp_current_weather();
	    ob_start();
	    $wp_current_weather->buildWidget($location,$units,$show,$forecast);
	    $output = ob_get_contents();
	    ob_end_clean();
	    return $output;
    }
    
    	
}

$WPCW = new WPCW_Weather;
?>