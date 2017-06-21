<?php

class Chapters
{
	public $chaps = array();
	private $D = null;
	public function __construct()
	{
		$this->D = new DateTimeImmutable('1970-01-01 00:00:00');
		return $this;
	}
	
	public function Add($time, $name = null, $append = false)
	{
		if($time instanceof FFmpeg)
		{
			$time = $time->duration;
			$append = true;
		}
		if(is_array($time) && count($time['01']) == 3 && isset($time['01']['MINUTES']))
			$time = '00:'.$time['01']['MINUTES'].':'.$time['01']['SECONDS'].'.'.str_pad($time['01']['FRAMES'], 3, '0');
		if(is_string($time))
		{
			$time = new DateTimeImmutable('1970-01-01 '.$time);
			if($append)
				$time = (empty($this->chaps) ? $this->D : end($this->chaps)[0])->add(new DateInterval('PT'.$time->format('H\Hi\Ms\S')));
		}
		static $i = 1;
		$this->chaps[] = array(
			$time,
			!is_null($name) ? $name : 'Chapter '.$i++,
		);
		return $this;
	}
	
	public function toNero($file = null)
	{
		$o = '';
		foreach($this->chaps as $c)
		{
			$o .= $c[0]->format('H:i:s').' '.$c[1]."\n";
		}
		return $file ? file_put_contents($file, $o) : $o;
	}
	
	public function toOGM($file = null)
	{
		$o = '';
		foreach($this->chaps as $k=>$v)
		{
			$n=str_pad((string)$k, 2, '0', STR_PAD_LEFT);
			$o .= 'CHAPTER'.$n.'='.$v[0]->format('H:i:s').'.000'."\n";
			$o .= 'CHAPTER'.$n.'NAME='.$v[1]."\n";
		}
		return $file ? file_put_contents($file, $o) : $o;
	}
	
	public function __toString()
	{
		return $this->toOGM();
	}
}
