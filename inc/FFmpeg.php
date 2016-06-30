<?php

abstract class FFmpegCLI
{
	protected $args = '';
	protected $file = '';
	protected $executed = false;
	protected $output = array();
	protected $outfile = '';
	
	static public $CodecParams = array(
		'libx264' => '-direct-pred 3 -rc-lookahead 90 -me_range 20 -bf 16 -subq 9 -me_method umh -refs 5 -trellis 1',
		'libx265' => '-preset medium', // --tune grain -crf idem x264
		'libfdk_aac' => '-cutoff 20000', 
		'libopus' => '-vbr on -compression_level 10', //-b:a 384k for 5.1
	);
	
	protected function _($r)
	{
		$this->args .= $r.' ';
		return $this;
	}
	
	protected function _clear()
	{
		$this->args = '';
		return $this;
	}
}

class FFmpeg extends FFmpegCLI
{
	protected $data = array();
	public $streams = array();
	public $cropdetect = '';
	public $overwrite = false;
	private $current_stream_i = array('v'=>0,'a'=>0,'s'=>0);
	protected $p_input = array();
	
	public function __construct($file, $outfile = null)
	{
		if(is_array($file))
		{
			if(substr($file[0], -3) == 'wav')
			{
				//use list.txt
				$this->p_input[] = '-f concat';
// 				$file = '<('.implode('', array_map(function($a){return "echo \"file '$a'\";";}, $file)).')';
				$tfile = tempnam(sys_get_temp_dir(), 'FFMpegCONCATLIST_');
				file_put_contents($tfile, implode("\n", array_map(function($a){return "file '".str_replace("'", "'\''", $a)."'";}, $file)));
				$file = $tfile;
			}
			else
				$file = 'concat:'.implode('|', $file);
		}
		$this->file = $file;
		$this->getStreams();
		if(!is_null($outfile))
			$this->OutFile($outfile);
		return $this;
	}
	
	public function __get($name)
	{
		return $this->data[$name];
	}
	
	public function __set($key, $v)
	{
		$this->data[$key] = $v;
		return true;
	}
	
	private function getStreams($return = false)
	{
// 		$backup = $this->outfile;
		$this	->Exec();
// 		$this->outfile = $backup; //put back the out file
		
		foreach($this->output as $line)
		{
// 			print $line."\n";
		//title
			if(preg_match('/title[\s]*:\s(\w)+$/', $line, $match))
				$this->title = $match[1];
			if(preg_match('/Duration: ([0-9]{2}:[0-9]{2}:[0-9}{2}\.[0-9]{2})/', $line, $match))
				$this->duration = $match[1];
			if(preg_match('/Stream \#([0-9]:[0-9]{1,2})(\([a-z]{3}\))?: (Audio|Subtitle|Video): (.+)$/', $line, $match))
			{
				if(empty($match[2])) 
					$match[2] = '(und)';
				$match[2] = substr($match[2], 1, 3);
				switch($match[3])
				{
					case 'Video':
						$d = explode(', ', $match[4]);
						$codec = $d[0];
						preg_match('/([0-9]{3,4}x[0-9]{3,4})/', $match[4], $size);
						$size = $size[1];
						$this->streams['Video'][] = new FFmpegStream(array(
							'type' => 'Video',
							'streamid' => $match[1],
							'lang'=> $match[2],
							'codec' => $codec,
							'size' => $size,
							'additionaldata' => $match[4],
						), $this->current_stream_i['v']++, $this);
					break;
					case 'Audio':
						$d = explode(', ', $match[4]);
						$codec = $d[0];
						$rate = $d[1];
						$channel = $d[2];
						$this->streams['Audio'][] = new FFmpegStream(array(
							'type' => 'Audio',
							'streamid' => $match[1],
							'lang' => $match[2],
							'codec' => $codec,
							'rate' => $rate,
							'channel' => $channel,
							'additionaldata' => $match[4],
						), $this->current_stream_i['a']++, $this);
					break;
					case 'Subtitle':
						$this->streams['Subtitle'][] = new FFmpegStream(array(
							'type' => 'Subtitle',
							'streamid' => $match[1],
							'lang' => $match[2],
							'additionaldata' => $match[3],
						), $this->current_stream_i['s']++, $this);
					break;
					default:
						$this->streams['Unknown'][] = new FFmpegStream(array(
							'type' => 'Unknown',
							'additionaldata' => $match[3],
							'lang' => $match[2],
							'streamid' => $match[1],
						), $this->current_stream_i['u']++, $this);
					break;
				}
			}
		}
		$this->clearOutput();
		return $return ? $this->streams : $this;
	}
	
	public function OutFile($file, $erase = true)
	{
		$this->Overwrite($erase);
		$this->outfile = $file;
		return $this;
	}
	
	public function Exec()
	{
		if($this->executed)
			return false;
		
		exec((string)$this, $this->output);
		//print $this."\n";
		return $this->executed = true;
	}
	
	protected function clearOutput()
	{
		$this->executed = false;
		$this->output = array();
		return $this->_clear();
	}
	
	public function __toString()
	{
// 		exit($this->file);
		if(!empty($this->streams))
			foreach($this->streams as $s)
				foreach($s as $ss)
					$this->_((string)$ss);
		return 'ffmpeg '.
			implode(' ', $this->p_input).
			' -i '.escapeshellarg($this->file).' '.
			$this->args.
			($this->overwrite ? '-y ' : '').
			'"'.$this->outfile.'"'
			.' 2>&1';
	}

	public function no($type)
	{
		switch(strtolower($type))
		{
			case 'video': 	$arg = '-vn'; 	unset($this->streams['Video']);		break;
			case 'audio': 	$arg = '-an';	unset($this->streams['Audio']);		break;
			case 'subtitle':$arg = '-sn';	unset($this->streams['Subtitle']);	break;
		}
		$this->_($arg);
		return $this;
	}
	
	public function noAudio()
	{
		return $this->no('Audio');
	}
	
	public function noVideo()
	{
		return $this->no('Video');
	}
	
	public function noSubtitle()
	{
		return $this->no('Subtitle');
	}
	
	public function noMetaData()
	{
		$this->_('-map_metadata -1');
		return $this;
	}
	
	public function stripTags()
	{
		return $this->noMetaData();
	}
	
	public function stripMetadata()
	{
		return $this->noMetaData();
	}
	
	public function AutoGain()
	{
		return $this->Normalize();
	}
	
	public function Normalize()
	{
		return $this
			->VolumeDetect()
			->Volume();
	}
	
	public function Overwrite($o = true)
	{
		$this->overwrite = $o;
		return $this;
	}
	
	public function VolumeDetect($return = false)
	{
		if($return == false)
			foreach($this->streams['Audio'] as $sa)
				$sa->VolumeDetect();
		elseif(is_int($return) && array_key_exists($return, $this->streams['Audio']))
			$this->streams['Audio'][$return]->VolumeDetect();
		return $this;
	}
	
	public function Volume($volume = 0)
	{
		foreach($this->streams['Audio'] as $sa)
			$sa->Volume($volume);
		return $this;
	}
	
	public function CropDetect($return = false)
	{
// 		$this	->OutFile('/dev/null', true)
// 			->_('-vf cropdetect')
// 			->_('-ss 120') // start at 2min 
// 			->_('-t 10') //stop at 10 sec later -- skip beginin generic...;
// 			->_('-f matroska')
// 			->_('-sn')
// 			->_('-an')
// 			->Exec();
		exec('ffmpeg -i '.escapeshellarg($this->file).' -vf cropdetect -ss 600 -t 30 -f matroska -sn -an -y /dev/null 2>&1', $this->output);
// 		print_r($this->output);
		foreach($this->output as $l)
			if(preg_match('/crop=([0-9]{1,4}:[0-9]{1,4}:[0-9]{1,4}:[0-9]{1,4})$/', $l, $match))
				$this->cropdetect = $match[1];
// 		print $this->cropdetect;
		$this->clearOutput();
		return $return ? $this->cropdetect : $this;
	}
	
// 	public function __call($name, $args)
// 	{
// 		if(preg_match('/(.*)(Audio|Video|Subtitle)(.*)/i', $name, $match))
// 		{
// 			$name = empty($match[1]) ? $match[3] : $match[1];
// 			if(empty($name))
// 				return false;
// 			return call_user_func(array($this, $name), array_merge(array($match[2]), $args));
// 		}
// 		throw new Exception('Unknown function');
// 		return false;
// 	}

}

class FFmpegStream extends FFmpeg
{
	protected $data;
	private $filters = array();
	protected $cancel = false;
	private $current_stream_i = 0;
	private $crop = array();
	private $parent = null;
	
	public $volumedetect = '';
	
	public function __construct($stream, $current_stream_i, &$object)
	{
		$this->data = $stream;
		$this->current_stream_i = $current_stream_i;
		$this->parent = $object;
		return $this;
	}
	
	public function Cancel($cancel = true)
	{
		$this->cancel = $cancel;
		return $this;
	}
	
	private function t()
	{
		return strtolower($this->type[0]);
	}
	
	private function map()
	{
		return $this->t().':'.$this->current_stream_i;
	}
	
	public function addFilter($filter)
	{
		$this->filters[] = $filter;
		return $this;
	}
	
	public function Filter($filters)
	{
		if(is_string($filters))
			$this->filters += explode(',', $filters);
		return $this;
	}
	
	private function genFilters()
	{
		if(!empty($this->filters))
			$this->_('-filter:'.$this->map() .' "'.implode(',',$this->filters).'"');
		return $this;
	}
	
	public function Crop($value)
	{
		if($this->t()!='v')
			throw new Exception('cannont crop another track than a video track');
// 		if(!is_null($value))
// 			$value = $this->cropdetect;
		$d = explode(':', $value);
		$s = explode('x', $this->size);

		if((int)$d[0] == (int)$s[0] && (int)$d[1] == (int)$s[1])
			return $this; // no crop needed
		if((int)$d[2] < 8 && (int)$d[3] < 8)
			return $this;
		
		$this->crop = $d;
		return $this->addFilter('crop='.$value);
	}
	
	public function VolumeDetect($return = false)
	{
		$file = $this->parent->file;
		exec('ffmpeg -i '.escapeshellarg($file).' -map '.$this->streamid.' -filter:a volumedetect -sn -vn -f null /dev/null 2>&1', $out);
		if(preg_match('/max_volume: ([-+\.0-9]+) dB/', implode(' ', $out), $db))
		{
			$db = (float)$db[1];
			if($db < 0) // 
				$this->volumedetect = $db;
			else
				$this->volumedetect = false;
		}
		return $return ? $this->volumedetect : $this;
	}
	
	public function Volume($volume = 0)
	{
		if($volume == 0)
			$volume = abs($this->volumedetect);
		if(is_float($volume))
			$this->Filter('volume='. $volume .'dB');
		return $this;
	}
	
	public function Normalize()
	{
		return $this
			->VolumeDetect()
			->Volume();
	}
	
	//Alias of Normalize
	public function AutoGain()
	{
		return $this->Normalise();
	}
	
	public function Scale($w=null, $h=null)
	{
// 		print_r($this->filters);
		if($this->t()!='v')
			throw new Exception('its not a video track.');
		if(is_null($w) && is_null($h))
			throw new Exception('need at least one param.');
		if(!empty($this->crop))
			$ar = $this->crop;
		else
		{
			$ar = explode('x', $this->size);
			$ar[0] = (int)$ar[0];
			$ar[1] = (int)$ar[1];
		}
		if($w==$ar[0] || $h==$ar[1]) return $this;
		$ar = round($ar[0]/$ar[1], 3);
		if($w==null) $w = ceil($ar*$h);
		if($h==null) $h = ceil($w/$ar);
		if($w%16<8) $w -= $w%16; else $w += (16-($w%16));
		if($h%16<8) $h -= $h%16; else $h += (16-($h%16));
		
		return $this->addFilter('scale='.$w.'x'.$h);
	}
	
	public function Profile($profile)
	{
		$this->_('-profile:'.$this->map().' '.$profile);
		return $this;
	}

	public function Codec($codec)
	{
		$this->_('-c:'.$this->map().' '.$codec);
		if(isset(parent::$CodecParams[$codec]))
			$this->_(parent::$CodecParams[$codec]);
		$this->codec = $codec;
		return $this;
	}
	
	public function Channels($c)
	{
		if($this->t()!='a')
			return false;
		if($c == 'stereo')	$c = 2;
		if($c == '5.1')		$c = 6;
		if($c == 6 && $this->channel == '5.1')
			return $this;
		if($c == 2 && $this->channel == 'stereo')
			return $this;
		$this->_('-ac:'.$this->t().' '.(int)$c);
		return $this;
	}
	
	public function Bitrate($br)
	{
		$this->_('-b:'.$this->map().' '.$br);
		$this->bitrate = $br;
		return $this;
	}
	
	public function Quality($q)
	{
// 		print_r($this->codec);
// 		exit();
		if($this->codec == 'libx264' || $this->codec == 'libx265')
			$this->_('-crf '.$q);
		elseif($this->codec == 'libfdk_aac')
			$this->_('-vbr:'.$this->map().' '.$q);
		else
			throw new Exception('Cant determine the appropriate quality attribute. Tell me what\'s your codec first.');
		return $this;
	}

	public function __toString()
	{
		if($this->cancel)
			return '';
		$this->_('-map '.$this->streamid);
// 		print_r($this->filters);
		$this->genFilters();
		return $this->args;
	}
}

?>

