<?php
session_start();
include('config.php');
include('functions.php');

if(!isset($_POST['shoppingCart'])) {
	$_POST['shoppingCart']  = json_encode($_SESSION['shoppingCart']);
}


log2(urlencode($_POST['shoppingCart']));
ob_start();
print_r($_POST);	
$bod = ob_get_contents();
ob_end_clean();			

log2($bod);

print doOrderLogic($_POST);

	
function log2($msg){
	file_put_contents('access-order.log',date("Y-m-d H:i:s").': '.$msg."\n",FILE_APPEND);
}

function doOrderLogic($post){
	$ret = "";

	if($post['invoice']){
		$post['orderStatus'] = 'geboekt';
		$post['orderType'] = 'invoice';
	}
	else if ($post['estimate']){
		$post['orderStatus'] = 'offerte';
		$post['orderType'] = 'estimate';	
	}
	
	if(isset($post['orderChangeCode'])) {
		log2('Sending a modified order to the backend...');
		//post cart + orderType + changeCode		
		$post['Person_id'] = 0; //dummy number, since it is required (unused on server side)
		$post['viaSite'] = true;

		$ret = curl_post(BASE_URL_CATERINGSOFTWARE.'/orders', $post);
		log2("returned value from /orders: ".$ret);

	}
	else {
		//first POST to the /persons API
		$savedPerson = curl_post(BASE_URL_CATERINGSOFTWARE.'/persons', $post); //extra fields are automatically removed.
		
		$savedPerson = utf8_encode($savedPerson);
		log2("returned value from /persons: ".$savedPerson);
		
		
		$obj = json_decode($savedPerson);
		
		
		$personId = $obj->Person_id;
	
		log2("Inserted person id: ".$personId);
		$ret = $personId;
		
		
		//fetch the returned PERSON ID.
		$post['Person_id'] = $personId;
		$pieces = explode('-', $post['orderDate']);
	
		$post['viaSite'] = true;
	
		$timePieces = explode('-',$post['orderDateTime']);
	
		$post['orderDate']= $pieces[1].'-'.$pieces[0].'-'.$pieces[2].' '.	
								$timePieces[0].':00'; // change to: MM-dd-yyyy HH:mm:ss
	
		$post['endTime'] = $timePieces[1].':00';
		
		$ret = curl_post(BASE_URL_CATERINGSOFTWARE.'/orders', $post);
		log2("returned value from /orders: ".$ret);
	}
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
        CURLOPT_POSTFIELDS => decodeParamsIntoGetString($post),
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