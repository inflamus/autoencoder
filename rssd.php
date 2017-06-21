#!/usr/bin/php
<?php 

$URLS = array(
	
	'http://showrss.info/user/827.rss?magnets=true&namespaces=true&name=null&quality=null&re=null', //ShowRSS
// 	'http://torrentz.eu/feedA?f=a+state+of+trance+inspiron+split', //A State Of Trance - Armin van Buuren
// 	'https://torrentz.eu/feedA?q=a+state+of+trance+split+sbd', // a state of trance split, after inspiron quited
// 	'http://kickass.so/usearch/category%3Amusic%20user%3Aash968%20age%3Aweek/?rss=1', // ash968 Trance torrents
// 	'https://torrentz.eu/feed?f=jessica+jones+720p+hevc',//jessica jones hevc 720p
// 	'http://torrentz.eu/feedA?q=a+state+of+trance+split+bluefree', // Bluefree version of ASOT splitted
	
);

require_once('inc/RSSParser.php');
require_once('inc/Downloader.php');

$Downloader = Downloader::__New()
	->AddHandler(TransmissionRPC::__New())
// 	->AddHandler(FreeboxApi::__New()->Downloads())
	;
foreach($URLS as $rss_url)
{
	$U = RSSData::launch($rss_url);
	foreach($U->getNew() as $item)
		$Downloader->Add($item->link);
// 		print $item->link;
	$U->Save();
}


?>
