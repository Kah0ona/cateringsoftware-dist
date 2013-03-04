<?php
include_once('config.php');
include_once('functions.php');


ob_start();
print_r($_POST);	
$bod = ob_get_contents();
ob_end_clean();			

log2($bod);
print doBookingLogic($_POST);

	
function log2($msg){
	file_put_contents('access.log',date("Y-m-d H:i:s").': '.$msg."\n",FILE_APPEND);
}

function doBookingLogic($post){
	$ret = "";

	if($post['option']){
		$post['bookingStatus'] = 'optie';
	}
	else if ($post['submit']){
		$post['bookingStatus'] = 'geboekt';	
	}
	else if ($post['quote']) {
		$post['bookingStatus'] = 'offerte';
	}
	
		//first POST to the /persons API
	$savedPerson = curl_post(BASE_URL_CATERINGSOFTWARE.'/persons', $post); //extra fields are automatically removed.
	
	$savedPerson = utf8_encode($savedPerson);
	$obj = json_decode($savedPerson);
	
	$personId = $obj->Person_id;
	log2("Inserted person id: ".$personId);
	$ret = $personId;
	
	//fetch the return PERSON ID.
	
	$post['workshopExtraIds'] = implode(',',$post['WorkshopExtra_id']);
		
	
	
	//Add this to the booking array, to be posted to /bookings
	//return the result of the booking (ie. booking id)
	$post['Person_id'] = $personId;
	$pieces = explode('-', $post['datum']);
	
	if(!isset($post['WorkshopDate_id'])){
		$post['dateBooked'] = $pieces[1].'-'.$pieces[0].'-'.$pieces[2].' '.$post['deliverOnTime'].':00'; // change to: MM-dd-yyyy HH:mm:ss
	}
	else {
		unset($post['dateBooked']);
	}
	$post['numPersons'] = $post['persons'];
	
	$ret = curl_post(BASE_URL_CATERINGSOFTWARE.'/bookings', $post);
	
	return $ret;
}


/**
 * Send a POST requst using cURL
 * @param string $url to request
 * @param array $post values to send
 * @param array $options for cURL
 * @return string
 */
function curl_post($url, array $post = NULL, array $options = array())
{
    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_POSTFIELDS => decodeParamsIntoGetString($post) ,
		CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT']
    );

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    if( !$result = curl_exec($ch))
    {    
    //    trigger_error(curl_error($ch));
    }
    else {
    	return $result;
    }
    $err = curl_errno($ch);
    
    curl_close($ch);
    return $err;
} 
?>