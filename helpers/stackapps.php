<?php
/* Discus for KISSCMS */
class Stackapps {

	public $name = "stackapps";
	public $key;
	public $secret;
	public $token;
	public $refresh_token;
	public $api;
	public $me;
	public $oauth;
	private $cache;

	function  __construct() {

		$this->api = "https://stackapps.com/api/3.0/";

		$this->key = $GLOBALS['config']['stackapps']['key'];
	 	$this->secret = $GLOBALS['config']['stackapps']['secret'];

		$this->me = ( empty($_SESSION['oauth']['stackapps']['user_id']) ) ? false : $_SESSION['oauth']['stackapps']['user_id'];

		$this->token = ( empty($_SESSION['oauth']['stackapps']['access_token']) ) ? false : $_SESSION['oauth']['stackapps']['access_token'];
	 	$this->refresh_token = ( empty($_SESSION['oauth']['stackapps']['refresh_token']) ) ? false : $_SESSION['oauth']['stackapps']['refresh_token'];

		$this->cache = $this->getCache();
		// check if we need to refresh the token
		//$expires_in = $_SESSION['oauth']['stackapps']['expires_in']; // seconds
		//if( $expires_in < 500 && $this->refresh_token ){
			//$this->token = $_SESSION['oauth']['stackapps']['access_token'] = $this->refreshToken();
		//}
	}


	function listThread( $id ){
		$url = $this->api ."threads/listPosts.json?api_key=". $this->key ."&thread=".$id;
		$http = new Http();
		$http->execute( $url );
		return ($http->error) ? die($http->error) : json_decode( $http->result);
	}

	function listFollowing(){
		// return the cache under conditions
		if( $this->checkCache("following") ) return $this->cache['following'];

		$url = $this->api ."users/listFollowing.json?access_token=". $this->token ."&api_key=". $this->key ."&api_secret=". $this->secret ."&user=".$this->me;

		$http = new Http();
		$http->execute( $url );
		$result = ($http->error) ? die($http->error) : json_decode( $http->result);
		$this->setCache( array("following" => $result) );
		return $result;

	}

	function listPosts( $user, $limit ){
		$url = $this->api ."users/listPosts.json?api_key=". $this->key ."&user=". $user ."&limit=". $limit ."&related=thread";
		$http = new Http();
		$http->execute( $url );
		//($http->error) ? die($http->error) : $result = json_decode( $http->result);
		return ($http->error) ? die($http->error) : json_decode( $http->result);
	}

	function sendReply($id=0, $message=""){
		$url = $this->api ."posts/create.json";
		$http = new Http();
		$http->setMethod('POST');
		$http->setParams( array(
				"access_token" => $this->token,
				"api_key" => "$this->key",
				"api_secret" => "$this->secret",
				"parent" => $id,
				"message" => $message,
			)
		);
		$http->execute( $url );

		return ($http->error) ? die($http->error) : json_decode( $http->result);

	}

	function createReply( $data ){

			$comment = new Comment( $data->id, "stackapps");
			$user = new User(0, "stackapps");
			$discussion = new Discussion(0 , "stackapps");

			if( !empty($data->thread->id) ) $discussion->set('api_id', $data->thread->id);
			if( !empty($data->thread->title) ) $discussion->set('title', $data->thread->title);
			if( !empty($data->thread->message) ) $discussion->set('content', $data->thread->message);
			if( !empty($data->thread->link) ) $discussion->set('link', $data->thread->link);
			// find the unique id
			$discussion->set('id', $discussion->getID() );

			if( !empty($data->author->id) ) $user->set('api_id', $data->author->id);
			if( !empty($data->author->name) ) $user->set('name', $data->author->name);
			if( !empty($data->author->about) ) $user->set('about', $data->author->about);
			if( !empty($data->author->profileUrl) ) $user->set('link', $data->author->profileUrl);
			if( !empty($data->author->avatar->permalink) ) $user->set('avatar', $data->author->avatar->permalink);
			// find the unique id
			$user->set('id', $user->getID() );

			if( !empty($data->createdAt) ) $comment->set('date', $data->createdAt);
			// we want the comment tripped out from any formatting
			if( !empty($data->message) ) $comment->set('message', strip_tags( htmlspecialchars_decode( $data->message )) );
			if( !empty($data->url) ) $comment->set('link', $data->url);
			// find the unique id
			$comment->set('id', $comment->getID() );
			// link comment with discussion/user
			$comment->set('discussion', $discussion->get("id") );
			$comment->set('user', $user->get("id") );


			$is_following = $this->isFollowing( $user->get("api_id") );
			$is_mine = $this->isMine( $user->get("api_id") );
			// check if following the discussion
			$discussion->set('is_following', ( $is_following || $is_mine ) );
			// check if following the user
			$user->set('is_following', $is_following );
			// check if the comment ownership
			$comment->set('is_mine', $is_mine );


			// compile entry
			$reply = array(
				"id" => $comment->get("id"),
				"comment" => $comment->rs,
				"user" => $user->rs,
				"discussion" => $discussion->rs,
			);

			return $reply;
	}


	function getCache(){
		// set up the parent container, the first time
		if( !array_key_exists("stackapps", $_SESSION) ) $_SESSION['stackapps']= array();
		return $_SESSION['stackapps'];

	}

	function setCache( $data ){
		// save the data in the session
		foreach( $data as $key => $result ){
			$_SESSION['stackapps'][$key] = $result;
		}
		// update the local variable
		$this->cache = $this->getCache();
	}

	function checkCache( $type ){
		// always discard cache on debug mode
		if( DEBUG ) return false;

		if( !empty($this->cache[$type]) ) {
			// check the date
			$valid = true;
		}

		return ( $valid ) ? true : false;
	}

	function deleteCache(){
		unset($_SESSION['stackapps']);
	}


}