<?php
// 		if(!class_exists('CueSheet'))	require_once('inc/cue_parse.class.php');
// 		$c = new CueSheet;
// 		$cue=$c->readCueSheet($argv[1]);
// // $a = new AudioEncoder($argv[1]);
// 		if(!class_exists('Chapters'))	require_once('inc/Chapters.php');
// 		$ch = new Chapters();
// 		foreach($cue['TRACKS'] as $t)
// 			$ch->Add($t['INDEX'], $t['PERFORMER'].' - '.$t['TITLE']);
// 		print $ch->toSimpleMP4($argv[2]);
// // AudioEncoder::cueToChapters();
// exit;
// var_dump($c->readCueSheet($argv[1]));
// require('inc/FFmpeg.php');
// 
// $FFMpeg = new FFmpeg($argv[1]);
// print((string)$FFMpeg->Normalize());
// exit;
//SHowRSS : http://showrss.info/rss.php?user_id=192435&hd=null&proper=null

// require('inc/FileNameParser.php');
// require('inc/Addic7edAPI.php');
// $file = new FileNameParser($argv[1]);
// $api = new Addic7edAPI($file);
// print_r($api->search('fre'));
// exit;
// file_put_contents($argv[1], 
// 	implode('.', array_map(
// 		function($i){ return strtoupper($i[0]).strtolower(substr($i, 1));}, 
// 		explode('.', file_get_contents($argv[1]))
// )));

if($argv[1] == 'list.txt')
{
		require('inc/TMDBApi.php');
		require('inc/MKVTagger.php');
	$T = new TMDBApi();
	foreach(file('list.txt') as $line)
	{
// // 		exit($line);
		$data = $T->findMovie(basename(trim($line), '.mkv'));
		if($data->total_results == 0)
			continue;
		$data = $data->getBest()->Movie();

// 			->getBest()->Movie();
//  		exit(print_r($data, true));
		$MKV = new MKVPropEdit(trim($line));
		$MKV->addTags(MKVTagger::__New()->fetchFromTMDB($data)->setDescription($data->overview))
			->Exec();
		print((string) $MKV ."\n");
	}
}

if(count($argv) ==2)
{
require('inc/srtParser.php');

$s = new SrtParser($argv[1]);
$s->StripHi()->Save();
}

if(count($argv) == 3)
{ // Two arguments passed : MKVPropEdit;
require('inc/TMDBApi.php');
require('inc/MKVTagger.php');
	$T = new TMDBApi();
	$data = $T->findMovie($argv[1])
		->getBest()->Movie();
	$MKV = new MKVPropEdit($argv[2]);
	$MKV->addTags(MKVTagger::__New()->fetchFromTMDB($data)->setDescription($data->overview))
		->Exec()
		;
	exit((string) $MKV );
}
if(count($argv) == 4)
{ // Two arguments passed : MKVPropEdit;
require('inc/TMDBApi.php');
require('inc/MKVTagger.php');
	$T = new TMDBApi();
	$data = $T->findMovie($argv[1], $argv[2]) 
		->getBest()->Movie();
	$MKV = new MKVPropEdit($argv[3]);
	$MKV->addTags(MKVTagger::__New()->fetchFromTMDB($data)->setDescription($data->overview))
		->Exec();
	exit((string) $MKV );
}
/*
*/



// require('inc/TMDBApi.php');
// 
// $T = new TMDBApi();
// $T->findMovie('Her', 2013)->getBest()->Movie()->getPoster('test.jpg');

// require('inc/RSSParser.php');
// 
// $s = new ShowRSS();
// foreach($s->getNew() as $item)
// {
// 	print_r ($item);
// }
// $s->Save();