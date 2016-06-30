<?php

define ('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));
define ('LANGUAGE_DETECTION_SCRIPT', realpath(dirname(__FILE__)).'/PHP-Language-Detection-master');
class SrtParser
{
	const LANGUAGE_DETECTION = LANGUAGE_DETECTION_SCRIPT;
	const SRT_STATE_SUBNUMBER =0;
	const SRT_STATE_TIME	= 1;
	const SRT_STATE_TEXT	= 2;
	const SRT_STATE_BLANK	= 3;
	
	const AUTOMATIC_STRIP_HI = true; // automatically strip HI if .HI. or .CHI. is found in filename.

	public static $Ads = array(
// 		'thebiocleanse.com',
		'thebiocleanse',
// 		'www.opensubtitles.org',
		'opensubtitles',
		'tvsubtitles.net',
		'addic7ed.com', //add here some part of strings related to Ads, it will strip the sub if found.
		'www.Bytesized-hosting.Com',
		'bytesize-hosting.com',
		'www.filebot.net',
		'bird-hd.info',
		'facebook.com',
		'subscene.com',
	);
	
	public static $InvalidChars = array(
		'' => '\'', //add here others invalid characters that appears as a square on when your media player reads it.
	);
	
	private $filename = '';
	private $subs = array();
	private $lang = '';
	
	public static function __New($file)
	{
		return new self($file);
	}
	
	public function __construct($file, $automatic_strip_HI = self::AUTOMATIC_STRIP_HI)
	{
		if(!is_file($file) && is_string($file))
		{
			file_put_contents($tmpfile =tempnam(sys_get_temp_dir(), 'SRTPARSER_'), $file);
			$file = $tmpfile;
		}
		if(!file_exists($file) || !is_readable($file))
			throw new Exception('Error, '.$file.' is not reachable.');
		
		$this->filename = $file;

		$lines   = file($file);
		$state   = self::SRT_STATE_SUBNUMBER;
		$subNum  = 0;
		$subText = '';
		$subTime = '';

		foreach($lines as $line) {
			switch($state) {
				case self::SRT_STATE_SUBNUMBER:
				if(trim($line) == '')	break;
				$subNum = trim($line);
				$state  = self::SRT_STATE_TIME;
				break;

				case self::SRT_STATE_TIME:
				$subTime = trim($line);
				$state   = self::SRT_STATE_TEXT;
				break;

				case self::SRT_STATE_TEXT:
				if (trim($line) == '' && $subText != '') {
					$sub = new stdClass;
					$sub->number = $subNum;
					list($sub->startTime, $sub->stopTime) = explode(' --> ', $subTime);
					$sub->text   = $subText; //since there, the texts are stored utf8 encoded.
					$subText     = '';
					$state       = self::SRT_STATE_SUBNUMBER;
					$this->subs[]      = $sub;
				} else {
					$subText .= $line;
				}
				break;
			}
		}
// 		print_r($this->subs);

		if($automatic_strip_HI)
			if(preg_match('/\.| C?HI\.| /', $file))
				$this->stripHI();
		return $this;
	}
	
	public function stripHI()
	{
		for($i = 0; $i < count($this->subs); $i++)
		{
			$s = $this->subs[$i];
			$string = preg_replace('/[\[\(]+.+[\]\)]+/s', '', $s->text); // Strip Hearing Impaired
// 			$string = str_replace('[TO_REMOVE]', '', $string); //manual command
			$string = preg_replace('/^\(.+\)$/s', '', $string); //strip parenthesis because the previous one doesnt do the job
	 		$string = preg_replace("/[\wÉÈéèêù ]+ ?:[\n\r ]+/", '', $string);  // Strip Names of characters
	 		$string = str_replace('##', '', $string);
	 		//$string = str_replace('<i></i>', '', $string);
	 		$string = preg_replace('/<([^<\/>]*)([^<\/>]*)>([\s]*?|(?R))<\/\1>/imsU', '', $string); //remove empty tags
	 		$string = preg_replace("/[0-9]+[\n\r]+[0-9]{2}:[0-9]{2}:[0-9]{2},[0-9]{3} --> [0-9]{2}:[0-9]{2}:[0-9]{2},[0-9]{3}/", '', $string);
// 			$string = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", '', $string); // strip empty lines
//print $string."\n---\n";
			$string = preg_replace("/\n?\-$/", '', $string); //strip empty "-"
// 			$string = preg_replace("//", "", $string);
			if(!preg_match("/[\S]/", $string))
			{
				array_splice($this->subs, $i, 1);  // remove empty subtitles
				continue;
			}

			$this->subs[$i]->text = trim($string);
		}
		return $this;
	}
	
	private function isAd($string)
	{
		foreach(self::$Ads as $ad)
			if(stripos($string, $ad) !== false)
				return true;
		return false;
	}
	
	public function detectLang()
	{
		if($this->lang !='')
			return $this->lang;
		if(!class_exists('NGramProfiles'))
			require_once(self::LANGUAGE_DETECTION.'/classifier.php');
		
		$classifier = new NGramProfiles(self::LANGUAGE_DETECTION.'/etc/classifiers/ngrams.dat');
		if( !$classifier->exists() ) {
			$classifier->train('eng', self::LANGUAGE_DETECTION.'/etc/data/english.raw');
			$classifier->train('fre', self::LANGUAGE_DETECTION.'/etc/data/french.raw');
			$classifier->save();
		} else {
			$classifier->load();
		}
		
		if(empty($this->subs))
			return false;
		
		$string = '';
		foreach(array_rand($this->subs, 8) as $k)
			$string .= $this->subs[$k]->text.' ';
		
		return $this->lang = $classifier->predict($string);
	}
	
	public function __toString()
	{
		$i = 1;
		$text = '';
		foreach($this->subs as $s)
		{
			if($this->isAd($s->text)) //Automatically strip Ad-related entries
				continue;
			$text .= $i++ ."\n";
			$text .= $s->startTime." --> ".$s->stopTime."\n";
			$text .= trim($s->text); // strip empty lines
			$text .= "\n\n"; 
		}
		return $text;
	}
	
	public function Save($return = true)
	{
// 		print "lklk".$this->subs[61]->text;
		if($return)
			return $this->toFile($this->filename);
		$this->toFile($this->filename);
		return $this;
	}
	
	public function toFile($file)
	{
	
		static $enclist = array(
			'UTF-8', 'ASCII',
			'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',
			'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10',
			'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
			'ISO-8859-2',
			'Windows-1251', 'Windows-1252', 'Windows-1254',
		);
		
		$enc = mb_detect_encoding($string = (string)$this, $enclist, true);
		if($enc && $enc != 'UTF-8')
			$string = mb_convert_encoding($string, 'UTF-8', $enc);
// 		else
// 			$string = (string) $this;
		$string = str_replace(
				array_keys(self::$InvalidChars), 
				array_values(self::$InvalidChars), 
				$string);
// 		print($enc);
		file_put_contents($file, UTF8_BOM . $string);
		
		return $file;
	}

}
