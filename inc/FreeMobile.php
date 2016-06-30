<?php

class FreeMobile
{
	const DEFAULT_USER = 18959836;
	const DEFAULT_PASS = '1ARtoFCnEuWzFZ';
	
	const DEBUG = false;
	const URL = "https://smsapi.free-mobile.fr/sendmsg";
// 	const MODE = 'POST';
	const MODE = 'GET';
	
	private $user, $pass, $fullurl;
	public $errorCode = 0;
	
	public function __construct($user = self::DEFAULT_USER, $pass = self::DEFAULT_PASS)
	{
		if(!in_array('https', stream_get_wrappers()))
			throw new Exception ('Please enable https wrapper by enabling openssl php extension');
			
		$this->setUser($user);
		$this->setPass($pass);
		$this->url = self::URL;
		
		return $this;
	}
	
	public static function __New($user = self::DEFAULT_USER, $pass = self::DEFAULT_PASS)
	{
		return new self($user, $pass);
	}
	
	public function setUser($user)
	{
		$this->user = (int)$user;
		return $this;
	}
	
	public function User($user)
	{
		return $this->setUser($user);
	}
	
	public function setPass($pass)
	{
		$this->pass = trim($pass);
		return $this;
	}
	
	public function Pass($pass)
	{
		return $this->setPass($pass);
	}
	
	public function setPassword($pass)
	{
		return $this->setPass($pass);
	}
	
	public function Password($pass)
	{
		return $this->setPass($pass);
	}
	
	public function send($msg, $mode = self::MODE)
	{
		if(!$this->user || !$this->pass)	throw new Exception('Need a valid user and password');
		$data = http_build_query(
			array(
				'user' => $this->user,
				'pass' => $this->pass,
				'msg' => str_replace('&', '_', trim((string) $msg))
			)
		);
		$f = null;
		switch($mode)
		{
			default:
			case 'POST':
				$context = array(
					'http' => array(
						'method' => 'POST',
						'header' => 
							'Content-type: application/x-www-form-urlencoded',
// 							."\r\n".
// 							"Content-Length: " . strlen($data) 
// 							. "\r\n",
						'content' => $data
					)
				);
				$f = @fopen($this->url, 'r', false, stream_context_create($context));
// 					throw new Exception('Unable to reach url using POST method');
			break;
			case 'GET':
				$context = array(
					'http' => array(
						'method' => 'GET',
// 						'header' => 
// 							'Content-type: application/x-www-form-urlencoded'."\r\n".
// 							"Content-Length: " . strlen($data) . "\r\n",
// 						'contents' => $data
					)
				);
				$f = @fopen($this->url . '?' . $data, 'r', false, stream_context_create($context));
// 					throw new Exception('Unable to reach the url '.$url);
			break;
		}
		// debug : display what sent back
		if(self::DEBUG && $f)
			while (!feof($f)) 
				print fread($f, 8192);
// 		print_r($http_response_header);
		return $this->handleErrors(substr($http_response_header[0], 9, 3));
	}
	
	private function handleErrors($code)
	{
		$this->errorCode = (int)$code;
		switch((int)$code)
		{
			case 200:
				return true;
			break;
			default:
				throw new Exception('Unknown error : '.$code);
			break;
			case 400:
				throw new Exception ('One parameter missing.');
			break;
			case 402:
				throw new Exception ('Too much traffic.');
			break;
			case 403:
				throw new Exception ('Service unavailable, or incorrect user/pass');
			break;
			case 500:
				throw new Exception ('Server Error. Please try again later.');
			break;
		}
		return false;
	}
	
	public function getErrorCode()
	{
		return $this->errorCode;
	}
	
	public function SendMsg($msg, $mode = self::MODE)
	{
		return $this->send;
	}
	
	public function __destruct()
	{
	
	}
}

?>