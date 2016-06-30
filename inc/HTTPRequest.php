<?php

abstract class HTTPRequest
{
	protected $url = '';  // http://domain.org/
	protected $urlpath = ''; // blabla/blab/bla
	protected $query = array(); // ?arg1=skl&arg2=blabla...
	public $history_curlopts = array();
	protected $curlOpts = array(); // add curl options here

	protected function ReSendRequest()
	{
		exit(print_r($this->history_curlopts, true));
		return $this->sendRequest(end($this->history_curlopts));
	}
	
	protected function sendRequest()
	{
		$curlopts = @func_get_arg(0) ? func_get_arg(0) : array();
// 		if(is_int($curlopts) && $curlopts <= 0)
// 			$curlopts = $this->history_curlopts[count($this->history_curlopts) + $curlopts];
// 		else
// 			$this->history_curlopts[] = (array)$curlopts;
			
		$url = 	(substr($this->url, -1)=='/' ? $this->url : $this->url.'/').
			(substr($this->urlpath, 0, 1)=='/' ? substr($this->urlpath, 1) : $this->urlpath).
			'?'.
			http_build_query($this->query);
			
// 		print $url;
		$ch = curl_init();
		
// 		curl_setopt($ch, CURLOPT_URL, $url);
// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
// 		curl_setopt($ch, CURLOPT_HEADER, FALSE);
// 		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json", 'Accept-encoding: gzip'));
		
		$header = array("Accept: application/json", 'Accept-encoding: gzip');
		if(@$this->curlOpts[CURLOPT_HTTPHEADER])
			$header = array_merge($header, $this->curlOpts[CURLOPT_HTTPHEADER]);
		if(@$curlopts[CURLOPT_HTTPHEADER])
			$header = array_merge($header, $curlopts[CURLOPT_HTTPHEADER]);

// 		print_r($header);
		
		curl_setopt_array($ch, $arr = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false,
			CURLOPT_HTTPHEADER => $header
			) + $this->curlOpts + $curlopts
		);
//		print_r($arr);
// 		curl_setopt_array($ch, $this->curlOpts);
// 		curl_setopt_array($ch, $curlopts);
// 		print_r($curlopts);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		$response = curl_exec($ch);
		
		//print_r( curl_getinfo($ch) );
// 		print_r($header);
// 		print_r($curlopts);

		curl_close($ch);

// 		var_dump(json_decode($response));
// 		exit();
		//DEBUG
		//return json_decode(file_get_contents('./tmdbemulator.txt'));
		if(@gzdecode($response))
			$response = gzdecode($response);
// 		print $this->urlpath.'=>'.$response."\n";
		return json_decode($response);
	}
		
	protected function setUrl($url)
	{
		$this->url = $url;
		return $this;
	}
	
	protected function setCurlOpts($opts)
	{
		$this->curlOpts = (array)$opts;
		return $this;
	}
	
	protected function clearCurlOpts($key = null)
	{
		if($key == null)
			$this->curlOpts = array();
		else
			unset($this->curlOpts[$key]);
		return $this;
	}
	
	protected function addCurlOpts($opts)
	{
		$this->curlOpts += (array)$opts;
		return $this;
	}
	
	protected function addCurlOpt($opts)
	{
		return $this->addCurlOpts($opts);
	}
	
	protected function setCurlOpt($p)
	{
		return $this->setCurlOpts($p);
	}
	
	//alias of setPath()
	protected function setUrlPath($path)
	{
		return $this->setPath($path);
	}
	
	protected function setPath($path)
	{
		$this->urlpath = $path;
		return $this;
	}
	
	protected function addQuery($key, $value)
	{
		$this->query[$key] = $value;
		return $this;
	}
}