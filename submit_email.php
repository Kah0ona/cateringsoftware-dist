<?php
include('config.php');
include('functions.php');

print postEmail($_POST);
exit;

function postEmail($post){
	$ret = "";
	//first POST to the /persons API
	$savedPerson = curl_post(BASE_URL_CATERINGSOFTWARE.'/mailinglists', $post); //extra fields are automatically removed.
	
	$savedPerson = utf8_encode($savedPerson);
	$obj = json_decode($savedPerson);
	return $obj;
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