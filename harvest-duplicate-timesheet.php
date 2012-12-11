<?php

$shortname = ''; // account shortname (xxxxx.harvestapp.com)
$email     = ''; // account email address
$password  = ''; // password, assuming this script is in a safe place
$max_check = 10; // number of past days to search for the last active timesheet

// that's it!


/**
 * get the last used sheet
 */

$days_ago  = 0;
$entries   = 0;

while($entries < 1)
{
	$response    = query_harvest('daily/'.(date('z')-$days_ago).'/'.date('Y'));
	$xml         = new SimpleXMLElement($response);
	$day_entries = $xml->{'day_entries'}[0];
	$entries     = count($day_entries);

	if ($entries == 0) $days_ago++;
	if ($days_ago > $max_check) $entries = 1; // keep this loop from continuing indefinitely
}


/**
 * re-add items from last sheet to today's
 */

foreach ($day_entries[0] as $entry) 
{
	$post_xml = '<request>
  <notes></notes>
  <hours>0</hours>
  <project_id type="integer">'.$entry->{'project_id'}.'</project_id>
  <task_id type="integer">'.$entry->{'task_id'}.'</task_id>
  <spent_at type="date">'.date('D, j M Y').'</spent_at>
</request>
';

	$timer_xml = query_harvest('daily/add', $post_xml);
}


/**
 * get the last timer added and toggle it (by default, it'll be running)
 */

$xml      = new SimpleXMLElement($timer_xml);
$entry_id = $xml->{'day_entry'}->{'id'};
$response = query_harvest('daily/timer/'.$entry_id);


/**
 * simple HTTP query wrapper
 * 
 * @param  string $method API URL segment to pass
 * @param  string $post   post data to be included
 * @return string cURL response
 */

function query_harvest($method, $post = '')
{
	global $shortname, $email, $password;

	$url = 'https://'.$shortname.'.harvestapp.com/'.$method;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);

	if ( ! empty($post)) curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/xml', 
		'Accept: application/xml',
		'Authorization: Basic '.base64_encode($email.':'.$password).''
	)); 

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$response = curl_exec($ch);
	curl_close($ch);

	return $response;
}

?>