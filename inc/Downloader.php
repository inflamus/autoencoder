<?php 

class Downloader
{
	
// 	public static $Handlers =  array(
// 		'FreeboxDownloads' => 'FreeboxHandler',
// 		'TransmissionRPC' => 'TransmissionHandler',
// 	);

	private $handlers = array();
	public function __construct()
	{
		if(!class_exists('TransmissionRPC'))
			require_once(dirname(__FILE__).'/PHP-Transmission-Class-master/class/TransmissionRPC.class.php');
		if(!class_exists('FreeboxDownloads'))
			require_once(dirname(__FILE__).'/FreeboxApi.php');
		return $this;
	}
	
	public function AddHandler($h)
	{
		if	($h instanceof FreeboxDownloads)
				$this->handlers[] = new FreeboxHandler($h);
		elseif	($h instanceof TransmissionRPC)
				$this->handlers[] = new TransmissionHandler($h);
		else
			throw new Exception('Invalid Handler type. Valid are FreeboxDownloads:: and TransmissionRPC::');
		return $this;
	}
	
	public function Add($torrent)
	{
		if(empty($this->handlers))
			throw new Exception('At least one download handler must be specifier by Downloader::AddHandler');
		if(is_array($torrent))
		{
			foreach($torrent as $t)
				if(!$this->Add($t))
					return false;
			return true;
		}
		print ('Adding Torrent '. $torrent . '...'."\n");
		foreach($this->handlers as $h)
			$h->Add($torrent);
		return true;
	}
	
	public static function __New()
	{
		return new self ();
	}
	
}


interface Handler
{
	
	public function Add($file);
	
}

abstract class Handlers 
{

}

class FreeboxHandler extends Handlers implements Handler 
{

	private $H = null;
	public function __construct(FreeboxDownloads $H)
	{
		$this->H = $H;
		return $this;
	}

	public function Add($file)
	{
		return $this->H->Add($file);
	}
}

class TransmissionHandler extends Handlers implements Handler
{

	private $H = null;
	
	public function __construct(TransmissionRPC $H)
	{
		$this->H = $H;
		return $this;
	}
	
	public function Add($file)
	{
		return $this->H->add($file);
	}
}