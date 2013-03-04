<?php
include_once('functions.php');
/*
* $params is an array of key value parameters, that willl be encoded in the get
*/
function fetchWorkshops($params, $returnString=false){
	//fetch workshops from the backend
	$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/workshops?'.decodeParamsIntoGetString($params);
	$jsonString = curl_fetch($workshopUrl);
	//$jsonString = utf8_encode($jsonString);
	if($returnString)
		return $jsonString;
	else 
		return json_decode($jsonString);
}

/*
* $params is an array of key value parameters, that willl be encoded in the get
*/
function fetchBookings($params, $returnString=false){
	//fetch booked days from the backend, starting from day now
	$bookingUrl = BASE_URL_CATERINGSOFTWARE.'/bookings?'.decodeParamsIntoGetString($params);
	$bookingString = curl_fetch($bookingUrl);
	//$bookingString = utf8_encode($bookingString);

	if($returnString)
		return $bookingString;
	else
		return json_decode($bookingString);
}

?>
