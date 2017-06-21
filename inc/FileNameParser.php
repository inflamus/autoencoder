<?php
class FileNameParser
{
	const BLACKLIST = __DIR__.'/blacklist';
	
	// From MovieThumbs (https://github.com/mdemeyer/MovieThumbs/blob/master/core/fileparser.cpp)
	/* REGEXSEPARATORS
	* Remove underscores, etc. from filenames
	* Dots are removed later, they can be part of a title
	*/
	const REGEXSEPARATORS = "/[_\-]/";
	
	const REGEXSPECIALWITHDOTS = "/(5|7)+\.1/";

	/* REGEXSPECIAL
	* Used to remove special characters
	* \W Matches a non-word character.
	* Thank you Giuseppe Calà
	*/
	const REGEXSPECIAL = "/[^\w\sàáâãäåòóôõöøèéêëçìíîïùúûüÿñ]/";

	/* REGEXBRACKETS
	* \\(               Include opening bracket
	* [^\\(]            Start match
	* *\\)              Match everyting until closing bracket
	*/
	const REGEXBRACKETS = "/\([^\(]*\)|\[([^]]+)\]|\{([^}]+)\}/";

	/* REGEXYEAR
	* \(19|20) number starting with 19 OR 20
	* \d{2} 2 numbers [0-9]
	*/
	const REGEXYEAR = "/(19|20)\d{2}/";

	/* REGEXXVID
	* \xvid|divx/ 
	*/
	const REGEXXVID = "/xvid|dvix|dvdivx/i";
	
	/* REGEXTAGS
	* Strip endtags
	*/
	const REGEXTAGS = "/-[\w]$/";
	
	/* REGEXCD.
	* \\s* zero or more whitespaces
	* \\d+ 1 or more numbers
	*/
	const REGEXCD = "/[C|c][D|d]\s*\d+/";
	
	public static $REGEXSERIE = array(
		"/[sS]([0-9]+)\s*[eE]([0-9]+)(.*)/" => array('season' => 1, 'episode' => 2), // S00E00
		"/([0-9]{1,2})x([0-9]{1,2})(.*)/" => array('season' => 1, 'episode' => 2), //01x09
		"/\s[Ee][Pp]\s?([0-9]+)(.*)/" => array('season' => -1, 'episode' => 1), // Ep.00
// 		"/\s([0-9]+)x[0-9]{2}\s/" => array('season' => 1, 'episode' => -1), // blah.100
// 		"/\s(?i)p(?:ar)?t\s/", //Part Pt.
// 		"/(19|20)\d{2}\s((0[1-9])|(1[012]))\s((0[1-9]|[12]\d)|3[01])/",  // yyyy-mm-dd
// 		"/((0[1-9]|[12]\d)|3[01])\s((0[1-9])|(1[012]))\s(19|20)\d{2}/",  // dd-mm-yyyy
// 		"/([\[|\(][a-zA-Z0-9]{8}[\]|\)])/", //[abCD5678] or (abCD5678) //??
	);
	
	private $filename = '';
	private $originalfilename = '';
	private $regexDone = false;
	public $season = -1;
	public $episode = -1;
	
	public function __construct($filename, $onlytitle = false)
	{
		$this->originalfilename = $this->filename = $filename;
		if(!$onlytitle)
		{
			$dirname = basename(dirname($filename));
			$basename = basename($filename);
			$this->filename = strlen($dirname)>strlen($basename)*1.6 ? $dirname : $basename;
		}
// 		exit($this->filename);	
		return $this;
	}
	
	private function RegexSpecial()
	{
		$this->filename = preg_replace(self::REGEXSPECIAL, '', $this->filename);
		$this->filename = preg_replace('/ [a-zA-Z] /', ' ', $this->filename);
		return true;
	}
	
	private function RegexCD()
	{
		$this->filename = preg_replace(self::REGEXCD, '', $this->filename);
		return true;
	}
	
	private function cleanDots()
	{
		$this->filename = str_replace('.', ' ', $this->filename);
		return true;
	}
	
	private function cleanName()
	{
		$this->filename = trim(preg_replace(self::REGEXBRACKETS, ' ', $this->filename));
		return true;	
	}
	
	private function RegexTags()
	{
		$this->filename = trim(preg_replace(self::REGEXTAGS, '', $this->filename));
		return true;
	}
	
	public function Find($needle)
	{
		if(preg_match('/'.$needle.'/', $this->originalfilename, $matches))
			return $matches[0];
		return false;
	}
	
	// Return the Endtag
	public function Tags()
	{
//		if(preg_match(self::REGEXTAGS, $this->originalfilename, $match))
//			return $match[0];
		return substr(strrchr(basename($this->originalfilename, '.mkv'), '-'), 1);
	}
	
	private function parseBlackList()
	{
		if(!file_exists(self::BLACKLIST) || !is_readable(self::BLACKLIST))
			throw new Exception ('Blacklist file not reachable. Error.');
		$regex = array();
		foreach(file(self::BLACKLIST) as $l)
			if($l[0] == '#' || !preg_match('/\w/', $l)) continue;  // strip comments
// 			elseif($l[0] == '-')
// 				$regex[] = '/ '.substr(trim($l), 1).'$/i'; // end tag
			else $regex[] = '/ '.trim($l).'/i';
// 		print_r($regex);
		$this->filename = preg_replace($regex, '', $this->filename);
			
		return true;
	}
	
	private function RegexSeparator()
	{	
		$this->filename = preg_replace(self::REGEXSEPARATORS, ' ', $this->filename);
		return true;
	}

	public function isSerie()
	{
		foreach(self::$REGEXSERIE as $regex=>$index)
			if(preg_match($regex, $this->originalfilename, $match))
			{
				$this->season = (int)$match[$index['season']];
				$this->episode = (int)$match[$index['episode']];
				return true;
			}
		return false;
	}
	
	public function Season()
	{
		return $this->season;
	}
	
	public function Episode()
	{
		return $this->episode;
	}
	
	public function Year()
	{
		if(preg_match(self::REGEXYEAR, $this->originalfilename, $match))
			return $match[0];
		return false;
	}
	
	public function isXvid()
	{
		if(preg_match(self::REGEXXVID, $this->originalfilename))
			return true;
		return false;
	}
	
	private function RegexYear()
	{
		$this->filename = preg_replace(self::REGEXYEAR, '', $this->filename);
		return true;
	}
	
	private function RegexSerie()
	{
		$this->filename = preg_replace(array_keys(self::$REGEXSERIE), '', $this->filename);
		return true;
	}
	
	private function RegexWithDot()
	{
		$this->filename = preg_replace(self::REGEXSPECIALWITHDOTS, '', $this->filename);
		return true;
	}
	
	public function __toString()
	{
		if($this->regexDone)
			return ($this->filename);
		
		$this->RegexSeparator();
		
		$this->RegexWithDot();
		
		$this->RegexTags();
		
		// REMOVE DOTS
		$this->cleanDots();
		
		$this->RegexSpecial();
		
		$this->RegexCD();
		
		//Remove Blacklisted words
		$this->parseBlackList();
		
		//Clean cleanName
		$this->cleanName();
		
		$this->RegexYear();
		
		$this->RegexSerie();
		
		$this->filename = trim($this->filename);
		$this->regexDone = true;
		return ($this->filename);
	}
}

?>
