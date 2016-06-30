<?php

if(!class_exists('HTTPRequest'))
	require_once(dirname(__FILE__).'/HTTPRequest.php');
	

class FreeboxApi 
//extends FreeboxApp
extends HTTPRequest
{
	const FREEBOX_URL = 'http://78.228.2.112:80';
	const APP_ID = 'fr.freebox.encoder';
	const APP_TOKEN = 'vONgOt0kdxYxSOTLGuVp7U94IPfDfsJrx3kkE6hjMfgp/dkZPT7dIjhfAQY9CMWv';
	const APP_NAME = 'Encoder';
	const APP_VERSION = '1';
	const DEVICE_NAME = 'romein';
	
	const SESSION_TEMPFILE = 'FREEBOX_SESSION_TEMPFILE';
	
	private $fbxapiversion = -1;
	private $api_base_url = '';
// 	private $logged = false;
	private $appid = '';
	private $token = '';
	private $sessionToken = '';
	private $permissions = array();
		
	public $errorcode = '';
	public $errormsg = '';
	
	public function __construct($url = self::FREEBOX_URL, $appid = self::APP_ID, $token = self::APP_TOKEN, $authorize_mode = false)
	{
		if(is_null($url))
			$url = self::FREEBOX_URL;
		if(is_null($appid))
			$appid = self::APP_ID;
		if(is_null($token))
			$token = self::APP_TOKEN;
		$this->token = $token;
		$this->appid = $appid;
		$this->setUrl($url);
		$box = $this->setUrlPath('api_version')->sendRequest();
// 		print 'V2';
		$this->fbxapiversion = $box->api_version;
		$this->api_base_url = $box->api_base_url;
		$this->setUrl($url . $this->api_base_url .'v'. (int) $this->fbxapiversion);
		$this->addCurlOpt(array(CURLOPT_COOKIESESSION => true));
		
		if(!$authorize_mode)
		{
			if(!$token)
				throw new Exception('Please, give me a token or call FreeboxApi::Authorize(appid, appname, version, device)');
				
			if(!$appid)
				throw new Exception('Please, give me the app.id');
			
			$this->Login();
		}
		return $this;
	}

	public static function __New($url = self::FREEBOX_URL, $appid = self::APP_ID, $token = self::APP_TOKEN, $authorize_mode = false)
	{
		return new self($url, $appid, $token, $authorize_mode);
	}
	
	/*	
	protected function addPost($params, $json = true)
	{
		if(!array_key_exists(CURLOPT_POST, $this->curlOpts))
			$this->addCurlOpt(array(CURLOPT_POST => true));
		$this->addPostFields($params, $json);
		return $this;
	}
	*/
	protected function Post($params, $json = true)
	{
		return $this->sendRequest(
			array(
				CURLOPT_POST => true
			)
			+ $this->PostFields($params, $json)
		);
	}
	
	private function PostFields($params, $json = true)
	{
		return array(CURLOPT_POSTFIELDS => ($json ? json_encode($params) : (array)$params));
	}
	
// 	private function addPostFields($params, $json = true)
// 	{
// 		if(!array_key_exists(CURLOPT_POSTFIELDS, $this->curlOpts))
// 			$this->addCurlOpt(array(CURLOPT_POSTFIELDS => ($json ? json_encode($params) : $params)));
// 		else
// 			$json ? json_encode(array_merge(json_decode($this->curlOpts[CURLOPT_POSTFIELDS]), $params))
// 			 : array_merge(json_decode($this->curlOpts[CURLOPT_POSTFIELDS]), $params);
// 		return $this;
// 	}
	
	protected function Put($params, $json = true)
	{
		return $this->sendRequest(
			array(
				CURLOPT_CUSTOMREQUEST => 'PUT',
			) + $this->PostFields($params, $json)
		);
	}
	
	protected function Delete($param = array(), $json = true)
	{
		return $this->sendRequest(
			array(
				CURLOPT_CUSTOMREQUEST => 'DELETE'
			) + ($param ? $this->PostFields($param, $json) : array())
		);
	}
	
	protected function Login()
	{
		//try to recover last used session.
		if($sess = $this->getSession())
			return $this->addCurlOpt(array(
				CURLOPT_HTTPHEADER => array('X-Fbx-App-Auth: '.($this->sessionToken = $sess))
				));
		
		
		$c = $this->handleErrors($this->setPath('login')->sendRequest()) 
			->challenge;
			
		
		$pass = hash_hmac('sha1', $c, $this->token);
		
		$R = $this->handleErrors(
			$this	->setUrlPath('login/session')
				->Post(array('app_id' => $this->appid, 'password' => $pass))
			);
// 		exit(print_r($R, true));
		$this->sessionToken = $R->session_token;
		$this->permissions = $R->permissions;
		
		$this->addCurlOpts(array(
			CURLOPT_HTTPHEADER => array("X-Fbx-App-Auth: $this->sessionToken")
			));
		
		$this->saveSession();
		return $R;
	}
	
	private function saveSession()
	{
		file_put_contents(sys_get_temp_dir().'/'.self::SESSION_TEMPFILE, $this->sessionToken);
		return true;
	}
	
	private function getSession()
	{
		if(is_readable(sys_get_temp_dir().'/'.self::SESSION_TEMPFILE))
			return file_get_contents(sys_get_temp_dir().'/'.self::SESSION_TEMPFILE);
		return false;
	}
	
	private function removeSession()
	{
		$this->clearCurlOpts(CURLOPT_HTTPHEADER);
		unlink(sys_get_temp_dir().'/'.self::SESSION_TEMPFILE);
		return $this;
	}
		
	public static function Authorize($appid = self::APP_ID, $appname = self::APP_NAME, $version = self::APP_VERSION, $device = self::DEVICE_NAME)
	{
		$that = new self(null, null, null, true);
		return call_user_func_array(array($that, '_Authorize'), func_get_args());
	}
	
	private function _Authorize($appid = self::APP_ID, $appname = self::APP_NAME, $version = self::APP_VERSION, $device = self::DEVICE_NAME)
	{
		if(!$appname)
			throw new Exception('Please Specify an AppName');
		if(!$version)
			throw new Exception('Please Specify an AppVersion');
		if(!$device)
			throw new Exception('Please Specify a Device Name');
		if(!$appid)
			throw new Exception('Please Specify an AppId');
// 		exit($appid.$appname.$version.$device);
		$this->setUrlPath('login/authorize');
		$R = $this->handleErrors($this->addPost(array(
				'app_id' => $appid,
				'app_name' => $appname,
				'app_version' => $version,
				'device_name' => $device
			))->sendRequest());
		$this->token = $R->app_token;
		print "Vous avez 30 secondes pour valider cette application sur le boitier serveur...";
 		sleep(30);
// 		$this->handleErrors($this
// 			->clearCurlOpts()
// 			->setUrlPath('login/authorize/'.$R->track_id)
// 			->sendRequest());
		file_get_contents('http://mafreebox.freebox.fr/api/v3/login/authorize/'.$R->track_id);
		return $R;
	}
	
	private $reauth_attempts = false;
	protected function handleErrors(stdClass $request)
	{
		if($request->success)
			return @$request->result;
		else
		{		
			if($request->error_code == 'auth_required' && !$this->reauth_attempts)
			{
				//Re logging automatically...
				$url = $this->url;
				$urlpath = $this->urlpath;
				$params = end($this->history_curlopts); // get last options
				
				$this->reauth_attempts = true;
				$this->removeSession()->Login();
				return $this->handleErrors(
					$this->setPath($urlpath)
					->sendRequest($params)
					); // Re-send the last request.
			}
			$this->errorcode = $request->error_code;
			$this->errormsg = $request->msg;
			throw new Exception(print_r($request, true));
		}
		return false;
	}	
	
	public function Logout()
	{
		if($this->sessionToken)
			$this->setUrlPath('login/logout')->sendRequest();
		return $this;
	}
	
	public function Downloads()
	{		
		if($this->sessionToken)
 			return new FreeboxDownloads($this);
// 			return $this->handleErrors(
// 				$this->setPath('downloads/')
// 				->sendRequest());
		return false;
	}
	
	public function __destruct()
	{
// 		if($this->sessionToken)
// 			$this->call('login/logout');
		return true;
	}

}

class FreeboxDownloads extends FreeboxApi
{
	protected $FBX = null;
	public $results = array();
	
	public static $Type = array(
		'bt' => 'Torrent',
		'nzb' => 'Newsgroup download',
		'http' => 'HTTP download',
		'ftp' => 'FTP download');
		
	public static $Status = array(
		'done' 		=> 'Done',
		'seeding' 	=> 'Seeding torrent',
		'downloading' 	=> 'Downloading',
		'stopped' 	=> 'Stopped',
		'checking' 	=> 'Checking',
		'error' 	=> 'Error',
		'starting' 	=> 'Starting',
		'stopping'	=> 'Stopping',
		'queued' 	=> 'Queued',
		'retry' 	=> 'Retry',
		'extracting' 	=> 'Extracting NZB',
		'repairing' 	=> 'Repairing NZB',
	);
	
	public function __construct(FreeboxApi $FBX)
	{
		$this->FBX = $FBX;
// 		print_r($this->FBX);

		$this->results = $this->BuildDownloads(
			$this->FBX->handleErrors(
			$this->FBX	->setUrlPath('downloads/')
					->sendRequest()
			));
		return $this->results;
	}
	
	public function getList()
	{
		return $this->results;
	}
	
	public function Add($file)
	{
		$this->FBX->setPath('downloads/add');
		if(count(func_get_args())>1)
			$file = (array)func_get_args();
		$urllist = array();
		$re = array();
		foreach((array) $file as $f)
		{
			if(preg_match('/^(magnet|http|ftp):/', $f))
				$urllist[] = $f;
			else
			{
				$f = realpath($f);
				if(is_file($f) && is_readable($f) && strrchr($f, '.') == '.torrent')
				{	//upload torrent
					if(class_exists('CURLFILE'))
						$params = array('download_file' => 
							new CURLFILE($f));
					else
						$params = array('download_file' => '@'.$f);
					$re[] = $this->FBX->handleErrors(
						$this->FBX->Post($params, false));
				}
				else
					throw new Exception('Le fichier '.$f.' n\'est pas un fichier valide.');
			}
		}
		if(!empty($urllist))
			foreach($this->FBX->handleErrors(
					$this->FBX->Post(array('download_url_list' => implode("\n", $urllist)), false)
					)->id as $i)
				$re[] = (object) array('id' => $i);
		
		return $this->BuildDownloads($re);	
	}
	
	public function Get($id)
	{
		if(isset($this->results[$id]))
			return $this->results[$id];
		else
			return false;
	}
		
	private function BuildDownloads($result)
	{
		$re = array();
		foreach((array)$result as $r)
		{
			$re[$r->id] = new FreeboxDownload($this->FBX, $r);
		}
		return $re;
	}
	
// 	public function __call($name, $arguments)
// 	{
// 		return call_user_func_array(array($this->FBX, $name), $arguments);
// 	}
// 	
// 	public function __get($name)
// 	{
// 		return $this->FBX->$name;
// 	}
}

class FreeboxDownload extends FreeboxDownloads
{
	public $data = array();
	protected $FBX = null;
	public function __construct(&$FBX, $download)
	{
		$this->FBX = $FBX;
		$this->data = $download;
		return $this;
	}
	
	public function Delete($erase = false, $json=true) // second argument is here to compatibility with strict stds
	{
		$this->FBX->handleErrors(
			$this->FBX->setPath('downloads/'.$this->id .($erase ? '/erase' : ''))
			->Delete());
		return $this;
	}
	
	public function Update($opts)
	{
		$this->data = $this->FBX->handleErrors(
			$this->FBX->setPath('downloads/'.$this->id)->Put($opts)
			);
		return $this;
	}
	
	public function Pause()
	{
		return $this->Update(array('status' => 'stopped'));
	}
	
	public function Resume()
	{
		return $this->Update(array('status' => 'downloading'));
	}
	
	public function Retry()
	{
		return $this->Update(array('status' => 'retry'));
	}
	
	public function Restart()
	{
		return $this->Retry();
	}
	
	public function Priority($level)
	{
		if(is_int($level))
		{
			if($level <= 1) $level = 'low';
			elseif($level >= 3) $level = 'high';
			else $level = 'normal';
		}
		if(is_string($level))
		{
			$level = strtolower($level);
			if(in_array($level, array('high', 'normal', 'low')))
				return $this->Update(array('io_priority' => $level));
		}
		return false;
	}
	
	public function HighPriority()
	{
		return $this->Priority('high');
	}
	
	public function LowPriority()
	{
		return $this->Priority('low');
	}
	
	public function NormalPriority()
	{
		return $this->Priority('normal');
	}
	
	public function Refresh()
	{
		$this->data = $this->FBX->handleErrors($this->FBX->setPath('downloads/'.$this->id)->sendRequest());
		return $this;
	}
	
	private function FormatBytes($B, $D=2)
	{
		$S = 'BkMGTPEZY';
		$F = floor((strlen(trim($B)) - 1) / 3);
		return sprintf("%.{$D}f", (int)$B/pow(1024, $F)).' '.@$S[$F].'B';
	}
	
	public function getSize($formated=true)
	{
		if($formated)
			return $this->FormatBytes((int)$this->size);
		else
			return (int)$this->size;
	}
	
	public function Size($formated = true)
	{
		return $this->getSize($formated);
	}
	
	public function getETA($formated = 'H:i:s')
	{
		if($formated != false)
			return gmdate($formated, (int)$this->eta);
		else
			return (int)$this->eta;
	}
	
	public function ETA($formated = 'H:i:s')
	{
		return $this->getETA($formated);
	}
	
	public function getRemainingTime($formated = 'H:i:s')
	{
		return $this->getETA($formated);
	}
	
	public function RemainingTime($formated = 'H:i:s')
	{
		return $this->getETA($formated);
	}
	
	public function getProgress()
	{
		if((int)$this->rx_pct == 10000)
			return (int)$this->tx_pct / 100;
		return (int)$this->rx_pct / 100;
	}
	
	public function Progress()
	{
		return $this->getProgress();
	}
	
	public function getSpeed($mode = 'down')
	{
		if(in_array(strtolower($mode), array('up', 'upload', 'emit', 'uploading', 'emitting', 'transmitted', 'uploaded', 'transmit', 'transmitting')))
			return $this->FormatBytes($this->tx_rate).'/s';
		return $this->FormatBytes($this->rx_rate).'/s';
	}
	
	public function Speed($mode = 'down')
	{
		return $this->getSpeed($mode);
	}
	
	public function Rate($mode = 'down')
	{
		return $this->getSpeed($mode);
	}
	
	public function DownloadSpeed()
	{
		return $this->getSpeed();
	}
	
	public function UploadSpeed()
	{
		return $this->getSpeed('up');
	}
	
	public function getDownloadSpeed()
	{
		return $this->DownloadSpeed();
	}
	
	public function getUploadSpeed()
	{
		return $this->UploadSpeed();
	}
	
	public function Created($formated = 'd/m/Y H:i:s')
	{
		if($formated != false)
			return date($formated, (int)$this->created_ts);
		return (int)$this->created_ts;
	}
	
	public function CreationDate($formated = 'd/m/Y H:i:s')
	{
		return $this->Created($formated);
	}
	
	public function Type($formated = true)
	{
		if($formated)
			return self::$Type[$this->type];
		return $this->type;
	}
	
	public function Status($formated = true)
	{
		if($formated)
			return self::$Status[$this->status];
		return $this->status;
	}
	
	public function getRatio()
	{
// 		if((int) $this->tx_bytes == 0)	return 0;
		return (int)$this->size != 0 ? round((int)$this->tx_bytes / (int)$this->size, 2) : 0;
	}
	
	public function Ratio()
	{
		return $this->getRatio();
	}
	
	public function getStopRatio()
	{
		return (int)$this->stop_ratio / 100;
	}
	
	public function StopRatio()
	{
		return $this->getStopRatio();
	}
	
	public function FinalRatio()
	{
		return $this->getStopRatio();
	}
	
	public function getFinalRatio()
	{
		return $this->getFinalRatio();
	}
	
	public function getName()
	{
		return (string)$this->name;
	}
	
	public function TorrentName()
	{
		return $this->getName();
	}
	
	public function getTorrentName()
	{
		return $this->getName();
	}
	
	public function Downloaded($formated = true)
	{
		if($formated)
			return $this->FormatBytes($this->rx_bytes);
		return (int)$this->rx_bytes;
	}
	
	public function Received($formated = true)
	{
		return $this->Downloaded($formated);
	}
	
	public function Uploaded($formated = true)
	{
		if($formated)
			return $this->FormatBytes($this->tx_bytes);
		return (int)$this->tx_bytes;
	}
	
	public function Emitted($formated = true)
	{
		return $this->Uploaded($formated);
	}
	
	public function getDownloadDir($decode = true)
	{
		if($decode)
			return base64_decode($this->download_dir);
		return $this->download_dir;
	}
	
	public function DownloadDirectory($decode = true)
	{
		return $this->getDownloadDir($decode);
	}
	
	public function Directory($decode = true)
	{
		return $this->getDownloadDir($decode);
	}
	
	public function __get($name)
	{
		return $this->data->$name;
	}
}

?>