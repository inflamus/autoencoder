<?php

class MKVTagger
{
	const MIME = 'video/x-matroska';
	const CHARSET = 'UTF-8';
	
	const STRICT_TAGS = false; // true => report every non standards tags. (E_NOTICE)
	
	static public $OfficialEntities = array(
// 		Nesting Information (tags containing other tags)
		'ORIGINAL',
		'SAMPLE',
		'COUNTRY',
// 		Organizational Information
		'TOTAL_PARTS',
		'PART_NUMBER',
		'PART_OFFSET',
// 		Titles
		'TITLE',
		'SUBTITLE',
// 		Nested Information (tags contained in other tags)
		'URL',
		'SORT_WITH',
		'INSTRUMENTS',
		'EMAIL',
		'ADDRESS',
		'FAX',
		'PHONE',
// 		Entities
		'ARTIST',
		'LEAD_PERFORMER',
		'ACCOMPANIMENT',
		'COMPOSER',
		'ARRANGER',
		'LYRICS',
		'LYRICIST',
		'CONDUCTOR',
		'DIRECTOR',
		'ASSISTANT_DIRECTOR',
		'DIRECTOR_OF_PHOTOGRAPHY',
		'SOUND_ENGINEER',
		'ART_DIRECTOR',
		'PRODUCTION_DESIGNER',
		'CHOREGRAPHER',
		'COSTUME_DESIGNER',
		'ACTOR',
		'CHARACTER',
		'WRITTEN_BY',
		'SCREENPLAY_BY',
		'EDITED_BY',
		'PRODUCER',
		'COPRODUCER',
		'EXECUTIVE_PRODUCER',
		'DISTRIBUTED_BY',
		'MASTERED_BY',
		'ENCODED_BY',
		'MIXED_BY',
		'REMIXED_BY',
		'PRODUCTION_STUDIO',
		'THANKS_TO',
		'PUBLISHER',
		'LABEL',
// 		Search / Classification
		'GENRE',
		'MOOD',
		'ORIGINAL_MEDIA_TYPE',
		'CONTENT_TYPE',
		'SUBJECT',
		'DESCRIPTION',
		'KEYWORDS',
		'SUMMARY',
		'SYNOPSIS',
		'INITIAL_KEY',
		'PERIOD',
		'LAW_RATING',
		'ICRA',
// 		Temporal Information
		'DATE_RELEASED',
		'DATE_RECORDED',
		'DATE_ENCODED',
		'DATE_TAGGED',
		'DATE_DIGITIZED',
		'DATE_WRITTEN',
		'DATE_PURCHASED',
// 		Spacial Information
		'RECORDING_LOCATION',
		'COMPOSITION_LOCATION',
		'COMPOSER_NATIONALITY',
// 		Personnal
		'COMMENT',
		'PLAY_COUNTER',
		'RATING',
// 		Technical Information
		'ENCODER',
		'ENCODER_SETTINGS',
		'BPS',
		'FPS',
		'BPM',
		'MEASURE',
		'TUNING',
		'REPLAYGAIN_GAIN',
		'REPLAYGAIN_PEAK',
// 		Identifiers
		'ISRC',
		'MCDI',
		'ISBN',
		'BARCODE',
		'CATALOG_NUMBER',
		'LABEL_CODE',
		'LCCN',
// 		Commercial
		'PURCHASE_ITEM',
		'PURCHASE_INFO',
		'PURCHASE_OWNER',
		'PURCHASE_PRICE',
		'PURCHASE_CURRENCY',
// 		Legal
		'COPYRIGHT',
		'PRODUCTION_COPYRIGHT',
		'LICENSE',
		'TERMS_OF_USE',
	);
	
	public $Tags = array();
	private $StrictTags = self::STRICT_TAGS;
	
	public static function __New()
	{
		return new self();
	}
	
	public function __construct()
	{
		return $this;
	}
	
	public function StrictReport($v = self::STRICT_TAGS)
	{
		$this->StrictTags = $v;
		return $this;
	}
	
	public function setReleaseDate($rel)
	{
		$this->Tags['DATE_RELEASED'] = $rel;
		return $this;
	}
	
	public function fetchFromTMDB($TMDB)
	{
		if($TMDB instanceof TMDBMovie)
		{
			$this	->setTitle	($TMDB->title)
				->setReleaseDate($TMDB->release_date)
				->setOriginal_title($TMDB->original_title)
				->setSummary	($TMDB->overview)
				->setDescription($TMDB->overview)
				->setSynopsis	($TMDB->tagline)
				->setGenre	($TMDB->implodeGenres())
				->setContent_Type	('Film')
				->setProduction_Studio	($TMDB->implode('production_companies'))
				->setRecording_Location	($TMDB->implodeProductionCountries())
			;
			$Credits = $TMDB->Credits();
			$this
				->setActor	($Credits->implodeActors())
				->setCharacter	($Credits->implodeCharacters())
				->setDirector	($Credits->Crew('Director'))
				->setProducer	($Credits->Crew('Producer'))
				->setExecutive_Producer	($Credits->Crew('Executive Producer'))
				->setDirector_Of_Photography	($Credits->Crew('Director of Photography'))
				->setScreenplay_By	($Credits->Crew('Screenplay'))
				->setWritten_By	($Credits->implodeWriters())
				->setArt_Director	($Credits->Crew('Art Direction'))
				->setSound_Engineer	($Credits->Crew('Sound'))
				->setCostume_Designer	($Credits->Crew('Costume Design'))
				->setProduction_Designer($Credits->Crew('Production Design'))
				->setChoregrapher	($Credits->Crew('Choregrapher'))
			;
		}
		elseif($TMDB instanceof TMDBTvSeasonEpisode)
		{
			$this	->setTitle	($TMDB->name)
// 				->setSummary	($TMDB->overview)
				->setDescription($TMDB->overview)
				->setSynopsis	($TMDB->overview)
// 				->setComments	($TMDB->overview)
// 				->setComment	($TMDB->overview)
				->setReleaseDate($TMDB->air_date)
				->setSeason	($TMDB->season_number)
				->setEpisode	($TMDB->episode_number)
				->setContent_Type('TV Show')
			;
			$Credits = $TMDB->Credits();
			$this
				->setActor	($Credits->implodeActors())
				->setCharacter	($Credits->implodeCharacters())
				->setGuests	($Credits->implodeGuestStars())
				->setDirector	($Credits->Crew('Director'))
				->setProducer	($Credits->Crew('Producer'))
				->setExecutive_Producer	($Credits->Crew('Executive Producer'))
				->setDirector_Of_Photography	($Credits->Crew('Director of Photography'))
				->setScreenplay_By	($Credits->Crew('Screenplay'))
				->setWritten_By	($Credits->implodeWriters())
				->setArt_Director	($Credits->Crew('Art Direction'))
				->setSound_Engineer	($Credits->Crew('Sound'))
				->setCostume_Designer	($Credits->Crew('Costume Design'))
				->setProduction_Designer($Credits->Crew('Production Design'))
				->setChoregrapher	($Credits->Crew('Choregrapher'))
			;
		}
		elseif(is_array($TMDB))
			throw new Exception('You need to pass a valid TMDB Object (such as TMDBMovie or TMDBTvSeasonEpisode) to MKVTagger::fetchFromTMDB.');
		else
			throw new Exception('Unknown error occured at MKVTagger::fetchFromTMDB.');
		
		return $this;
	}
	
	public function __call($name, $args)
	{
		$what = substr($name, 3);
		switch(substr($name, 0, 3))
		{
			default:
			case 'set':
				$this->Tags[$what] = $args[0];
				return $this;
			break;
			case 'get':
				return $this->Tags[$what];
			break;
// 			default:
// 				call_user_func(array(&$this, $name), $args);
// 			break;
		}
	}
	
	public function __set($name, $value)
	{
		$this->Tags[$name] = $value;
		return $this;
	}
	
	public function __get($name)
	{
		return $this->Tags[$name];
	}
	
	public function toXML($file = null)
	{
		$this->DATE_TAGGED = date('Y-m-d');
		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->setIndent(true);
		$xml->setIndentString("\t");
		$xml->startDocument('1.0', self::CHARSET);
		$xml->startDTD('Tags', null, 'matroskatags.dtd');
		$xml->endDTD();
		$xml->startElement('Tags');
		$xml->startElement('Tag');
		foreach($this->Tags as $tag => $value)
		{
			if(empty($value))
				continue;
			if(!in_array(strtoupper($tag), self::$OfficialEntities) && $this->StrictTags)
				trigger_error('Matroska Standards : <'.strtoupper($tag).'> is not a valid matroska tag. See http://matroska.org/technical/specs/tagging/index.html');
			$xml->startElement('Simple');
				$xml->startElement('Name');
					$xml->text(strtoupper($tag));
				$xml->endElement();
				$xml->startElement('String');
					$xml->text($value);
				$xml->endElement();
			$xml->endElement();
		}
		$xml->endElement();
		$xml->endElement();
		$xml->endDocument();
		if(!is_null($file))
			file_put_contents($file, $xml->outputMemory());
		else
			return $xml->outputMemory();
		return $this;	
	}

	public function insertInto($file)
	{
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if($finfo->file($file) != self::MIME)
			throw new Exception ('File is not a Matroska.');
		$this->toXML($xml = tempnam(sys_get_temp_dir(), 'MKVTAGGER_XML_'));
		$mkvpropedit = new MKVPropEdit($file);
		$mkvpropedit->AddTags($xml)->Exec();
		unlink($xml);
		return true;
	}
	// alias of insertInto();
	public function toFile($file)
	{
		return $this->insertInto($file);
	}
	
	public static function loadXML($xml)
	{
		$el = new simpleXMLElement($xml);
		$that = new self();
		foreach($el->Tag[0] as $t)
			if(isset($t->Name))
				$that->Tags[(string)$t->Name] = (string)$t->String;
		return $that;
	}
}

abstract class MKVCLI
{
	const UI_LANGUAGE	= 'en_US';

	protected $file = '';
	protected $args = '';
	protected $executed = false;
	
	public static $AcceptedCoverMime = array(
		'image/jpg' => 'jpg',
		'image/jpeg'=> 'jpg',
		'image/png' => 'png'
		);
	
	protected function _($r)
	{
		$this->args .= $r.' ';
		return $this;
	}
}

class MKVPropEdit extends MKVCLI
{	

	const EXEC_ON_DESTRUCT	= false;
	
	public function __construct($file)
	{
		$this->file = $file;
		return $this;
	}
	
	public function __destruct()
	{
		if(self::EXEC_ON_DESTRUCT)
			$this->Exec();
		return;
	}
	
	public function Exec()
	{
		if($this->executed)
			return false;
		exec((string)$this); // call __toString()
		return $this->executed = true;
	}
	
	public function __toString()
	{
		return 'mkvpropedit "'
			.$this->file.'" '
			.$this->args;
	}
	
	public function AddTags($xmlfile)
	{
		if($xmlfile instanceof MKVTagger)
			$xmlfile->toXML($file = tempnam(sys_get_temp_dir(), 'MKVPROPEDIT_'));
		else
			$file = $xmlfile;
		$this->_('--tags "global:'.$file.'"');
		return $this;
	}
	
	public function AddCover($imgfile)
	{
		$fi = new finfo(FILEINFO_MIME_TYPE);
		if(!in_array($mime = $fi->file($imgfile), array_keys(parent::$AcceptedCoverMime)))
			throw new Exception('Unaccepted file type '.$imgfile.' ['.$mime.']');
		$this	->_('--attachment-description cover')
			->_('--attachment-name cover.'.parent::$AcceptedCoverMime[$mime])
			->_('--attachment-mime-type "'.$mime.'"')
			->_('--add-attachment "'.$imgfile.'"');
		return $this;
	}
	
	public function SetCover($imgfile)
	{
		$id = MKVInfo::__New($this->file)->getCover();
		if($id != false)
			$this->DeleteAttachment($id);
		return $this->AddCover($imgfile);
	}

	public function AddChapters($filename = '')
	{
		$this	->_('--chapters "'.$filename.'"');
		return $this;
	}
	
	public function RemoveChapters()
	{
		return $this->AddChapters();
	}
	public function DeleteChapters()
	{
		return $this->RemoveChapters();
	}
	
	public function DeleteAttachment($id)
	{
		$this	->_('--delete-attachment '.(int) ($id instanceof MKVInfoLine ? $id->id : $id));
		return $this;
	}
}

class MKVMerge extends MKVCLI
{
	private $options = array();
	protected $tomerge = array();
	public function __construct($outfile)
	{
		if(substr($outfile, -4) != '.mkv')
			$outfile .= '.mkv';
		$this->file = $outfile;
	}
	
	public function Title($title)
	{
		$this->options[] = '--title "'.$title.'"';
		return $this;
	}
	
	public function addChapter($chapfile)
	{
		$this->options[] = '--chapters "'.$chapfile.'"';
		return $this;
	}
	
	//alias of addCover
	public function addPoster($imgfile)
	{
		return $this->addCover($imgfile);
	}
	
	public function addCover($imgfile)
	{
		$fi = new finfo(FILEINFO_MIME_TYPE);
		if(!in_array($mime = $fi->file($imgfile), array_keys(parent::$AcceptedCoverMime)))
			throw new Exception('Unaccepted file type '.$imgfile.' ['.$mime.']');
		$this->options[] = '--attachment-description cover';
		$this->options[] = '--attachment-name cover.'.parent::$AcceptedCoverMime[$mime];
		$this->options[] = '--attach-file "'.$imgfile.'"';
		return $this;
	}
	
	public function addTags($mkvtag)
	{
		if($mkvtag instanceof MKVTagger)
			$mkvtag->toXML($file = tempnam(sys_get_temp_dir(), 'MKVMERGE_'));
		else
			$file = $mkvtag;
		$this->options[] = '--global-tags "'.$file.'"';
		return $this;
	}
	
	public function addTrack(MKVMergeTrack $track)
	{
		$this->tomerge[] = (string) $track;
		return $this;
	}
	
	public function Exec()
	{
		if($this->executed)
			return false;
		exec((string)$this);
		return $this->executed = true;	
	}
	
	public function __toString()
	{
		$this->args = 
			implode(' ', $this->options) .
			' ' .
			implode(' ', $this->tomerge);
		return 'mkvmerge -o "'
			.$this->file.'" '
			.$this->args;
	}
}

class MKVMergeTrack extends MKVMerge
{
	private $t_opt = array();
	private $track = '';
	
	static function __New($file)
	{
		return new self($file);
	}
	
	public function __construct($file)
	{
		$this->track = $file;
		return $this;
	}
	
	public function opt($key, $value = 1, $tid = 0)
	{
		//default values
		if(is_null($tid)) 	$tid = 0;
		if(is_null($value)) 	$value = 1;
		//func
		$this->t_opt[] =
			'--'.strtolower($key).' '.
			$tid.':'.
			$value;
		return $this;
	}
	
	public function no($key)
	{
		if(!substr($key, 3) == 'no-')
			$key = 'no-'.$key;
		$this->t_opt[] = '--'.strtolower($key);
		return $this;
	}
	
	public function select_tracks($key, $value)
	{
		if(!substr($key, -7) == '-tracks')
			$key .= '-tracks';
		$this->t_opt[] = '--'.$key.' '.
			$value;
		return $this;
	}
	
	public function __call($name, $args)
	{
		$key = preg_replace('/(?<!^)([A-Z])/', '-\\1', $name);
		$value = isset($args[0]) ? $args[0] : null;
		$tid = isset($args[1]) ? $args[1] : null;
		if(substr($key, 0, 3)=='no-') /// no-audio, no-subtitle kinda commands...
			return $this->no($key);
		elseif(substr($key, -7) == '-tracks') // audio-tracks, video-tracks...
			return $this->select_track($key, $value);
		else
			return $this->opt($key, $value, $tid);
		return $this;
	}
	
	public function __toString()
	{
		return implode(' ', $this->t_opt).
			' '.
			'"'.$this->track.'"';
	}
}

class MKVInfo extends MKVCLI
{
	private $infile	= '',
		$simple	= true,
		$output	= array(),
		$streams	= array()
		;
// 	protected 
// 		$tags	= array(),
// 		$tracks	= array(
// 			'Video' => array(),
// 			'Audio' => array(),
// 			'Subtitles' => array(),
// 		),
// 		$attachments	= array();
		
	public static function __New($infile, $simple = true)
	{
		return new self($infile, $simple);
	}
	
	public function __construct($infile, $simple = true) // simple use mkvmerge summary, otherwise it uses mkvinfo (unsupported yet)
	{
		$this->infile = $infile;
		$this->simple = $simple;
		if(!file_exists($this->infile))
			throw new Exception('Unable to locate File '.$this->infile);
	
		$this	->Exec()
			->parseOutput();
		
		return $this;
	}
	
	public function Exec()
	{
		exec((string)$this, $this->output);
		return $this;
	}
	
	public function __toString()
	{
		if($this->simple)
			return 'mkvmerge '
				.'--ui-language '.MKVCLI::UI_LANGUAGE.' '
				.'-i '.escapeshellarg($this->infile);
		else
			return 'mkvinfo '
				.'--ui-language '.MKVCLI::UI_LANGUAGE.' '
				.escapeshellarg($this->infile);
	}
		
	public function getOutput($and_die = false)
	{
		if($and_die)
			die(implode("\n", $this->output));
		else
			return implode("\n", $this->output);
	}
	
	public function getRaw($and_die = false)
	{
		return $this->getOutput($and_die);
	}
	
	private function parseOutput()
	{
		if($this->simple)
			foreach($this->output as $line)
			{
				$this->streams[] = new MKVInfoLineSimple($this->infile, $line);
			}
	}

	public function get($type, $id = null)
	{
		$re = array();
		foreach($this->streams as $s)
		{
			if($s->type != $type)
				continue;
			if($id != null && $this->id == $id)
				return $s;
			else
				$re[] = $s;
		}
		if(count($re) == 1)
			return $re[0];
		else
			return $re;
	}
	
	public function getList($type)
	{
		return $this->get($type);
	}
	
	public function getAttachmentsList()
	{
		return $this->getList('Attachment');
	}
	
	public function getAttachment($id = null)
	{
		return $this->get('Attachment', $id);
	}
	
	public function getAudioList()
	{
		return $this->getList('Audio');
	}
	
	public function getAudio($id=null)
	{
		return $this->get('Audio', $id);
	}
	
	public function getSubtitlesList()
	{
		return $this->getList('Subtitle');
	}
	
	public function getSubtitle($id = null)
	{
		return $this->get('Subtitle', $id);
	}
	
	public function getVideo()
	{
		return $this->get('Video');
	}
	
	public function getChapters()
	{
		return $this->get('Chapters');
	}
	
	public function getTags()
	{
		return $this->get('Tags');
	}

	public function getCover()
	{
		$list = $this->getAttachmentsList();
		if(empty($list))
			return false;
		if($list instanceof MKVInfoLine && substr($list->mime, 0, 5) == 'image')
			return $list;
		else
			return false;
		$wait = array();
		foreach($list as $l)
		{
			if(substr($list->mime, 5) == 'image')
				// good, it's a picture
				if($list->description != false 
					&& in_array($list->description, array('cover','Cover', 'FOLDER', 'Folder', 'folder', 'COVER', 'artwork', 'ARTWORK', 'Artwork')))
					return $l;
				else
					$wait[] = $l;
		}
		if(!empty($wait))
			return $wait[0];
	}
}

class MKVInfoLine extends MKVInfo
{
	protected $data = array();
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
		return $this;
	}
	
	public function __get($name)
	{
		if(isset($this->data[$name]))
			return $this->data[$name];
		else
// 			throw new Exception('Undefined variable '.$name);
			return false;
	}
	
	public function Extract($filename = null)
	{
		$ex = new MKVExtract($this->file);
		switch($this->type)
		{
			case 'Audio':
			case 'Video':
			case 'Subtitle':
				if($filename==null)
					throw new Exception('a valid filename should be specified');
				return $ex->Tracks($this, $filename);
			break;
			case 'Attachment':
				if($filename == null && !isset($this->data['filename']))
					throw new Exception('a valid filename should be specified');
				return $ex->Attachments($this, ($filename == null ? $this->data['filename'] || 'unknown.attachment' : $filename));
			break;
			case 'Tags':
				return $ex->Tags($filename);
			break;
			case 'Chapters':
				return $ex->Chapters($filename);
			break;
		}		
	}

	public function Delete()
	{
		$p = new MKVPropEdit($this->file);
		switch($this->type)
		{
			case 'Attachment':
				$p->DeleteAttachment($this);
			break;
			case 'Chapters':
				$p->RemoveChapters();
			break;
			default:
				die('deleting other tracks than attachments are not supported yet.');
			break;
		}
		$p->Exec();
		return $this;
	}
	public function Remove()
	{
		return $this->Delete();
	}
}

class MKVInfoLineSimple extends MKVInfoLine
{
	public function __construct($file, $line)
	{
		$this->file = $file;
		
		// parsing : tracks, attachment, piece jointe.
		if(preg_match('/([\w\'è ]+) ([0-9]+) ?: (.+)$/', $line, $data))
		{
			switch($data[1])
			{
				case 'Track ID': //en_us
				case 'Piste d\'identifiant': //fr_FR
				// you can add other locales here
					$this->codec = substr(strstr($data[3], '('), 1, -1);
					switch(substr($data[3], 0, 5))
					{
						case 'video':
							$this->type = 'Video';
						break;
						case 'audio':
							$this->type = 'Audio';
						break;
						case 'subti':
							$this->type = 'Subtitle';
						break;
					}
					$this->id = $data[2];
				break;
				case 'Attachment ID': //en_us
				case 'Pièce jointe ID': //fr_FR
					$this->type = 'Attachment';
					$this->id = $data[2];
					$more = explode(',', $data[3]);
					foreach($more as $l)
					{
						$l = trim($l);
						switch(substr($l, 0, 4))
						{
							case 'type':
								$this->mime = substr(strstr($l, "'"), 1, -1);
							break;
							case 'size': //en_us
							case 'tail': //fr_fr
								$this->size = (int)substr(strstr($l, ' '), 1);
							break;
							case 'desc':
								$this->description = substr(strstr($l, "'"), 1, -1);
							break;
							case 'file':
							case 'nom ':
								$this->filename = substr(strstr($l, "'"), 1, -1);
							break;
							default:
								$this->other[] = $l;
							break;
						}
					}
				break;
			}
		}
		elseif(preg_match('/global/i', $line)) // global tags
		{
			$this->type = 'Tags';
		}
		elseif(preg_match('/chap/i', $line)) // chapters
		{
			$this->type = 'Chapters';
		}
		else
		{
			$this->type = 'Unknown';
			$this->other = $line;
		}
		
		return $this;
	}

}

class MKVExtract extends MKVCLI
{
	const EXEC_ON_DESTRUCT = true;
	private $what = '', $arr = array();
	
	public static function __New($file)
	{
		return new self($file);
	}
	
	public function __construct($file)
	{
		$this->file = $file;
// 		$this->_('--ui-language '.MKVCLI::UI_LANGUAGE);
		
		return $this;
	}
	
	public function __toString()
	{
		$arr = array();
		foreach($this->arr as $id=>$filename)
			$arr[] = $id.':'.escapeshellarg($filename);
		return 'mkvextract '.
			$this->what.' '.
			escapeshellarg($this->file).' '.
			$this->args.' '.
			implode(' ', $arr);
	}
	
	public function __destruct()
	{
		if(self::EXEC_ON_DESTRUCT)
			$this->Exec();
		return;
	}
	
	public function Exec()
	{
		if($this->executed)
			return false;
		exec((string)$this, $out); // call __toString()
		$this->executed = true;
		if($this->what == 'chapters' || $this->what == 'tags')
			return implode("\n", $out);
		return true;
	}
	
	public function Extract($what, $arr = array())
	{
		if($this->what != '' && $what != $this->what)
			throw new Exception('you cannot extract '.$what.' and '.$this->what.' at the same time. Please MKVExtract::Exec() after '.$this->what.' and before '.$what);
			
		if(!in_array($what, array('tracks', 'attachments', 'chapters', 'tags', 'cuesheet', 'timecodes_v2', 'cues')))
			throw new Exception($what.' is not a valid mode for mkvextract');
		else
			$this->what = $what;
		
		if(isset($this->arr[key($arr)]))
			throw new Exception('ID '.key($arr).' is already assigned to an output.');
		else
			$this->arr += $arr;
		
		return $this;
	}
	
	public function Tags($filename = null)
	{
		if($filename != null)
			$this->arr = '>'.$filename;
		return $this->Extract('tags');
	}
	
	public function Chapters($filename = null, $simple = false)
	{
		if($simple)
			$this->_('--simple');
		if($filename != null)
			$this->arr = '>'.$filename;
		return $this->Extract('chapters');
	}
	
	public function Tracks($arr, $filename = '')
	{
		return $this->Extract('tracks', ($arr instanceof MKVInfoLine ? array($arr->id => $filename) : $arr));
	}
	
	public function Attachments($arr = array(), $filename = '')
	{
		return $this->Extract('attachments', ($arr instanceof MKVInfoLine ? array($arr->id => $filename) : $arr));
	}
	
	public function ExtractAttachments($arr = array())
	{
		return $this->Attachments($arr);
	}
	
	public function ExtractTracks($arr)
	{
		return $this->Tracks($arr);
	}
	
	public function ExtractChapters($filename = null, $simple = false)
	{
		return $this->Chapters($filename, $simple);
	}
	
	public function ExtractTags($filename = null)
	{
		return $this->Tags($filename);
	}
}

?>
