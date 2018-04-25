<?php

/* for now, only for movies */


class SubsceneAPI 
{
	const TRUE_SIMILARITY = 87; // defaeult is 80% of similarity tomak eit good. increase to be more accurate, but you may loose some files without the exact name format
	const SUBSCENE_URL = 'https://subscene.com';
	const CACHE_LIST = true;

	private $filename = '';
	private $title = '';
	private $subs_url = '';
	private $sAvailable = array();
	private $lang = null;
	private $error = true;
	private $isSerie = false;
	
	public static function __New($TMDB, $filename)
	{
		return new self($TMDB, $filename);
	}
	
	public static function Serie($name, $season)
	{
		return new SubsceneAPISerie($name, $season);
	}
	
	public function __construct($TMDB, $filename)
	{
		if($TMDB instanceof TMDBMovie)
			$this->title = $TMDB->original_title;
		elseif($TMDB instanceof SubsceneAPISerie)
		{
			$this->title = $TMDB->get();
			$this->isSerie = true;
		}
		else
			return $this->error = false;

		$this->filename = basename($filename);
		if(strrchr($this->filename, '.') == '.mkv')
			$this->filename = basename($filename, '.mkv');
		$this->subs_url = self::SUBSCENE_URL.'/subtitles/'.preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($this->title));
		if(self::CACHE_LIST)
			if(file_exists( $cache = sys_get_temp_dir().'/SUBSCENE_'.md5($this->subs_url)))
				$data = file_get_contents($cache);
			else
			{
				if(!$data = file_get_contents($this->subs_url))
				{
					$this->subs_url.="-".substr($TMDB->release_date,0,4);
					if(!$data = file_get_contents($this->subs_url))
						return $this->error = false;
				}
				file_put_contents($cache, $data);
			}
		else
			if(!$data = file_get_contents($this->subs_url))
				return $this->error = false;
		if(!$html = utf8_decode($data))
			return $this->error = false;
		if(!preg_match_all('/ href="(\/subtitles\/.+)">.*<span class="l r (positive|neutral)-icon">(.+)<\/span>.*<span>(.+)<\/span>/Us', $html, $matches))
			return $this->error = false;
		for($i=0; $i<count($matches[0]); $i++)
		{
			if($this->isSerie)
			{
				$regex_ep = '/s[0-9]{2}e([0-9]{2})/i';
				if(!preg_match($regex_ep, trim($matches[4][$i]), $ep_match))
					continue;
				if(!preg_match($regex_ep, strtolower($this->filename), $ep_file))
					continue; // noepisode foudn
				if($ep_match[1] != $ep_file[1])
					continue;
			}
// 			print strtolower(trim($matches[4][$i])).'='.strtolower($this->filename);
			$lang = strtolower(substr(trim($matches[3][$i]), 0, 3));
			$link = self::SUBSCENE_URL.trim($matches[1][$i]);
			$rating = $matches[2][$i]=='positive' ? true : false;
			
			if(strtolower(trim($matches[4][$i]))!=strtolower($this->filename))
			{
				similar_text(strtolower(trim($matches[4][$i])), strtolower($this->filename), $pct);
				if($pct < self::TRUE_SIMILARITY)
					continue;
			}
			else
			{ //stop if find the perfect match for the lang.
				$this->sAvailable[$lang] = array(
					'pct' => 100,
					'link' => $link,
					'rating' => true,
				);
				$pct = 100;
			}
// 			print trim($matches[4][$i]).' Relevant.'.$pct."\n";

			if(isset($this->sAvailable[$lang]) && $this->sAvailable[$lang]['pct'] > $pct)
				continue;
			if(isset($this->sAvailable[$lang]) && $this->sAvailable[$lang]['pct'] == $pct)
				if($this->sAvailable[$lang]['rating'])
					continue;
					
			// choose the best.
			$this->sAvailable[$lang] = array(
				'pct' => $pct,
				'link' => $link,
				'rating' => $rating,
			);
		}
		return $this;
	}
	
	public function	Lang($lang)
	{
		if(!$this->error)	return false;
		$this->lang = $lang;
		return $this;
	}
	
	public function Has($lang = null)
	{	
		if(!$this->error)	return false;
		if($lang == null)
			if($this->lang == null)
				return false;
			else
				$lang = $this->lang;
		if(array_key_exists(strtolower(substr($lang, 0, 3)), $this->sAvailable))
			return true;
		return false;
	}
	
	public function Download($file, $lang = null)
	{
		if(!$this->error)	return false;
		if($lang == null)
			if($this->lang == null)
				return false;
			else
				$lang = $this->lang;
		if(!$this->Has($lang))
			return false;
		$link = $this->sAvailable[$lang]['link'];
		if(!preg_match('/href="(\/subtitles\/[a-z-]+text\/.+)"/U', file_get_contents($link), $matches))
			throw new Exception('No subscene link found to download the file');
		print 'Downloading... '.$this->sAvailable[$lang]['link']."\n";
		copy(self::SUBSCENE_URL.$matches[1], $tmp = sys_get_temp_dir().'/'.md5(microtime()).'.zip');
		$zip = new ZipArchive();
		if($zip->open($tmp) === true)
		{
			$filename = "";
			for($i=0; $i < $zip->numFiles; $i++)
			{
				if(strrchr($zip->getNameIndex($i), '.') != '.srt')
					continue;
				$filename = $zip->getNameIndex($i);
// 				similar_text(basename($filename), $this->filename, $pct);
// 				if($pct < self::TRUE_SIMILARITY)
// 					continue;
			}
			if($zip->extractTo(dirname($file), $filename))
				rename(dirname($file).'/'.$filename, $file);
			else
				exit('error');
			$zip->close();
		}
		else
			exit('zip error');
		return $file;
	}
	
	public function getString($lang = null)
	{
		if($this->Download($tmp = sys_get_temp_dir().'/'.md5(microtime()).'.srt', $lang) === false)
			return false;
		return file_get_contents($tmp);
	}

}

class SubsceneAPISerie extends SubsceneAPI
{
	public static $nbToWords = array(
		1 => 'First',
		2 => 'Second',
		3 => 'Third',
		4 => 'Fourth',
		5 => 'Fifth',
		6 => 'Sixth',
		7 => 'Seventh',
		8 => 'Eighth',
		9 => 'Ninth',
		10=> 'Tenth',
		11=> 'Eleventh',
		12=> 'Twelveth',
	);
	private $name, $season;
	public function __construct($name, $season)
	{
		if(!is_int($season))
			return false;
		if($name instanceof TMDBTv)
			$name = $name->original_name;
		if(!is_string($name))
			return false;
		$this->name = $name;
		$this->season = $season;
	}

	protected function get()
	{
		return $this->name.' - '.self::$nbToWords[$this->season].' Season';
	}
}
