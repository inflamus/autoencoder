<?php

/* Metadata fetcher and updater */
require('inc/FileNameParser.php');
require('inc/MKVTagger.php');
require('inc/TMDBApi.php');
if($argc < 2)
	die('Need at least one file to parse.');
print 'Parsing...'."\n";
$T = new TMDBApi();
for($i = 1; $i < $argc; $i++)
{
	$file = $argv[$i];
	print $file."... \n ";
	$F = new FileNameParser($file);
	print $F;
	$data = null;
	if($F->isSerie())
	{
		print ' '.$F->Season().'x'.$F->Episode();
		print " [Serie] ";
		print "\n  ";
		print "Searching TMDB...";
		$data = $T->searchTv($F);
		if($data->total_results == 0)
			continue;
		print 'found!';
		$TV = $data->getBest()->Tv();
		$serie_synopsis = $TV->overview;
		print "\n   Downloading poster...";
		$poster = sys_get_temp_dir().'/TMDBPOSTER_'.md5(time().microtime());
		$TV->Season($F->Season())->getPoster($poster);
		$season_synopsis = $TV->Season($F->Season())->overview;
		$data = $TV->Episode($F->Season(), $F->Episode());
		print "\n   Building tags...";
		print "\n    ";
		// if there is a cover, remove it.
		$MKV = new MKVPropEdit(trim($file));
		$MKV	->AddTags(MKVTagger::__New()->fetchFromTMDB($data)
				->setSummary($serie_synopsis)
				->setSeason_Synopsis($season_synopsis)
				)
			->RemoveChapters() // remove wrong chapters on series
			->SetCover($poster)
			->Exec()
			;
		print_r($data->PosterURL());
		print((string) $MKV ."\n");
	}
	else
	{
		print " [Film] ";
		print "\n  ";
		print "Searching TMDB...";
		$data = $T->findMovie($F);
		if($data->total_results == 0)
			continue;
		print 'found!';
		$data = $data->getBest()->Movie();
		$poster = sys_get_temp_dir().'/TMDBPOSTER_'.md5(time().microtime());
		$data->getPoster($poster);
		print "\n   ";
		$MKV = new MKVPropEdit(trim($file));
		$MKV->AddTags(MKVTagger::__New()->fetchFromTMDB($data)->setDescription($data->overview))
			->SetCover($poster)
			->Exec()
			;
		print((string) $MKV ."\n");
	}
}