<?php

/***********************
CREDITS
Strava GPX Export Script
by Cosmo Catalano
http://cosmocatalano.com

/**********************
OVERVIEW
Takes a URL from the social fitness site Strava.com and turns it into a valid GPX file.

/**********************
FUNCTIONS
listed alphabetically
**********************/

//API_CALL
//RETURNS DATA OBJECT FROM STRAVA API
function api_call ($target_id, $target_type) {
	$raw_return = file_get_contents('http://strava.com/api/v1/'.$target_type.'/'.$target_id);
	$array_return = json_decode($raw_return);
	return $array_return;
}

//ARRAY_AVG
//GET AVERAGE OF NON-ZERO VALUES FROM ARRAY
function array_avg($array) {
	$zeros = count(array_keys($array, 0));
	$array_avg = array_sum($array) / (count($array) - $zeros);
	$round_avg = round($array_avg);
	return $round_avg;
}

//CHECK_RESPONSE
//SEE WHAT STRAVA SAYS ABOUT THE API CALL AND ID WE SEND
function check_response($target_stream, $target_id) {
	$url = 'http://app.strava.com/api/v1'.$target_stream.$target_id;
	$http_array = get_headers($url, 1);
	$status = $http_array[Status][1];
	if ($status == 404) {
		echo 'the API endpoint "'.$url.'" returned a 404 error'; 
		exit();
	}
}

//GET_TARGET
//CHECKS USER SUBMITTED URL AND EXTRACTS INFORMATION TO MAKE API CALLS
function get_target($target_url) {
	$parse_array = parse_url($target_url);
	if (strpos($parse_array[host], 'strava.com') === FALSE 
		OR (	strpos($parse_array[path], 'activities') === FALSE 
			AND strpos($parse_array[path], 'segments') === FALSE      
			AND strpos($parse_array[path], 'rides') === FALSE 
			AND strpos($parse_array[path], 'runs') === FALSE 
			AND strpos($parse_array[path], 'rides') === FALSE 
			)
		) {
		echo 'the URL "'.$target_url.'" is not valid';
		exit();
	}
	if ($parse_array[fragment] !== NULL AND (strpos($parse_array[fragment], '|') === FALSE)) {
		$target_type = '/stream/efforts/';
		$target_info ='/efforts/';
		$target_object = 'effort';
		$target_id = $parse_array[fragment];
	}elseif (strpos($parse_array[path], '/segment') === 0) {
		$target_type = '/stream/segments/';
		$target_info = '/segments/';
		$target_object = 'segment';
		$target_id = numerals_only($parse_array[path]);
	}else{
		$target_type = '/streams/';
		$target_info = '/rides/';
		$target_object = 'ride';
		$target_id = numerals_only($parse_array[path]);
	}
	$target_array = array(
		'stream' => $target_type, 
		'id' => $target_id, 
		'info' => $target_info, 
		'object' => $target_object
	);
	return $target_array;
}

//GPS_DATE
//MAKES GPS-FORMAT TIMESTAMP FROM UNIX STAMP. 
function gps_date($epoch) {
	$gps_date = date('Y-m-d', $epoch)."T".date('H:i:s', $epoch)."Z";
	return $gps_date;
}

//GPS_TO_EPOCH
//USING TO MAKE GPS TIME INTO A UNIX STAMP, BECAUSE GPS TIMESTAMP MUNGS UP strtotime(). 
function gps_to_epoch($gps_date) {
	$gps_dirty = array('T', 'Z');
	$epoch_safe = array(' ', '');
	$gps_clean = str_replace($gps_dirty, $epoch_safe, $gps_date);
	$epoch_time = strtotime($gps_clean);
	return $epoch_time;
}

//NUMERALS_ONLY
//BANISHES PESKY NON-NUMERALS TO THE LAND OF WIND AND GHOSTS
function numerals_only($string) {
	$string = preg_replace('/[^0-9,]|,[0-9]*$/','',$string);
	return $string;
} 

//OUTPUT_FILE
//writes data to a filename
function output_file($file_type, $file_id, $contents) {
	$file_string = $file_id.strtolower(".".$file_type);
	$output_file = fopen($file_string, "w");
	fwrite($output_file, $contents);
	fclose($output_file);
	return $file_string;
}

/**********************
OUTPUT FORMAT–SPECIFIC FUNCTIONS
these are what you'll have to modify if you want to add new output formats
**********************/

//ADD_FOOTER
//adds appropriate footer for selected filetype
function add_footer($file_type) {
	if ($file_type === 'GPX') {
		$footer_loc = 'footer_gpx.txt';
	}
	if ($file_type === 'TCX') {
		$footer_loc = 'footer_tcx.txt';
	}
	$footer_source = file_get_contents($footer_loc);
	return $footer_source;
}
	
//ADD HEADER	
//adds appropriate header for selected filetype
function add_header ($file_type, $ride_data_array) {
	$replace_these_array = array('#export_time', '#ride_name', '#ride_time');
	if ($file_type === 'GPX') {
		$header_loc = 'header_gpx.txt';
		$replace_these_array = array(
			'#export_time', 
			'#ride_name', 
			'#ride_time'
		);
		$replace_with_array = array(
			$ride_data_array[export_date], 
			$ride_data_array[ride_name],
			$ride_data_array[ride_date]
		);
	}
	if ($file_type === 'TCX') {
		$header_loc = 'header_tcx.txt';
		$replace_these_array = array(
			'#export_time', 
			'#ride_name', 
			'#ride_time',
			'#total_time',
			'#total_distance',
			'#hr_avg',
			'#hr_max',
			'#cadence'
		);
		
		if ($ride_data_array[hr_avg] == NULL) {
			$replace_hr_avg = NULL;
		}else {
			$replace_hr_avg = "<AverageHeartRateBpm><Value>".$ride_data_array[hr_avg]."</Value></AverageHeartRateBpm>";
		}
		if ($ride_data_array[hr_max] == NULL) {
			$replace_hr_max = NULL;
		}else {
			$replace_hr_max = "<MaximumHeartRateBpm><Value>".$ride_data_array[hr_max]."</Value></MaximumHeartRateBpm>";
		}
		if ($ride_data_array[cadence] == NULL) {
			$replace_cadence = NULL;
		}else {
			$replace_cadence = "<Cadence>".$ride_data_array[cadence]."</Cadence>";
		}
		
		$replace_with_array = array(
			$ride_data_array[export_date], 
			$ride_data_array[ride_name],
			$ride_data_array[ride_date],
			$ride_data_array[total_time],
			$ride_data_array[total_distance],
			$replace_hr_avg,
			$replace_hr_max,
			$replace_cadence,
		);
	}
	
	$header_source = file_get_contents($header_loc);
	$new_header = str_replace($replace_these_array, $replace_with_array, $header_source);
	return $new_header;
}
			
//LOOP_SETUP
//taking the API data and aligning it by timestamp, rather than type.
function loop_setup ($file_type, $target_array) {	
	$api_return = api_call ($target_array[id], $target_array[info]);
	if ($target_array[object] === 'effort') {
		$ride_name = $api_return->$target_array[object]->segment->name;
	}else{
		$ride_name = $api_return->$target_array[object]->name;
	}
	$offset_date = $api_return->$target_array[object]->startDate;
	$utc_offset = $api_return->$target_array[object]->timeZoneOffset;
	$total_time = $api_return->$target_array[object]->elapsedTime;
	$total_dist = $api_return->$target_array[object]->distance;
	
	//TCX FORMAT REQUIRES AVG HR, MAX HR, AVG CADENCE
	if ($file_type === 'TCX' AND $target_array[object] !== 'segment') {
		$stream_return = api_call($target_array[id], $target_array[stream]);
		if ($stream_return->heartrate !== NULL) {
			$hr_avg = array_avg($stream_return->heartrate);
			$hr_max = max($stream_return->heartrate);
		}
		if ($stream_return->cadence !== NULL) {
			$cadence = array_avg($stream_return->cadence);
		}
	}
	
	$start_epoch = gps_to_epoch($offset_date) - $utc_offset;
	$ride_date = gps_date($start_epoch);
	$export_date = gps_date(time()); 
	
	if ($target_array[object] === 'segment') {
		$ride_date = $export_date;
		$total_time = 0;
		$hr_avg = NULL;
		$hr_max = NULL;
		$cadence = NULL;
	}
	$ride_data_array = array(
		'export_date' => $export_date, 
		'ride_name' => $ride_name, 
		'ride_date' => $ride_date, 
		'total_time' => $total_time,
		'total_distance' => $total_dist,
		'hr_avg' => $hr_avg,
		'hr_max' => $hr_max,
		'cadence' => $cadence,
	);
	return $ride_data_array;
}

//DATAPOINT_FORMAT
//puts each timestamp's data into correct format for user-selected file 
function datapoint_format ($file_type, $point_array) {
	if ($file_type === 'GPX') {
		$cad_line = '';
		$hr_line = '';
		$atemp_line = '';
		$extensions_open = '';
		$extensions_close = '';
		if ($point_array[lat]  !== NULL) {
			$trkpt_line = "<trkpt lon=\"".$point_array[lng]."\" lat=\"".$point_array[lat]."\">";
		}else{
			$trkpt_line = "<trkpt lon=\"3.205969\" lat=\"50.677813\">"; //Roubaix Velodrome, if you're curious.
		}						
		$time_line = "<time>".$point_array[time]."</time>";
		$ele_line = "<ele>".$point_array[altitude]."</ele>";
		if ($point_array[cadence]  !== NULL) {
			$cad_line = "<gpxtpx:cad>".$point_array[cadence]."</gpxtpx:cad>";
		}
		if ($point_array[heartrate]  !== NULL) {
			$hr_line = "<gpxtpx:hr>".$point_array[heartrate]."</gpxtpx:hr>";
		}
		if ($point_array[temp]  !== NULL) {
			$atemp_line = "<gpxtpx:atemp>".$point_array[temp]."</gpxtpx:atemp>";
		}
		if (($point_array[cadence]  !== NULL) || ($point_array[heartrate]  !== NULL ) || ($point_array[temp] !== NULL)) {
			$extensions_open = "<extensions><gpxtpx:TrackPointExtension>";
            $extensions_close = "</gpxtpx:TrackPointExtension></extensions>";
        }
        $trkpt_end = "</trkpt>";
        $data_line = $trkpt_line.$time_line.$ele_line.$extensions_open.$cad_line.$hr_line.$atemp_line.$extensions_close.$trkpt_end;
	}
	if ($file_type === 'TCX') {
		$position_line = '';
		$latitude_line = '';
		$longitude_line = '';
		$position_close = '';
		$altitude_line = '';
		$distance_line = '';
		$heartrate_line = '';
		$value_line = '';
		$heartrate_close = '';
		$cadence_line = '';
		$extensions_open = '';
		$watts_line = '';
		$extensions_close = '';
		
		$trackpoint_line = "<Trackpoint>";
		$time_line = "<Time>".$point_array[time]."</Time>";
		if ($point_array[lat] !== NULL) {
			$position_line = "<Position>";
			$latitude_line = "<LatitudeDegrees>".$point_array[lat]."</LatitudeDegrees>";
			$longitude_line = "<LongitudeDegrees>".$point_array[lng]."</LongitudeDegrees>";
			$position_close = "</Position>";
		}
		if ($point_array[altitude] !== NULL) {
			$altitude_line = "<AltitudeMeters>".$point_array[altitude]."</AltitudeMeters>";
		}
		if ($point_array[distance] !== NULL) {
			$distance_line = "<DistanceMeters>".$point_array[distance]."</DistanceMeters>";
		}
		if ($point_array[heartrate] !== NULL) {
			$heartrate_line = "<HeartRateBPM>";
			$value_line = "<Value>".$point_array[heartrate]."</Value>";
			$heartrate_close = "</HeartRateBPM>";
		}
		if ($point_array[cadence] !== NULL) {
			$cadence_line = "<Cadence>".$point_array[cadence]."</Cadence>";
		}
		if ($point_array[watts] !== NULL) {
			$extensions_open = "<Extensions><ns3:TPX>";
			$watts_line = "<ns3:Watts>".$point_array[watts]."</ns3:Watts>";
			$extensions_close = "</ns3:TPX>\n\t\t\t</Extensions>";
		}
		$trackpoint_end = "</Trackpoint>";
        $data_line = $trackpoint_line.$time_line.$position_line.$latitude_line.$longitude_line.$position_close.$altitude_line.$distance_line.$heartrate_line.$value_line.$heartrate_close.$cadence_line.$extensions_open.$watts_line.$extensions_close.$trackpoint_end;
	}
	return $data_line;
}

/***************
END FUNCTIONS
***************/

ini_set('user_agent', '[your app name here]'); //Strava says they like it if you use a user-agent name 

//PHONY USER INPUTS
//these are where your user-provided data go.
$file_type = 'GPX';
$user_url = 'http://app.strava.com/rides/13969368'; 

//PARSING SUBMISSIONS, ASKING FOR API DATA
$target_array = get_target($user_url);

//CHECKING THE RESPONSE FROM STRAVA
check_response($target_array[stream], $target_array[id]);

//SELECTING THE DATA I WANT
$ride_data_array = loop_setup($file_type, $target_array);

//ASSEMBLING A HEADER FOR THE APPROPRIATE FILETYPE
$header = add_header($file_type, $ride_data_array);

//GETTING DATA FOR EACH POINT ON THE STRAVA TARGET
$stream_data = api_call ($target_array[id], $target_array[stream]);

//SETTING INITIAL VALUES FOR THE LOOP
$trackpoints = '';
$start_epoch = gps_to_epoch($ride_data_array[ride_date]);
if ($target_array[object] === 'segment') {  				//segments have no time stamps
	$stop_at = count($stream_data->latlng) - 1;
}else{														//stationary rides have no coordinates	
	$stop_at = count($stream_data->time) - 1;
}

//TURNING THE CRANK
for ($i = 0; $i <= $stop_at; $i++) {
	$seconds_time = $stream_data->time[$i];
	$timestamp = gps_date($start_epoch + $seconds_time);
	$altitude = $stream_data->altitude[$i];
	$cadence = $stream_data->cadence[$i];
	$distance = $stream_data->distance[$i];
	$latlng = $stream_data->latlng[$i];
	$heartrate = $stream_data->heartrate[$i];
	$temp = $stream_data->temp[$i];
	if ($stream_data->watts[0] !== FALSE) { 
		$watts = $stream_data->watts[$i];
	}else{
		$watts = $stream_data->watts_calc[$i];
	}

	$loop_result = array(
		'time' => $timestamp, 
		'altitude' => $altitude, 
		'cadence' => $cadence, 
		'distance' => $distance, 
		'lat' => $latlng[0],
		'lng' => $latlng[1],
		'heartrate' => $heartrate,
		'temp' => $temp,
		'watts' => $watts,
	);
	
$data_line = datapoint_format($file_type, $loop_result);
$trackpoints = $trackpoints.$data_line;
}

$footer = add_footer($file_type);
$contents = $header.$trackpoints.$footer;
$new_name = output_file($file_type, $target_array[id], $contents);
echo 'Congratulations - <a href="'.$target_array[id].'.'.strtolower($file_type).'">your file export</a> is ready.';
?>