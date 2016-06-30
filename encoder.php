#!/usr/bin/php
<?php

abstract class Encoder
{
	const BASEDIR = '/home/romein/encoder';
// 	const NOTIFY = 'Free'; // notify by sms with free mobile extension
	const NOTIFY = 'notify'; // notify with native notification on desktop

	protected $file = '';
	protected $tempdir = '';
	protected $bashoutput = "#!/bin/sh\n\n#Automatically Generated by phpEncoder\n####################\n\n";
	protected $vars = array();
	private $var_i = 0;

	protected function genTempDir($filename)
	{
		@mkdir($dir = self::BASEDIR.'/'.$filename, 0777);
		return $this->tempdir = $dir;
	}
	
	protected function addVar($var)
	{
		if(count($argv = func_get_args()) == 2)
		{
			$key = $argv[0];
			$var = $argv[1];
		}else	$key = 'var'.$this->var_i++;
		if(array_key_exists($key, $this->vars))
			return $this->addVar($key.$this->var_i++, $var);
		$var = $this->parseIllegalShellChars($var);
		$this->vars[$key] = escapeshellarg($var);
		$this->addLine($key.'="'.$var.'"');
		return '$'.$key;
	}
	
	protected function parseIllegalShellChars($var)
	{
		$patt = array( 'ç' );
		$repl = array( 'c' );
		return str_replace($patt, $repl, $var);
	}
	
	protected function Todo($file, $comments='')
	{
		switch(self::NOTIFY)
		{
			case 'Free':
				if(!class_exists('FreeMobile'))	require_once('inc/FreeMobile.php');
				FreeMobile::__New()->Send('Todo : '.$msg = '#'.$comments."\n\n".$file);
			break;
			case 'notify':
				if(!class_exists('Notify')) require_once('inc/Notify.php');
				Notify::Send('Auto Video Encoder', 'TODO : '.$msg = '#'.$comments."\n\n".$file);
			break;
			default: break;
		}
		file_put_contents($this->tempdir.'.todo', $msg);
		return false;
	}
	
	protected function addLine($line)
	{
		$this->bashoutput .= $line ."\n";
		return $this;
	}
	
	protected function genBash($output, $automatic_end=false)
	{
		if(file_put_contents($file = $this->tempdir.'/encoder.sh', $this->bashoutput."\n".$output.($automatic_end ? "\nmv encoder.sh encoder.end" : "\n####")))
			chmod($file, 0644);
		return $file;
	}
	
	protected function removeIllegalChars($output)
	{
		$bad = array_merge(
			array_map('chr', range(0,31)),
			array("<", ">", '"', "\\", "|", "?", "*"));
		$output = str_replace(':', '-', $output);
		return str_replace($bad, "", $output);
	}
}

require('inc/PhpOpenSubtitles/SubtitlesManager.php');
require('inc/FileNameParser.php');
require('inc/MKVTagger.php');
require('inc/FFmpeg.php');
require('inc/TMDBApi.php');
require('inc/srtParser.php');
require('inc/VideoEncoder.php');
require('inc/AudioEncoder.php');
require('inc/TheSubDBApi.php');
require('inc/SubsceneAPI.php');
require('inc/Addic7edAPI.php');
require('inc/FreeMobile.php');
// require('inc/Notify.php');

chdir('/home/romein/encoder');

if($argv[1] == 'ktorrent')
	for($i = 2; $i < count($argv); $i++)
	{
		$f = $argv[$i];
		if(preg_match('/<b>(.+)<\/b>/U', $f, $match))
		{
			$argv[$i] = '/home/romein/Downloads/Downloads_134/'.$match[1];
			$argv[++$i] = '/home/romein/Downloads/Downloads_160/'.$match[1];
			$argv[++$i] = '/home/romein/Downloads/Downloads_Verbatim_ HD/'.$match[1];
		}
	}
if(getenv('TR_TORRENT_DIR') !== false)
{
	//Transmission torrent-end-script
	$argv[1] = getenv('TR_TORRENT_DIR').'/'.getenv('TR_TORRENT_NAME');
// 	FreeMobile::__New()->Send("Téléchargement terminé : ".getenv('TR_TORRENT_NAME'));
}
// <b>Nick_Callaghan_and_Craig_Meichan-Rectify-KR024-WEB-2014-JUSTiFY</b> has completed downloading.
// Average speed: 3.18 KiB/s DL / 100 B/s UL.

for($i = ($argv[1] == 'ktorrent') ? 2 : 1; $i<count($argv); $i++)
{
	if($argv[$i] == false || is_null($argv[$i]))
		continue;
	$f = $argv[$i];
	if(!is_dir($f) && !is_file($f))
		continue;
	$f = realpath($f);
	if(is_dir($f))
	{

		$d = array();
		foreach(scandir($f) as $v)
			if($v == '.' || $v == '..')
				continue;
			else
				$d[filesize($f.'/'.$v)] = $f.'/'.$v;
		ksort($d);
		$f = end($d);
	}
	else
	{
		mkdir($dir = dirname($f).'/_encode_'.basename($f));
		symlink($f, $f = $dir.'/'.basename($f));
	}
// 	print 'File : '.$f."\n";
	if(fsockopen("www.google.com", 80) === false)
	{
		Encoder::Todo($f, 'No Internet Connection.');
		continue;
	}
	if(VideoEncoder::isVideo($f))
	{
// 		print "Video";
		$video = new VideoEncoder($f);
	}
	elseif(AudioEncoder::isAudio($f))
	{
		$audio = new AudioEncoder($f);
	}
}


?>