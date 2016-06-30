<?php

define('CACHE_DIR', dirname(__FILE__).'/rss_cache/');

class MagnetLink
{
	const ONLY_DHT = true;
	public static $PublicTrackers = array(
		'udp://open.demonii.com:1337',
		'udp://exodus.desync.com:6969',
		'udp://tracker.leechers-paradise.org:6969',
		'udp://tracker.pomf.se',
		'udp://tracker.blackunicorn.xyz:6969',
		'udp://tracker.coppersurfer.tk:6969',
		'udp://tracker.publicbt.com:80',
		'udp://tracker.openbittorrent.com:80',
		'udp://tracker.istole.it:80',
		'udp://tracker.btzoo.eu:80/announce',
		'http://opensharing.org:2710/announce',
		'udp://fr33domtracker.h33t.com:3310',
		'http://announce.torrentsmd.com:8080/announce.php',
		'http://announce.torrentsmd.com:6969/announce',
		'http://bt.careland.com.cn:6969/announce',
		'http://i.bandito.org/announce',
		'http://bttrack.9you.com/announce',
		'udp://trackr.sytes.net:80',
		'udp://tracker.opentrackr.org:1337/announce',
// 		'udp://1.rarbg.me:80/announce',
// 		'udp://2.rarbg.me:80/announce',
// 		'udp://3.rarbg.me:80/announce',
// 		'udp://[...]
// 		'udp://11.rarbg.me:80/announce',
		
	);
	
	const REGEX_HASH = '/[A-F0-9]{40}/i';
	const SCHEME = 'magnet';
	
	public $magnet = '';
	
	public static function __new($hash, $title = null)
	{
		return new self($hash, $title);
	}
	
	public function __construct($hash, $title = null)
	{
		if(!$this->controlHash($hash))
			throw new Exception ('Invalid torrent hash ('.$hash.') for '.$title);
		$this->magnet = self::SCHEME . ":?xt=urn:btih:" . strtoupper($hash);
		if($title != null)
			$this->magnet .= '&dn='.urlencode($title);
		if(!self::ONLY_DHT)
			$this->magnet .= '&tr='.implode('&tr=', array_map('urlencode', self::$PublicTrackers));
		return true;
	}
	
	public function __toString()
	{
		return $this->magnet;
	}
	
	public static function controlHash($hash)
	{
		return preg_match(self::REGEX_HASH, $hash) ? true : false;
	}
	
	//Alias controlHash => isMagnet ?
	public static function isMagnet($magnet)
	{
		return self::controlHash($magnet);
	}
	
	public static function parseHash($string)
	{
		if(preg_match(self::REGEX_HASH, $string, $matches))
			return $matches[0];
		return false;
	}
}

class RSSParser
{
	public $data = null;
	const MAX_TRIES = 5;
	const DEBUG = false;

	public function __construct($file)
	{
		$try = 0;
		do
		{	//multiple tries, server timeout avoiding.
			if(self::DEBUG)
			{
				if((boolean)$try)
					readfile($file);
				print $file." - Try ".$try.", sleeping ".$try."s\n";
			}
			sleep($try++);
		}
		while(($this->data = simplexml_load_string(file_get_contents($file, false, stream_context_create(
			array(  //making this looking like a common rss browser, torrentz requires it
				'http'=>array(
					'method'=>	"GET",
					'header'=>	"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
							"User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.21 (KHTML, like Gecko) rekonq/2.4.2 Safari/537.21\r\n"
					)
				)
			)))) === false 
			&& $try != self::MAX_TRIES);

		return $this;
	}
	
	public function __get($name)
	{
		return $this->data->$name;
	}
	
	public function Items()
	{
		$it = array();
		foreach($this->channel->item as $i)
			$it[] = new RSSItem($i);
		return $it;
	}
}

class RSSItem extends RSSParser
{
	public $data = null;
	public $filename = '';
	public function __construct($item)
	{
		$this->data = $item;
		if(!class_exists('FileNameParser'))
			require_once('FileNameParser.php');
		$this->filename = new FileNameParser($this->title, true);
		return $this;
	}
	
	public function Season()
	{
		if($this->filename->isSerie())
			return $this->filename->season;
		return false;
	}
	
	public function Episode()
	{
		if($this->filename->isSerie())
			return $this->filename->episode;
		return false;
	}
	
	public function Year()
	{
		return $this->filename->Year();
	}
	
	public function Tags()
	{
		return $this->filename->Tags();
	}
	
	public function getHash()
	{
		if($this->torrent->infoHash)
			return $this->torrent->infoHash;
		elseif(isset($this->enclosure) && $at = $this->enclosure->attributes())
		{
			$parse = MagnetLink::parseHash($at['url']);
			if($parse !== false)
				return $parse;
		}
		elseif($parse = MagnetLink::parseHash($this->description))
			return $parse;
		else	
			return false;
	}
	
	public function getMagnetLink()
	{
		if($this->torrent->magnetURI)
			return $this->torrent->magnetURI;
		elseif($this->link && substr($this->link, 0, 6) == MagnetLink::SCHEME)
			return $this->link;
		elseif($this->getHash())
			return (string) MagnetLink::__new($this->getHash(), $this->title);
		else	
			return false;
	}
	
	public function getTorrent($magnetpriority = true)
	{
		if($magnetpriority)
			if($this->getMagnetLink())
				return $this->getMagnetLink();
		
		if(isset($this->enclosure) && $at = $this->enclosure->attributes())
			if($at['url'])
				return $at['url'];
		return false;
	}
	
	public function getMagnetURI()
	{
		return $this->getMagnetLink();
	}
	
	public function getMagnet()
	{
		return $this->getMagnetLink();
	}
	
}

class RSSData
{
	const CONF_DIR = CACHE_DIR;
	private $rssfile = null;
	private $storage = null;
	private $rssdata = null;
	private $formated_rssdata = array();
	
	public function __construct($file)
	{
		$this->rssfile = $file;
		if(!is_dir(self::CONF_DIR))
			mkdir(self::CONF_DIR);
		return $this;
	}
	
	protected function getCache()
	{
		if(!class_exists('Storage'))	require_once(dirname(__FILE__).'/fileStorage.php');
		$file = $this->getRssFile();
		if(is_file($file))
			$this->storage = new Storage($file);
		else
		{
			$this->storage = new Storage();
			$this->storage->setFile($file);
		}
		
		return $this;
	}
	
	protected function getRssFile()
	{
		return self::CONF_DIR . md5($this->rssfile).'.dat';
	}
	
	public function getNew()
	{
		$this->rssdata = new RSSParser($this->rssfile);
		foreach($this->rssdata->Items() as $item)
			$this->formated_rssdata[(string)$item->guid] = (object)array(
				'guid' => (string)$item->guid,
				'title' => (string)$item->title,
				'link' => (string)$item->getTorrent(),
				'date' => (string)$item->pubDate,
				'description' => (string)$item->description,
				//other parameters to add
			);
		$this->getCache();

		return array_diff_key($this->formated_rssdata, $this->storage->data);
	}
	
	public function Save()
	{
		$this->storage->data = array_merge($this->storage->data, $this->formated_rssdata);
		$this->storage->save();
		return $this;
	}
	
	public function getAll()
	{
		if(is_null($this->rssdata))
			$this->getNew();
		return array_merge($this->formated_rssdata, $this->storage->data);
	}
	
	public static function launch($file)
	{
		return new self ($file);
	}
	
	public static function __New($file)
	{
		return new self ($file);
	}
	
}

// class ShowRSS extends RSSData
// {
// 	const FEED_URL = "http://showrss.info/rss.php?user_id=192435&hd=null&proper=null";
// 	const LOCAL_FILE = 'rss/showrss.dat';
// 	const PREFER_REPACK = true;
// 
// 	private $conf = null;
// 	
// 	public function __construct($file = null)
// 	{
// 		parent::__construct(is_null($file) ? self::FEED_URL : $file);
// // 		if(!class_exists('Storage'))
// // 			require_once(dirname(__FILE__).'/fileStorage.php');
// // 		if(is_file(self::LOCAL_FILE))
// // 			$this->conf = new Storage(self::LOCAL_FILE);
// // 		else
// // 		{
// // 			$this->conf = new Storage();
// // 			$this->conf->setFile(self::LOCAL_FILE);
// // 		}
// 		return $this;
// 	}
// 	
// 
// }

?>
