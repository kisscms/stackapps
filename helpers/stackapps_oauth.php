<?php
// FIX - to include the base OAuth lib not in alphabetical order
include_once( realpath("../") . "/app/plugins/oauth/helpers/kiss_oauth.php" );

/* Discus for KISSCMS */
class Stackapps_OAuth extends KISS_OAuth_v2 {
	
	function  __construct( $api="stackapps", $url="https://stackexchange.com/oauth" ) {

		$this->url = array(
			'authorize' 		=> $url ."", 
			'access_token' 		=> $url ."/access_token", 
			'refresh_token' 	=> $url ."/refresh_token"
		);
		
		$this->redirect_uri = url("/oauth/api/". $api);
		
		$this->client_id = $GLOBALS['config'][$api]['client_id'];
	 	$this->client_secret = $GLOBALS['config'][$api]['secret'];
		
	}
	
	// additional params not covered by the default OAuth implementation
	public function access_token( $code, $params=array() ){
		parent::access_token($code, $params);
	}
	
	public function refresh_token($params=array()){
		
		$request = array(
			"params" => array( "grant_type" => "refresh_token" )
		);
		
		parent::refresh_token($params);
	}
	
	function save( $response ){
		
		// erase the existing cache
		$stackapps = new Stackapps();
		$stackapps->deleteCache();
		
		// convert string into an array
		parse_str( $response, $auth );
		
		// save to the user session 
		$_SESSION['oauth']['stackapps'] = $auth;
		
	}
	
}