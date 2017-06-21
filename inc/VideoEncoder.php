<?php
// VP8 fait moins bien que x264 sur bitrate plus important. x265 > x264 > VP8. Ou placer VP9 ?
//TODO : harmoniser les codecs audio et video, inclure VP9, x265, opus, avec une interface 'quality+/-channel' vers les params FFMpeg
// VP9 : libvpx-vp9 -crf lossless 0 ... 63 -b:v 0 or 1M for constrained vbr
// x265: -preset medium / slow / slower -crf (voir FFMPEG.php) --better than 264, but 20x slower.
// opus: -vbr on -b:a 64k (overall bitrate) (voir FFMPEG.php)
// => et format webm ? probleme des tags non compatibles entre matroska et webm, notamment recording_location etc...
// => voir à passer en 720p ?
class VideoEncoder extends Encoder
{
	const OUTPUT_DIRECTORY = '/home/romein/Videos'; // without endslash
	const OUTPUT_SERIES_AUTOMATIC_DIRECTORY = true;  // move series to their matchinn directory

	const SERIEOUTFILE = '%name% %season%x%episode% - %title%';  // available : title, episode, name, season, year(of the first air_date)
	const MOVIEOUTFILE = '%title%';
	
	const SERIETITLE = '%name% %season%x%episode% - %title%';
	const MOVIETITLE = '%title% (%year%)';
	
	const INCLUDE_SEASON_POSTER_IN_SERIES = true;  //turn to false to not embbed covers on series.
	const INCLUDE_POSTER_IN_MOVIES = true;
	
	const AUTOMATIC_PREFERRED_LANG = 'eng'; // choose, between two audio tracks, the lang to allocate much more quality.
	const AUTOMATIC_DECREASE_AUDIO_LEVEL = 1; // audioquality - automaticdecrease  when the lang is not the preferred. default : 0.8 to 1.5

	// Encoding params
	const SERIE_VIDEOSIZE_W = 976;
	const MOVIE_VIDEOSIZE_W = 976;
	
	const SERIE_VIDEOCODEC = 'libx264';
	const MOVIE_VIDEOCODEC = 'libx264';
	
	public static $DoNotReencodeVideo = array(
		'Serie' => array('hevc'),
		);
	
	const SERIE_VIDEOQUALITY = 22.7;
	const MOVIE_VIDEOQUALITY = 22.6; //will be modified function of the genre of the movie (increased on action/adventure films)
	const MOVIE_VIDEOQUALITY_AUTO = true; // ...if this is checked to true.
	
	const SERIE_AUDIOCODEC = 'libopus'; //libfdk_aac, libopus
	const MOVIE_AUDIOCODEC = 'libopus'; //libfdk_aac, libopus
	
	public static $DoNotReencodeAudio = array('aac', 'mp3', 'opus'); //here the codecs tocopy directly without reencoding to the output file
	
	const SERIE_AUDIOCHANNELS = 2; // put 2 or 'stereo', or 6 aka '5.1'
	const MOVIE_AUDIOCHANNELS = 6; //will be modified too...
	const MOVIE_AUDIOCHANNELS_AUTO = true; //.. if this is checked to true;

	// From 1 to 10;  (values are mono-channel)  libfdk_aac
	// 		1-------2-------3-------4-------5-------6-------7-------8-------9-------10
	// mono		16k	24k	32k	40k	56k	80k	96k	112k	128k	160k
	// stereo	32k	48k	64k	80k	112k	160k	192k	224k	256k	320k
	// 5.1		--	--	192k	240k	336k	480k	576k	672k	768k	960k
	// profile	hev2	hev2	he	he	lc	lc	lc	lc	lc	lc
	
	const SERIE_AUDIOQUALITY = 5.5;//128k stereo
	const SERIE_AUDIOQUALITY_AUTO = true;
	const MOVIE_AUDIOQUALITY = 4;//224k 5.1
	const MOVIE_AUDIOQUALITY_AUTO = true;
	
	const SERIE_SUBTITLES_REQUIRED_LANG = 'fre,eng'; // 3 letters nationality, comma separated
	const SERIE_SUBTITLES_AUTODOWNLOAD = true;
	const MOVIE_SUBTITLES_REQUIRED_LANG = 'fre,eng';
	const MOVIE_SUBTITLES_AUTODOWNLOAD = true;
	
	//Modules to download Subtitles --- set to true to acivate
	const OPENSUBTITLES = 	true; //TODO : be careful, opensubtitles now deactivated by default. - too many wrong guesses.
	const THESUBDB = 	true;
	const SUBSCENE = 	true;
	const ADDIC7ED = 	true;
	
	public static $AvailableMime = array(
		'video/x-matroska',
		'video/x-mpeg4',
		'video/x-mp4',
		'video/mp4',
	);
	
	public static $OldMimes = array(
		'video/avi',
		'video/x-msvideo',
		'video/msvideo',
	);
	
	public static $MovieQuality = array (
		28 => 	-0.5,	//Action
		12 => 	-0.4,	//Aventure
		16 => 	+0.9,	//animation
		35 => 	+0.2,	//comédie
		80 => 	+0,	//Crime
		105 => 	-0.1,	//Catastrophe
		99 => 	+0.1,	//Documentaire
		18 => 	+0.2,	//Drame
		82 => 	+0,	//Estern
		2916 => +0,	//Erotique
		10751=>	+0.1,	//Familial
		10750=>	+0,	//Fan Film
		14 => 	-0.3,	//Fantastique
		10753=>	+0.1,	//Film noir
		10769=>	+0,	//Etranger
		36 => 	-0.1,	//Histoire
		10595=>	+0.5,	//Vacances
		27 => 	+0,	//Horeur
		10756=>	+0.3,	//Indie
		10402=>	+0.3,	//Musique
		22 => 	+0.3,	//Musical
		9648 => +0,	//Mystere
		10754=>	+0,	//Neonoir
		1115 => +0,	//Road Movie
		10749=>	+0.1,	//Romance
		878 => 	-0.3,	//Science fiction
		10755=>	+0.3,	//Court metrage
		9805 =>	+0,	//Sport
		10758=>	+0,	//Sporting Event
		10757=>	+0,	//Sports films
		10748=>	+0,	//Suspense
		10770=>	+0.4,	//Telefilm
		53 => 	+0,	//Thriller
		10752=>	-0.4,	//Guerre
		37 => 	-0.1,	//Western
		);
		
	public static $MovieChannels = array(
		28 => 	6,	//Action
		12 => 	6,	//Aventure
		16 => 	2,	//animation
		35 => 	2,	//comédie
		80 => 	2,	//Crime
		105 => 	6,	//Catastrophe
		99 => 	2,	//Documentaire
		18 => 	2,	//Drame
		82 => 	2,	//Estern
		2916 => 2,	//Erotique
		10751=>	2,	//Familial
		10750=>	2,	//Fan Film
		14 => 	6,	//Fantastique
		10753=>	2,	//Film noir
		10769=>	2,	//Etranger
		36 => 	2,	//Histoire
		10595=>	2,	//Vacances
		27 => 	2,	//Horeur
		10756=>	2,	//Indie
		10402=>	2,	//Musique
		22 => 	2,	//Musical
		9648 => 2,	//Mystere
		10754=>	2,	//Neonoir
		1115 => 2,	//Road Movie
		10749=>	2,	//Romance
		878 => 	6,	//Science fiction
		10755=>	2,	//Court metrage
		9805 =>	2,	//Sport
		10758=>	2,	//Sporting Event
		10757=>	2,	//Sports films
		10748=>	2,	//Suspense
		10770=>	2,	//Telefilm
		53 => 	2,	//Thriller
		10752=>	6,	//Guerre
		37 => 	2,	//Western
		);

	public static $MovieAudioQuality = array (
		28 => 	+1.2,	//Action
		12 => 	+0.9,	//Aventure
		16 => 	-1.5,	//animation
		35 => 	+0,	//comédie
		80 => 	+0,	//Crime
		105 => 	+0.3,	//Catastrophe
		99 => 	+0,	//Documentaire
		18 => 	+0,	//Drame
		82 => 	+0,	//Estern
		2916 => +0,	//Erotique
		10751=>	-1.0,	//Familial
		10750=>	+0,	//Fan Film
		14 => 	+0.7,	//Fantastique
		10753=>	-0.1,	//Film noir
		10769=>	+0,	//Etranger
		36 => 	-0.2,	//Histoire
		10595=>	-1.5,	//Vacances
		27 => 	+0,	//Horeur
		10756=>	+0,	//Indie
		10402=>	+2.0,	//Musique
		22 => 	+0.6,	//Musical
		9648 => +0,	//Mystere
		10754=>	+0,	//Neonoir
		1115 => +0,	//Road Movie
		10749=>	+0,	//Romance
		878 => 	+0.9,	//Science fiction
		10755=>	+0,	//Court metrage
		9805 =>	+0,	//Sport
		10758=>	+0,	//Sporting Event
		10757=>	+0,	//Sports films
		10748=>	+0,	//Suspense
		10770=>	+0,	//Telefilm
		53 => 	+0,	//Thriller
		10752=>	+0.4,	//Guerre
		37 => 	+0.0,	//Western
		);
	
	private $OldFormat = false;
	public static function isVideo($file)
	{
// 		print 'isvideo '.$file."\n";
		if(file_exists($file) && is_readable($file))
		{
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			if(!in_array($finfo->file($file), self::$AvailableMime))
				if(in_array($finfo->file($file), self::$OldMimes))
					return 'oldformat';
				else
					return false;
			return true;
		}
		return false;
	}
	
	public function __construct($file)
	{
		if(!$mime = self::isVideo($file))
			throw new Exception($file.' is not Video. cannont construct VideoEncoder()');
 		elseif($mime === 'oldformat')
 			$this->OldFormat = true;
// 		exit($mime);
		$this->file = $file;
		
		$filename = new FileNameParser($file);
		//print_r($filename);
		if($filename->isSerie())
			$this->Serie($filename);
		else	
			$this->Movie($filename);
		return $this;
	}
	
	public static function AverageAudioQuality($quality, $channels = 2, $codec = 'libfdk_aac')
	{
		// From 1 to 10;  (values are mono-channel)  libfdk_aac
		// 		1	2	3	4	5	6	7	8	9	10
		// mono	16k	24k	32k	40k	56k	80k	96k	112k	128k	160k
		// stereo	32k	48k	64k	80k	112k	160k	192k	224k	256k	320k
		// 5.1	--	--	192k	240k	336k	480k	576k	672k	768k	960k
		// profile	hev2	hev2	he	he	lc	lc	lc	lc	lc	lc
		$quality = (float)$quality;
		$channels = (int)$channels;
		if($quality<1)	$quality = 1;
		if($quality>10)	$quality = 10;
		switch($codec)
		{
			default:
			case 'libfdk_aac':
				if($channels == 6 && $quality < 2)
					$quality = 2;
				if($quality <= 4) // HE / HE+PS
					$bitrate = (16 + round(($quality-1)*8))*$channels;
				else	// -vbr  start at AAC-LC vbr = 2 ~40k mono  (horrible btw)
					$quality = round( 0.5 * $quality , 1); // quality=5 => -vbr 2.5
				return isset($bitrate) ? array('bitrate' => $bitrate.'k', 'profile' => ($quality>2 ? 'aac_he' : 'aac_he_v2')) :
					array('quality' => $quality);
			break;
			case 'libopus':
				return array('bitrate' => round($channels==6 ? 64*$quality : 16*$channels*$quality).'k');
			break;
		}
	}
	
	private function Serie(FileNameParser $filename)
	{
		//print_r($filename);
		$year = $filename->Year();
		$season = $filename->season;
		$episode = $filename->episode;
		if($season <1 || $episode <1)
			throw new Exception('Error regexin season and episodes.');
		
		$tempdir = $this->genTempDir((string)$filename.' '.$year.' '.$season.'x'.$episode);
		
		$tmdb = new TMDBApi();
		$data = $tmdb->findTv((string)$filename, $year);
		if($data->total_results == 0)
			return $this->Todo($this->file, 'No result for '.$filename.' in TMDB database. Correct the file name.');
		$data = $data->getBest()->Tv();
// 		print_r($data);
		$name = $data->original_name;
		$serie_synopsis = $data->overview;
		
		$season_synopsis = $data->Season($season)->overview;
		
		//--SUBTITLES Variables
		$subtitles = explode(',', self::SERIE_SUBTITLES_REQUIRED_LANG);
		$subtitles_files = array();
		
		
		if(self::INCLUDE_SEASON_POSTER_IN_SERIES)
		{
			$posterfile = $tempdir.'/poster.jpg';
			if(!$data->Season($season)->getPoster($posterfile))
				$data->getPoster($posterfile);
			$tagxml = MKVTagger::__New()
				->fetchFromTMDB( $data = 
					$data	->Season($season)
						->Episode($episode)
					)
// 				->setDescription($serie_synopsis)
				->setSummary($serie_synopsis)
				->setSeason_Synopsis($season_synopsis)
				->toXML($tagfile = $tempdir.'/tags.xml');
// 			print_r($data);
		}
		else
			$tagxml = MKVTagger::__New()
				->fetchFromTMDB( $data = 
					$data	->Episode($season,$episode)
					)
// 				->setDescription($serie_synopsis)
				->setSummary($serie_synopsis)
				->setSeason_Synopsis($season_synopsis)
				->toXML($tagfile = $tempdir.'/tags.xml');	
// 		print (var_dump($this->OldFormat)."\n".var_dump($filename->isXvid()));
		$ffmpeg = new FFmpeg($this->file);
		if(!$this->OldFormat && !$filename->isXvid() && (int)$ffmpeg->streams['Video'][0]->size > 976)
		{
// 		exit('good');
		//FFMPEG
	// 		print_r($streams);
	// 		exit();
			$streamscopy = true;
			$streams = $ffmpeg->OutFile($tempoutfile = $tempdir.'/temp.mkv')->streams;
			//---VIDEO
			if(!in_array(substr($streams['Video'][0]->codec,0,4), self::$DoNotReencodeVideo['Serie']))
			{
				$streamscopy = false;
				$streams['Video'][0]
					->Scale(self::SERIE_VIDEOSIZE_W)
					->Codec(self::SERIE_VIDEOCODEC)
					->Quality($this->addVar('crf', self::SERIE_VIDEOQUALITY));
			}
			else
				$streams['Video'][0]
					->Codec('copy');
			//---AUDIO
			foreach($streams['Audio'] as $sa)
			{
				if(!in_array(substr($sa->codec, 0, 3), self::$DoNotReencodeAudio))
				{
					$streamscopy = false;
					$quality = self::SERIE_AUDIOQUALITY;
					$channels = self::SERIE_AUDIOCHANNELS;
					if(self::SERIE_AUDIOQUALITY_AUTO
						&& count($streams['Audio']) > 1)
						if($sa->lang != self::AUTOMATIC_PREFERRED_LANG)
							$quality = self::SERIE_AUDIOQUALITY - self::AUTOMATIC_DECREASE_AUDIO_LEVEL;
					$sa	->Codec(self::SERIE_AUDIOCODEC)
						->Channels($channels);
					
		// 			print($quality);
					$quality = self::AverageAudioQuality($quality, $channels, self::SERIE_AUDIOCODEC);
		// 			print_r($quality);
					if(isset($quality['bitrate']))
						$sa	->Bitrate($this->addVar('bitrate_'.$sa->lang, $quality['bitrate']));
					if(isset($quality['profile']))
						$sa	->Profile($this->addVar('profile_'.$sa->lang, $quality['profile']));
					if(isset($quality['quality']))
						$sa	->Quality($this->addVar('quality_'.$sa->lang, $quality['quality']));
					
					// Normalize sound (volumedetect + volume to 0dB)
					$sa->	Normalize();
				}
				else
					$sa	->Codec('copy');
			}
			
			if($streamscopy == true) //every streams are copied without reencoding
			{
				$ffmpeg = "#No Reencoding\n";
				$tempoutfile = $this->file;
			}

			//---SUBTITLES exclusion
			if(isset($streams['Subtitle']) && count($streams['Subtitle'])>0)
				foreach($streams['Subtitle'] as $ss)
					if(in_array($ss->lang, $subtitles))
						$subtitles = array_diff($subtitles, array($ss->lang));
		}
		else
		{	//XVID
			$ffmpeg = "#No reencoding\n";
			$tempoutfile = $this->file;
		}
		

		//-- subtitles download
		//-----Automatic Subtitle Download
		$this->Subtitles($subtitles, $subtitles_files, $tempdir, $filename, array('name' => $name, 'season' => $season));
		
		// MKVMERGE
		$finaloutput = self::OUTPUT_DIRECTORY.'/';
		if(self::OUTPUT_SERIES_AUTOMATIC_DIRECTORY)
			$finaloutput .= $name.'/';
		$patt = array(
			'%year%',
			'%title%',
			'%season%',
			'%episode%',
			'%name%',
		);
		$replace = array(
			$year,
			$data->name,
			$season = $season,
			$episode = strlen((string)$episode)==1 ? '0'.$episode : $episode,
			$name
		);
		$finaloutput .= str_replace($patt, $replace, self::SERIEOUTFILE);
		$finaloutput = $this->removeIllegalChars($finaloutput);
		$title = str_replace($patt, $replace, self::SERIETITLE);
		$MKVMerge = new MKVMerge($this->addVar('finaloutputfile', $finaloutput));
		$MKVMerge
			->Title($this->addVar('title', $title))
			->addTags($tagfile)
			->addTrack(MKVMergeTrack::__New($tempoutfile));
		if(isset($posterfile))
			$MKVMerge->addPoster($posterfile);
		if(isset($subtitles_files) && !empty($subtitles_files))
			foreach($subtitles_files as $s)
				$MKVMerge->addTrack(
					MKVMergeTrack::__New($s['file'])
					->Language($s['lang'])
					->subCharset('utf-8'));
			
		// GEN BASH
		$output = '##FFMPEG'."\n".$ffmpeg;
		$output .= "\n\n##MKVMerge\n".$MKVMerge;
		$this->genBash($output, true);
		return true;
	}
	
	private function Subtitles($langs, &$subtitles_files, $tempdir, $filename, $subscene_data)
	{
// 		print_r(scandir( dirname($this->file) ));
// 		print dirname($this->file);
		//Adding attached subs.
		foreach((array)scandir($dir=dirname($this->file)) as $subfile)
		{
			if(strrchr($subfile, '.') != '.srt')
				continue;
			print 'Adding available subtitle '.$subfile."\n";
			//continue;
			$subtitles_files[] = array(
				'lang' => $lang = SrtParser::__New($dir.'/'.$subfile)
					->stripHI()
					->Save(false)
					->detectLang(),
				'file' => $dir.'/'.$subfile,
			);
			if(in_array($lang, $langs))
				$langs = array_diff($langs, array($lang));
		}
		
		$subscene = null;
		foreach($langs as $lang)
		{
			if(self::THESUBDB)
			{
				$SubDB = TheSubDBApi::getInstance();
				if(($sub = $SubDB->fetchMediaItemSubtitle($this->file, substr($lang, 2)))!==null)
				{
					print 'Downloading '.$lang.' on TheSubDB...'."\n";
					$subtitles_files[] = array(
						'lang' => $lang,
						'file' => SrtParser::__New($sub)
							->stripHI()
							->toFile($tempdir.'/'.$lang.'.'.$filename.'.srt')
						);
					continue;
				}
			}
			if(self::ADDIC7ED)
			{
				$Addic7ed = new Addic7edAPI($filename);
				if($Addic7ed->search($lang))
				{
					print "Downloading on Addic7ed lang ".$lang."...";
					$subtitles_files[] = array(
						'lang' => $lang,
						'file' => SrtParser::__New($Addic7ed->Download())
							->stripHi()
							->toFile($tempdir.'/'.$lang.'.'.$filename.'.srt'),
						);
					continue;
				}
			}
			if(self::OPENSUBTITLES)
			{
				$OpenSubtitles = new OpenSubtitles\SubtitlesManager($lang);
				if(!empty($sub = $OpenSubtitles->getSubtitleUrls($this->file, true)))
				{
					print 'Downloading '.$lang.' on OpenSubtitles...'."\n";
					$subtitles_files[] = array(
						'lang' => $lang,
						'file' => SrtParser::__New(
								$OpenSubtitles->downloadSubtitle(
									$sub[$lang], 
									$tempdir.'/'.$lang.'.'.$filename.'.srt')
								)
							->stripHI()
							->Save(),
						);
					continue;
				}
			}
			if(self::SUBSCENE)
			{
				if(is_null($subscene))
					if(is_array($subscene_data))
						$subscene = new SubsceneAPI(SubsceneAPI::Serie($subscene_data['name'], $subscene_data['season']), $this->file);
					else
						$subscene = new SubsceneAPI($subscene_data, $this->file);
				if($subscene)
					if($subscene->Has($lang))
					{
						print 'Downloading '.$lang.' on Subscene... '."\n";
						$subtitles_files[] = array(
							'lang' => $lang,
							'file' => SrtParser::__New(
									$subscene->Lang($lang)
										->Download($tempdir.'/'.$lang.'.'.$filename.'.srt')
									)
								->stripHI()->Save()
							);
						continue;
					}
			}
			if(self::OPENSUBTITLES)
			{
				if(!empty($sub = $OpenSubtitles->getSubtitleUrls(basename($this->file, '.mkv'), true)))  // only for series, if nothing tryed out before, we search opensubtitles by name
				{
					print "Warning : Fall back on name related download on OpenSubtitles.";
					$subtitles_files[] = array(
						'lang' => $lang,
						'file' => SrtParser::__New(
								$OpenSubtitles->downloadSubtitle(
									$sub[$lang], 
									$tempdir.'/'.$lang.'.'.$filename.'.srt')
								)
							->stripHI()
							->Save(),
						);
					continue;
				}
			}
// 			return $this->Todo($this->file, 'subtitle not found yet for lang '.$lang);
			$this->Todo($this->file, $lang.' subtitle not found yet. ');
		}
		return true;
	}
	
	private function CorrectQuality(TMDBMovie $tmdb)
	{
		$quality = self::MOVIE_VIDEOQUALITY;
		foreach($tmdb->genres as $g)
			$quality += self::$MovieQuality[$g->id];
		return $quality;
	}
	
	private function CorrectAudioQuality(TMDBMovie $tmdb)
	{
		$quality = self::MOVIE_AUDIOQUALITY;
		foreach($tmdb->genres as $g)
			$quality += self::$MovieAudioQuality[$g->id];
		return $quality;
	}
	
	private function CorrectChannel(TMDBMovie $tmdb, $channels)
	{
		if((int)$channels >= 5)
		{
			foreach($tmdb->genres as $g)
				if(self::$MovieChannels[$g->id] == 6)
					return 6;
			return 2;
		}
		else 
			return 2;
		
	}
	
	private function Movie(FileNameParser $filename)
	{
		$year = $filename->Year();

		$tempdir = $this->genTempDir((string)$filename.' '.$year);
		
		$tmdb = new TMDBApi();
		$data = $tmdb->findMovie((string)$filename, $year);
		if($data->total_results == 0)
		{
			$filename_dir = new FileNameParser(dirname($this->file));
			if((string)$filename_dir != (string)$filename)
			{
				$filename = $filename_dir;
				$data = $tmdb->findMovie((string)$filename, $year = $filename->Year());
			}
			if($data->total_results == 0)
				return $this->Todo($this->file, 'No result for '.$filename.' in TMDB database. Correct the file name.');
		}
		$data = $data->getBest()->Movie();
		
		if(self::INCLUDE_POSTER_IN_MOVIES)
			$data->getPoster($posterfile = $tempdir.'/poster.jpg');
		if(!$year)
			$year = substr($data->release_date, 0, 4);
		$tagxml = MKVTagger::__New()
				->fetchFromTMDB( $data )
				->setDescription($data->overview)
				->toXML($tagfile = $tempdir.'/tags.xml');
		
		$subtitles_files = array();
		$XVIDOLD = false;
		if(!$this->OldFormat && !$filename->isXvid())
		{
			//FFMPEG
			$ffmpeg = new FFmpeg($this->file);
			$cropdetect = $ffmpeg->CropDetect(true);
			$streams = $ffmpeg->OutFile($tempoutfile = $tempdir.'/temp.mkv')->streams;
// 			print $cropdetect;
			//---VIDEO
			$streams['Video'][0]
				->Crop($cropdetect)
				->Scale(self::MOVIE_VIDEOSIZE_W)
				->Codec(self::MOVIE_VIDEOCODEC);
			if(self::MOVIE_VIDEOQUALITY_AUTO)
				$streams['Video'][0]->Quality($this->addVar('crf', $this->CorrectQuality($data)));
			else
				$streams['Video'][0]->Quality($this->addVar('crf', self::MOVIE_VIDEOQUALITY));
			
			//---AUDIO
			foreach($streams['Audio'] as $sa)
			{
				$channels = self::MOVIE_AUDIOCHANNELS_AUTO ? 
					$this->CorrectChannel($data, $sa->channel) : 
					self::MOVIE_AUDIOCHANNELS;
				$quality = self::MOVIE_AUDIOQUALITY;
				if(self::MOVIE_AUDIOQUALITY_AUTO)
				{
					$quality = $this->CorrectAudioQuality($data);
					if(count($streams['Audio']) > 1)
						if($sa->lang != self::AUTOMATIC_PREFERRED_LANG)
							$quality -= self::AUTOMATIC_DECREASE_AUDIO_LEVEL;
				}
				if(!in_array(substr($sa->codec, 0, 3), self::$DoNotReencodeAudio))
				{
					$sa	->Codec(self::MOVIE_AUDIOCODEC)
						->Channels($channels);
					$quality = $this->AverageAudioQuality($quality, $channels, self::MOVIE_AUDIOCODEC);
					if(isset($quality['bitrate']))
						$sa	->Bitrate($this->addVar('bitrate_'.$sa->lang, $quality['bitrate']));
					if(isset($quality['profile']))
						$sa	->Profile($this->addVar('profile_'.$sa->lang, $quality['profile']));
					if(isset($quality['quality']))
						$sa	->Quality($this->addVar('quality_'.$sa->lang, $quality['quality']));
					
					// Normalize volumedetect + volume()
					$sa->	Normalize();
				}else
					$sa	->Codec('copy');
			}

			//---SUBTITLES
			if(self::MOVIE_SUBTITLES_AUTODOWNLOAD && $data->implodeProductionCountries() != 'FR') // french movies doesn't need subtitles.
			{
				$subtitles = explode(',', self::MOVIE_SUBTITLES_REQUIRED_LANG);
				if(isset($streams['Subtitle']) && count($streams['Subtitle'])>0)
					foreach($streams['Subtitle'] as $ss)
						if(in_array($ss->lang, $subtitles))
							$subtitles = array_diff($subtitles, array($ss->lang));
				//-----Automatic Subtitle Download
				$this->Subtitles($subtitles, $subtitles_files, $tempdir, $filename, $data);
			}
			$ffmpeg->noSubtitle();
		
		}
		else
		{ //XVID
			$XVIDOLD = true;
			$ffmpeg = "#No Reencoding\n";
		//Subtitles
			if(self::MOVIE_SUBTITLES_AUTODOWNLOAD && $data->implodeProductionCountries() != 'FR') // french movies doesn't need subtitles.
			{
				$subtitles = explode(',', self::MOVIE_SUBTITLES_REQUIRED_LANG);
				foreach($subtitles as $lang)
				{
					$dl = new OpenSubtitles\SubtitlesManager($lang);
					if(!empty($sub = $dl->getSubtitleUrls($this->file, true)))
						$subtitles_files[] = array(
							'lang' => $lang,
							'file' => SrtParser::__New(
									$dl->downloadSubtitle(
										$sub[$lang], 
										$tempdir.'/'.$lang.'.'.$filename.'.srt')
									)
								->stripHI()->Save(),
							);
					else
						return $this->Todo($this->file, 'subtitle not found yet.');
				}
			}
		}
		
		// MKVMERGE
		$finaloutput = self::OUTPUT_DIRECTORY.'/';
		$patt = array(
			'%year%',
			'%title%',
		);
		$replace = array(
			$year,
			$data->title,
		);
		$finaloutput .= str_replace($patt, $replace, self::MOVIEOUTFILE);
		$finaloutput = $this->removeIllegalChars($finaloutput);
		$title = str_replace($patt, $replace, self::MOVIETITLE);
		$MKVMerge = new MKVMerge($this->addVar('finaloutputfile', $finaloutput));
		$MKVMerge
			->Title($this->addVar('title', $title))
			->addTags($tagfile);
		if($XVIDOLD) //XVid - no reenc, only remux in mkv with covers and tags
			$MKVMerge->addTrack(MKVMergeTrack::__New($this->file));
		else
			$MKVMerge
				->addTrack(MKVMergeTrack::__New($this->file)
					->noVideo()->noAudio()->noAttachments())
				->addTrack(MKVMergeTrack::__New($tempoutfile));
		if(isset($posterfile))
			$MKVMerge->addPoster($posterfile);
		if(isset($subtitles_files) && !empty($subtitles_files))
			foreach($subtitles_files as $s)
				$MKVMerge->addTrack(
					MKVMergeTrack::__New($s['file'])
					->Language($s['lang'])
					->subCharset('utf-8'));
		
		// GEN BASH
		$output = '##FFMPEG'."\n".$ffmpeg;
		$output .= "\n\n##MKVMerge\n".$MKVMerge;
		$this->genBash($output, true);
		return true;
	}
 
 }
 
 ?>
