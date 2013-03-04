<?php
	session_start();
	include_once('config.php');
	include_once('fetch.php');
	
	header('Content-Type: application/json;charset=UTF-8');
	
	echo fetchPackages(array(
		'hostname'=> $_GET['hostname'],
		'packageDeal'=>'true',
		'useNesting'=>'false'		
	),true);
?>