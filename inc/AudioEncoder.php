<?php

class AudioEncoder extends Encoder
{
// 	const MUSICDIR = '/home/romein/Music/Music';
	const MUSICDIR = '/home/romein/Music/Verbatim HD';
	
	public static $AvailableMime = array(
		'audio/matroska',
		'audio/mp4',
		'audio/mp3',
		'audio/mpeg3',
		'audio/x-mpeg3',
		'audio/mpeg',
// 		'video/mpeg',
// 		'video/x-mpeg',
		
	);
	
	
	public static function isAudio($file)
	{
		if(file_exists($file) && is_readable($file))
		{
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			if(($mime = $finfo->file($file)) == 'application/octet-stream')
				if(strrchr($file, '.') == '.mp3')
					return true;
			if(!in_array($mime, self::$AvailableMime))
				return false;
			return true;
		}
		return false;
	
	}
	
	public function __construct($file)
	{
// 		if(preg_match('/A*State*Of*Trance*Year*Mix*(20[0-9]{2})/i', $file, $m))
// 			return $this->AStateOfTranceEncoderYearmix($file, $m[1]);
		if(preg_match('/A State Of Trance ([0-9]{3})/i', $file, $match))
			return $this->AStateOfTranceEncoder($file, $match[1]);
		
		$this->EncoderMP3($file);
		//TODO other encoder
		//TODO parse album => strip catalog ARMD1184 WEB
		//TODO flac check cover and copy
		return $this;
	}
	
	private function EncoderMP3($file)
	{
//		exit($file);
		if(is_file($file))
			$dir = basename($file, '.mp3');
		else	$dir = strrchr($file, '/');
// 		$tempdir = $this->genTempDir(strlen($dir) > strlen($file)+4 ? $dir : basename($file, '.mp3'));
		$tempdir = $this->genTempDir($dir);
// 		exit($tempdir);
		$re = false;
// 		exit($dir);
		if(is_file($file))
			foreach(scandir(dirname($file)) as $f)
			{
				print $f;
				if($f == '.' || $f == '..') continue;
				if(preg_match('/radio edit/i', $f)) continue;
				if(!symlink(dirname($file).'/'.$f, $tempdir.'/'.basename($f))) break;
				else $re = true;
			}
		else
			if(!symlink($file, $tempdir.'/'.basename($file))) $re = false;
			else $re = true;
// 		exit(realpath(__DIR__));
		if($re)	copy(realpath(__DIR__).'/encodemp3.sh.bak', $tempdir.'/encodemp3.sh');
		return true;
	}
	
	private function format_time($prevduration=array(0,0,0)) // t = seconds, f = separator 
	{
		return (strlen((string)$prevduration[0])<2 ? '0'.$prevduration[0] : (string) $prevduration[0]).":".
				(strlen((string)$prevduration[1])!=2 ? '0'.$prevduration[1] : (string) $prevduration[1]).":".
				(strlen((string)$prevduration[2])!=2 ? '0'.$prevduration[2] : (string) $prevduration[2]);
	}
	
	private function glob($dir, $ext)
	{
		$re = array();
		foreach(scandir($dir) as $f)
			if(strrchr($f, '.') == $ext)
				$re[] = $dir.'/'.$f;
		return $re;
	}
	
	public static function parseCue($file)
	{
		if(!class_exists('CueSheet'))	require_once('cue_parse.class.php');
		$c = new CueSheet;
		return $c->readCueSheet($file);
	}
	
	public static function cueToChapters($cue, $file = false)
	{
		if(!class_exists('Chapters'))	require_once('Chapters.php');
		$ch = new Chapters();
		foreach($cue['TRACKS'] as $t)
			$ch->Add($t['INDEX'], $t['PERFORMER'].' - '.$t['TITLE']);
		return $ch->toNero($file);
	}
	
	private function AStateOfTranceEncoder($file, $asotnumber)
	{
		if(is_dir($file))
			$dir = $file;
		else
			$dir = dirname($file);
		$outfile = 'Armin van Buuren - A State Of Trance '.$asotnumber.'.m4a';
		// Set working directory
		$tempdir = $this->genTempDir(basename($outfile, '.m4a'));
		$output = "cd '$tempdir'\n";
		$chaps = '';
		$playlist = array();
		$i = 1;
		$prevduration = array(0, 0, 0);
		$files = array();
		foreach($this->glob($dir, '.mp3') as $f)
		{
			if(is_file($f))
			{
				//0. Remove id3 tags , which create bugs within the concatenated stream.
// 				exec('id3v2 -D '.escapeshellarg($f));
				//1. convert to wav
				$ffmpeg = new FFmpeg($f);
				$ffmpeg->noVideo()->noSubtitle()->stripTags()->Outfile($wav = realpath($f).'.wav');
				$ffmpeg->Exec();
				unset($ffmpeg);
				//2. Add file to merged encoding
				$files[] = $wav;
				//3. Generate chapters.
				$ffmpeg = new FFmpeg($wav);
				preg_match('#([0-9]{2})\.? (.+)\.mp3#', basename($f), $data); 
				$chaps .= $this->format_time($prevduration).'.000 '.$data[2]."\n";
				$playlist[] = str_pad((string)$i++, 2, '0', STR_PAD_LEFT).'. '.$data[2];
				$time = explode(':', $ffmpeg->duration);
				$prevtime = $prevduration[0]*3600+$prevduration[1]*60+$prevduration[2];
				$actualtime = $time[0]*3600+$time[1]*60+$time[2];
				$newtime = $prevtime+$actualtime;
				$prevduration = array(
					floor($newtime/3600),
					($newtime/60)%60,
					$newtime%60);
				//4. Clear memory.
				unset($ffmpeg);
			}
		}
		$pngs = $this->glob($dir, '.png');
		if(!empty($pngs))
			foreach($pngs as $p)
				imagejpeg(imagecreatefrompng($p), substr($p, 0, -3).'jpg');
		$covers = $this->glob($dir, '.jpg');
		if(($k=$this->array_search_i('folder.jpg', array_map('basename', $covers))) !== false)
			$cover = $covers[$k];
		if(($k=$this->array_search_i('cover.jpg', array_map('basename', $covers))) !== false)
			$cover = $covers[$k];
		if(is_array($covers) && !empty($covers)) // still no cover found, choose the last one standing
			$cover = $covers[0];
		//gen chapters
		file_put_contents($tempdir.'/'.basename($outfile, '.m4a').'.chapters.txt', $chaps);
		$ffmpeg = new FFmpeg($files); //pass an array so it concatenate magically
		$ffmpeg	->noVideo()->noSubtitle()
			->OutFile($outfile);
// 		print_r($ffmpeg);
		$ffmpeg	->streams['Audio'][0]->Codec('libfdk_aac')->Bitrate('64k')->Profile('aac_he');
		

		$output .= (string)$ffmpeg."\n";
		$tags = array(
			'song' => 'A State Of Trance '.$asotnumber,
			'album' => 'A State Of Trance',
			'artist' => 'Armin van Buuren',
			'albumartist' => 'Armin van Buuren',
			'genre' => 'Trance',
			'comment' => implode("\r\n", $playlist),
		);
		$output .= $this->Tags($outfile, $tags, $cover, true);
		$output .= "\n";
		$output .= "#Remove temporary wav files\n";
		foreach($files as $f)
			$output .= "rm ".escapeshellarg($f)."\n";
		$output .= "#Mark as done.\n";
 		$output .= 'mv "'.$outfile.'" "'.self::MUSICDIR.'"';
		$this->genBash($output, true);
		
		return true;
	}
		
	private function array_search_i($str,$array)
	{
		foreach($array as $key => $value)
			if(stristr($str,$value)) return $key;
		return false;
	} 
	
	private function Tags($out_file, $tags, $cover = '', $chaps = false)
	{
	// $tags = array(
	//	'artist'
	//	'album'
	//	'artistalbum'
	//	'song'
	//	'genre'
	//);
		$output = '';
		if(!empty($cover))
			$output .= 'convert "'.$cover.'" -resize 600 "'.basename($cover).'"'."\n".
			'mp4art --remove "'.$out_file.'"'."\n".
			'mp4art --add "'.basename($cover).'" "'.$out_file.'"'."\n";
		$output .= 'mp4tags ';
		if(!empty($tags))
		foreach($tags as $k=>$v)
			$output .= '-'.$k.' "'.$v.'" ';
		$output .= '"'.$out_file.'"'."\n";
		if($chaps)
			$output .= 'mp4chaps --optimize --import "'.$out_file.'"'."\n";
		return $output;
	}

}

?>
