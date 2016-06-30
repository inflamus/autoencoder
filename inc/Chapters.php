<?php

class Chapters
{
	public $chaps = array();
	public function __construct()
	{
		return $this;
	}
	
	public function Add($time, $name)
	{
		if(is_array($time) && count($time['01']) == 3)
			$time = '00:'.$time['01']['MINUTES'].':'.$time['01']['SECONDS'].'.'.str_pad($time['01']['FRAMES'], 3, '0');
		
		$this->chaps[] = array(
			$time,
			$name
		);
	}
	
	public function toNero($file = null)
	{
		$o = '';
		foreach($this->chaps as $c)
		{
			$o .= $c[0].' '.$c[1]."\n";
		}
		return $file ? file_put_contents($file, $o) : $o;
	}
	
	public function toOGG($file = null)
	{
		$o = '';
		foreach($this->chaps as $k=>$v)
		{
			$n=str_pad((string)$k, 2, '0', STR_PAD_LEFT);
			$o .= 'CHAPTER'.$n.'NAME='.$v[1]."\n";
			$o .= 'CHAPTER'.$n.'='.$v[0];
		}
		return $file ? file_put_contents($file, $o) : $o;
	}
	
	public function __toString()
	{
		return $this->toOGG();
	}
}