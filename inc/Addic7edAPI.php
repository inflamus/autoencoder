<?php

class Addic7edAPI
{
	const CACHE_LISTS = true;
// 	const CACHE_DURATION = '7j';
	const ADDICTED_URL = 'http://www.addic7ed.com/';
	const SHOW_LIST_URL = 'http://www.addic7ed.com/shows.php';
	const MOVIE_LIST_URL = 'http://www.addic7ed.com/movie-subtitles';
	const ADDICTED_SHOW_AJAX = 'http://www.addic7ed.com/ajax_loadShow.php?';
	const SERIE = 1;
	const MOVIE = 2;
	
	public static $Langs = array(
		'fre' => '|8|8[fr]',
		'eng' => '|1|8[fr]',
	);
	
	public static $TagsEquivalents = array(
		//TAGOFYOURFILE  => array ( EQUIVALENTS TAGS INTO ADDICTED SUBS RELEASE )
		// ex : DIMENSION => array ('LOL');
		// ex : LOL => array('DIMENSION');
		// Note : everything must be in capitalized letters here.
		'LOL' => array(		'DIMENSION'	),
		'DIMENSION' => array(	'LOL'		),
		'SHAANIG' => array(	'2HD', 'QCF'	),
	
	);
	
	public static $CommonFileNameCorrection = array(
		// Filename => Real name
		// ex : Marvels => Marvel's
		'Marvels' => 'Marvel\'s',
		'marvels' => 'Marvel\'s',
		'DCs' => 'DC\'s',
		'dcs' => 'DC\'s',
		'H E D' => 'S.H.I.E.L.D.',
		'h e d' => 'S.H.I.E.L.D.',
	);
	
	private $list = array();
	private $type = -1;
	private $file = null;
	private $referer = '';
	public $download = false;
	
	public static function __New($type)
	{
		return new self($type);
	}
	
	public function __construct($type)
	{
		if(!class_exists('FileNameParser'))
			require_once(dirname(__FILE__) . '/FileNameParser.php');
		if($type instanceof FileNameParser)
		{
			$this->file = $type;
			$type = $this->file->isSerie() ? self::SERIE : self::MOVIE;
		}
		switch($type)
		{
			default:
			case self::SERIE:
				$this->getList(self::SHOW_LIST_URL);
				$this->type = self::SERIE;
			break;
			case self::MOVIE:
				$this->getList(self::MOVIE_LIST_URL);
				$this->type = self::MOVIE;
			break;
		}
		return $this;
	}
	
	private function getList($url)
	{
		if(self::CACHE_LISTS)
			if(file_exists($tmp = sys_get_temp_dir().'/ADDIC7ED_CACHE_'.md5($url).'.dat') && is_readable($tmp))
				$file = file_get_contents($tmp);
			else
				file_put_contents($tmp, $file = file_get_contents($url));
		else
			$file = file_get_contents($url);
		if(preg_match_all(				// 1			//2   //3   	
// 			'/<td class="version">.*<a href="\/(.+)">(.+)<\/a><\/h3><\/td>/U',
			'/<a href="\/(show\/[0-9]+)">(.+)<\/a>/U',
// 			'/<option value="([0-9]+)" >(.+)<\/option>/U',
			$file,
			$matches)===false)	throw new Exception('Unable to reach the url '.$url);
// 		print_r($matches);
		for($i = 0; $i< count($matches[0]); $i++)
			$this->list[$this->formatName($matches[2][$i])] = self::ADDICTED_URL . $matches[1][$i]; 
// 		print_r($this->list);
		return $this;
	}
	
	private function formatName($name)
	{
		return trim(
			strtolower(
				preg_replace(
					'/[^a-zA-Z0-9]+/', 
					' ', 
					str_replace(
						array_keys(self::$CommonFileNameCorrection), 
						array_values(self::$CommonFileNameCorrection), 
						$name
					)
				)
			)
		);
	}

	private function searchInList(FileNameParser $file)
	{
// 		print_r($this->formatName((string) $file));
		if	(isset($this->list[$key = $this->formatName((string) $file . ' ' . $file->Year())]))
			return $this->list[$key];
		elseif	(isset($this->list[$key = $this->formatName((string) $file)]))
			return $this->list[$key];
		else	return false;
	}
	
	public function search($lang, $file = null)
	{
		if($this->type == self::SERIE)
		{
			if(is_null($this->file))
				if(!is_null($file))
					$this->file = new FileNameParser($file);
				else
					throw new Exception('There is no file specified. You should pass a FileNameParser instance in the constructor, or in Addic7edAPI::search($lang, FileNameParser $file).');
			if(!$this->file instanceof FileNameParser)
				throw new Exception('Strange error : $this->file is not an instance of FileNameParser');
			
			return $this->searchEpisode($this->file, $lang);
		}
		elseif($this->type == self::MOVIE)
// 			throw new Exception('Addi7edAPI is not for movies yet.');
			return false;
	}
	
	private function searchEpisode(FileNameParser $file, $lang, $HI = false)
	{
		if(!array_key_exists($lang, self::$Langs))
			throw new Exception('The lang '.$lang.' is not valid.');
		if(!$url = $this->referer = $this->searchInList($file))
			return false;
		if(!preg_match('/[0-9]+$/', (string)$url, $mat))
			return false;
		$id = $mat[0];
		$season = $file->Season();
		$url = self::ADDICTED_SHOW_AJAX . http_build_query(array(
			'show' => $id,
			'season' => $season,
			'langs' => self::$Langs[$lang],
			
			));
		if(!preg_match_all('/<tr class="epeven completed">(.+)<\/tr>/sU', file_get_contents($url), $matches))
// 			exit(file_get_contents($url));
			return false;
		$list = array();
		foreach($matches[1] as $line)
		{
			preg_match_all('/<td.*>(.*)<\/td>/sU', $line, $m);
// 			print_r($m);
			$td = $m[1];
			if($td[5] != 'Completed') //choose only completed
				continue;
			if((int)$file->Episode() != (int)$td[1]) //choose only the actual episode
				continue;
			$list[] = array(
				$td[4], //TAg,
				empty($td[6]) ? false : true, //HI
				preg_replace('/<a href="\/(.+)">Download<\/a>/', self::ADDICTED_URL . '$1', $td[9]),
				);
		}
		if(!empty($list))
		{
			$best = array();
			$tag = strtoupper($file->Tags());
			foreach($list as $l)
			{
// 				if(empty($best))
// 				{
// 					$best = $l;
// 					continue;
// 				} //choose the best : 				
				$score = 0;
				if(strtoupper($l[0]) == $tag)
					$score += 2;
				elseif(array_key_exists($tag, self::$TagsEquivalents)
					&& in_array(strtoupper($l[0]), self::$TagsEquivalents[$tag]))
					$score += 1;

// 					if(strtoupper($best[0]) == $tag // le tag precedent equals tag
// 						|| (array_key_exists($tag, self::$TagsEquivalents) // if the tag equals the sub tag (like lol=dimension)
// 							&& in_array(strtoupper($best[0]), self::$TagsEquivalents[$tag]))
// 						)
// 					{
				if((boolean)$HI === (boolean)$l[1])
					$score += 1;
// 				print $tag.$l[0].$best[0].$HI.$l[1].$best[1]."\n";
				$best[$score] = $l;
			}
			ksort($best);
			$best = end($best);
			return $this->download = $best[2]; //return the final url.
		}
		return false;
	}
	
	public function Download($file = false)
	{
		if(!$this->download)
			return false;
		$opts = array(
			'http'=>array(
				'method'=>"GET",
				'header'=>
					"Accept-language: en\r\n" .
					"Referer: ".$this->referer."\r\n" .
					"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad 
			)
		);
		$context = stream_context_create($opts);
		if($subtitle = file_get_contents($this->download, false, $context))
			if(strrpos($subtitle, "Daily Download count exceeded") === false)
				if($subtitle != null)
					if($file != false)
						if(file_put_contents($file, $subtitle))
							return $file;
						else
							throw new Exception('Unable to write file '.$file);
					else
						return $subtitle;
		return false;
	}
	
	public function __toString()
	{
		return $this->Download();
	}
	
}

?> 
