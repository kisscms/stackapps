<?php


//===============================================
// Configuration
//===============================================

if( class_exists('Config') && method_exists(new Config(),'register')){ 

	// Register variables
	Config::register("stackapps", "client_id", "00");
	Config::register("stackapps", "key", "0000000");
	Config::register("stackapps", "secret", "AAAAAAAAA");

}

?>