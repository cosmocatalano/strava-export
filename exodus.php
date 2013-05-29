<?php

include('export.php');

echo "\nI'm just going to assume you've already read the READ ME file...";
$user_id = readline("\nEnter your Strava User ID: ");
$user_ride = readline("\nEnter your preferred export format (GPX or TCX): ");
$export_type = strtoupper($user_ride);
if ($export_type !== 'GPX' AND $export_type !== 'TCX') {
	exit("\nsorry, I can't do ".$export_type." files. please try again.\n");
}
echo "\nGreat! Gathering a list of your rides now...";

$total_rides = array();
$i = 0;
$j = 1;
for ($i = 0; $j === 1; $i = $i + 50) {
	echo "\n...".($i)." rides gathered...";
	$rides = file_get_contents('http://app.strava.com/api/v1/rides?athleteId='.$user_id.'&offset='.$i);
	$ride_array = json_decode($rides, TRUE);
	if($ride_array['rides'] === array()) {
		echo "..actually, you have just under ".$i." rides.";
		$j = 0;
	}
	foreach ($ride_array['rides'] as $ride) {
		array_push($total_rides, $ride);
	}
	
}
echo "\nGot all your rides! Liberation in progress...";
foreach($total_rides as $ride) {
	echo "\n...liberating ".$ride['name']."...";
	liberate_ride($ride['id'], $export_type);
	echo $echo."done!";
}
echo "\nall done!";

//$empty = file_get_contents('http://app.strava.com/api/v1/rides?athleteId=6561&offset=800');
//print_r(json_decode($empty, TRUE));
	
?>