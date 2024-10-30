<?php
class wp_current_weather extends WP_Widget {
    
	var $image_dir   = IMAGE_PATH;
    
	/** constructor */
	function wp_current_weather() {
		parent::WP_Widget( 'wp_current_weather', $name = 'Current Weather' );
	}

	/**
	* Update the widget settings.
	*/
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['woeid']      = trim ( $new_instance['woeid'] );
		$instance['units']      = $new_instance['units']['select_value'];
		$instance['location']   = $new_instance['location'];
		$instance['forecast']   = $new_instance['forecast'];
		
		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {
	
		// instance exist? if not set defaults
		    if ( $instance ) {
				$title      = $instance['title'];
		        $woeid      = $instance['woeid'];
		        $units      = $instance['units'];
				$location   = $instance['location'];
				$forecast   = $instance['forecast'];
		    } else {
			    //These are our defaults
				$title      = 'Weather';
		        $woeid      = '';
		        $units      = 'f';
				$location   = true;
				$forecast   = true;
		    }
		
		?>	
		
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __( 'Title:' ); ?></label>
		<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" class="widefat" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'woeid' ); ?>"><?php _e('Location Code:'); ?></label>
		<input id="<?php echo $this->get_field_id( 'woeid' ); ?>" name="<?php echo $this->get_field_name( 'woeid' ); ?>" value="<?php echo $woeid; ?>"  class="widefat" />
		<span style="font-size: 10px;">(e.g. This could be a zip code; or city, state.)</span>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'units' ); ?>"><?php _e('Units:'); ?></label>
		<select id="<?php echo $this->get_field_id( 'units' ); ?>" name="<?php echo $this->get_field_name( 'units' ); ?>[select_value]" class="widefat">
      		<option value="c" <?php if ($units == 'c') echo 'selected'; ?>>Celsius</option>
      		<option value="f" <?php if ($units == 'f') echo 'selected'; ?>>Fahrenheit</option>
    	</select>			
		</p>
		
		<p>
		<input class="checkbox" type="checkbox" <?php if($location == true) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'location' ); ?>" name="<?php echo $this->get_field_name( 'location' ); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'location' ); ?>"><?php _e('Show the location?'); ?></label>
		</p>
		
		<p>
		<input class="checkbox" type="checkbox" <?php if($forecast == true) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'forecast' ); ?>" name="<?php echo $this->get_field_name( 'forecast' ); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'forecast' ); ?>"><?php _e('Display forecast?'); ?></label>
		</p>
        		
	<?php
	}

	/**
	* This is our Widget
	**/
	function widget( $args, $instance ) {
		extract( $args );

		#Our variables from the widget settings
		$title      = apply_filters('widget_title', $instance['title'] );
		$woeid      = $instance['woeid'];
		$units      = $instance['units'];
		$location   = $instance['location'];
		$forecast   = $instance['forecast'];

		#Before widget (defined by themes)
		echo $before_widget;

		#Display the widget title if one was input (before and after defined by themes)
		if ( $title )
			echo $before_title . $title . $after_title;

		#Display name from widget settings if one was input		
		$this->buildWidget($woeid,$units,$location,$forecast);
			
		#After widget (defined by themes)
		echo $after_widget;
	}
	
		
	function getData($woeid,$units) {
		
		$woeid   = str_replace(' ','%20',$woeid);
		
		//Yahoo! GeoLocation Service
		$locationXML   = 'http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20geo.places%20where%20text="'.$woeid.'"&format=xml';
		$location_id   = wp_remote_fopen($locationXML);
		$location_data = simplexml_load_string($location_id);
		$loc_id        = $location_data->results->place->woeid;

        //Yahoo! Weather API		
		$xmlURL = 'http://weather.yahooapis.com/forecastrss?w='.$loc_id.'&u='.$units;
		
		//We need to cache the data for an hour to eliminate some of our API request limitations.
        $cache = PLUGIN_PATH . '/includes/cache/cache-weather-'.$loc_id.'.xml';
        if(!file_exists($cache) || (time() - filemtime($cache)) > 60*15) {
		      //let's grab the XML data
		      $data = wp_remote_fopen($xmlURL);
              file_put_contents($cache, $data);
        }

        $output = file_get_contents($cache);
	 		
		//load resource into a xmldom
		$xmlData = simplexml_load_string($output);
		
		//handle yweather: namespace elements.
		$channel_yweather = $xmlData->channel->children("http://xml.weather.yahoo.com/ns/rss/1.0"); //location information
		$item_yweather    = $xmlData->channel->item->children("http://xml.weather.yahoo.com/ns/rss/1.0"); //current conditions and forecast
		
		foreach($channel_yweather as $x => $channel_item) {
	        foreach($channel_item->attributes() as $k => $attr) {
		       $yw_channel[$x][$k] = $attr;
		    }
		}
		
		$r = -1;
		foreach($item_yweather as $x => $yw_item) {
			foreach($yw_item->attributes() as $k => $attr) {
				if($k == 'day') { $day = $attr; $r++; }
				if($x == 'forecast') { $yw_forecast[$x][$r][$k] = $attr;} 
				else { $yw_forecast[$x][$k] = $attr; }
			}
		}

		//define conditions array
		$conditions = array();
		
		//F and C html
		if($units=='c') {
			$t = '&deg;C';
		} else {
		    $t = '&deg;F';
		}
		
		$conditions['current']['city']        = $yw_channel['location']['city'];
		$conditions['current']['region']      = $yw_channel['location']['region'];
		$conditions['current']['country']     = $yw_channel['location']['country'];
		$conditions['current']['temperature'] = $yw_forecast['condition']['temp'].$t;
		$conditions['current']['conditions']  = $yw_forecast['condition']['text'];
		$conditions['current']['icon']        = $yw_forecast['condition']['code'];
		$conditions['attribution']['link']    = $xmlData->channel->item->link;
		$conditions['current']['test']        = /*$yw_forecast['forecast']*/$locationXML;
		//print_r($conditions['current']['test']);
		
		$i = 0;
		foreach ($yw_forecast['forecast'] as $day) {
            $conditions['forecast'][$i]['day']  = $day['day'];
            $conditions['forecast'][$i]['date'] = $day['date'];			
			$conditions['forecast'][$i]['hi']   = $day['high'];
			$conditions['forecast'][$i]['low']  = $day['low'];
			$conditions['forecast'][$i]['icon'] = $day['code'];
			$conditions['forecast'][$i]['cond'] = $day['text'];
		    $i++;
		}
		
		return $conditions;
		
	}
	
	function buildWidget($woeid,$units,$location,$forecast) {
		$conditions = $this->getData($woeid,$units);
		
		
		$bg = ' icon'.$conditions['current']['icon'];
		
				
		echo '<div class="wp_current_weather">
		      <div class="wpcw_box'.$bg.'">
		      <dl class="clearfix">  
				<dd class="today clearfix">
				  <span class="temperature">'.$conditions['current']['temperature'].'</span>
				  <span class="conditions">'.$conditions['current']['conditions'].'</span>
				</dd>';
				
		/*
		 * Unfortunately, the API doesn't provide a three day forecast.
		 *
		 */
		if($forecast) {
		    
			echo '
				<dd class="day1">
				    <span class="temp">
					<img src="'.$this->image_dir.'icons/31x31/'.$conditions['forecast'][0]['icon'].'.png" alt="'.$conditions['forecast'][0]['cond'].'" title="'.$conditions['forecast'][0]['cond'].'" /><br />
					'.$conditions['forecast'][0]['hi'].'/'.$conditions['forecast'][0]['low'].'
					</span>
					<span class="day">'.$conditions['forecast'][0]['day'].'</span>
				</dd>
				<dd class="day2">
					<span class="temp">
					<img src="'.$this->image_dir.'icons/31x31/'.$conditions['forecast'][1]['icon'].'.png" alt="'.$conditions['forecast'][1]['cond'].'" title="'.$conditions['forecast'][1]['cond'].'" /><br />
					'.$conditions['forecast'][1]['hi'].'/'.$conditions['forecast'][1]['low'].'
					</span>
					<span class="day">'.$conditions['forecast'][1]['day'].'</span> 
				</dd>
			';
		}
				
		echo '
		</dl>
		<div class="clearfix"></div>';
		if($location){
		  $where_am_i = $conditions['current']['city'];
		  if($conditions['current']['region'] != ''){
		     $region = $conditions['current']['region'];
		     $where_am_i .= ', '.$region;
		  }
		  echo '<p class="location"><a href="'.$conditions['attribution']['link'].'" title="Weather data provided by Yahoo! Weather">'.$where_am_i.'</a></p>';
		} else {
		   echo '<p class="weathercom-link"><a href="http://weather.yahoo.com" title="Weather data provided by Yahoo! Weather">Yahoo! Weather</a></p>';
		}
		echo '</div></div>';
		
	}

} // class wp_current_weather

?>