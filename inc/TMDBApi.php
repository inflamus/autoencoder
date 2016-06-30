<?php

/*   // Genres 
{
    "genres": [
        {
            "id": 28,
            "name": "Action"
        },
        {
            "id": 12,
            "name": "Adventure"
        },
        {
            "id": 16,
            "name": "Animation"
        },
        {
            "id": 35,
            "name": "Comedy"
        },
        {
            "id": 80,
            "name": "Crime"
        },
        {
            "id": 105,
            "name": "Disaster"
        },
        {
            "id": 99,
            "name": "Documentary"
        },
        {
            "id": 18,
            "name": "Drama"
        },
        {
            "id": 82,
            "name": "Eastern"
        },
        {
            "id": 2916,
            "name": "Erotic"
        },
        {
            "id": 10751,
            "name": "Family"
        },
        {
            "id": 10750,
            "name": "Fan Film"
        },
        {
            "id": 14,
            "name": "Fantasy"
        },
        {
            "id": 10753,
            "name": "Film Noir"
        },
        {
            "id": 10769,
            "name": "Foreign"
        },
        {
            "id": 36,
            "name": "History"
        },
        {
            "id": 10595,
            "name": "Holiday"
        },
        {
            "id": 27,
            "name": "Horror"
        },
        {
            "id": 10756,
            "name": "Indie"
        },
        {
            "id": 10402,
            "name": "Music"
        },
        {
            "id": 22,
            "name": "Musical"
        },
        {
            "id": 9648,
            "name": "Mystery"
        },
        {
            "id": 10754,
            "name": "Neo-noir"
        },
        {
            "id": 1115,
            "name": "Road Movie"
        },
        {
            "id": 10749,
            "name": "Romance"
        },
        {
            "id": 878,
            "name": "Science Fiction"
        },
        {
            "id": 10755,
            "name": "Short"
        },
        {
            "id": 9805,
            "name": "Sport"
        },
        {
            "id": 10758,
            "name": "Sporting Event"
        },
        {
            "id": 10757,
            "name": "Sports Film"
        },
        {
            "id": 10748,
            "name": "Suspense"
        },
        {
            "id": 10770,
            "name": "TV movie"
        },
        {
            "id": 53,
            "name": "Thriller"
        },
        {
            "id": 10752,
            "name": "War"
        },
        {
            "id": 37,
            "name": "Western"
        }
    ]
}*/

/*   // Configuration
{
  "images": {
    "base_url": "http://image.tmdb.org/t/p/",
    "secure_base_url": "https://image.tmdb.org/t/p/",
    "backdrop_sizes": [
      "w300",
      "w780",
      "w1280",
      "original"
    ],
    "logo_sizes": [
      "w45",
      "w92",
      "w154",
      "w185",
      "w300",
      "w500",
      "original"
    ],
    "poster_sizes": [
      "w92",
      "w154",
      "w185",
      "w342",
      "w500",
      "w780",
      "original"
    ],
    "profile_sizes": [
      "w45",
      "w185",
      "h632",
      "original"
    ],
    "still_sizes": [
      "w92",
      "w185",
      "w300",
      "original"
    ]
  },
  "change_keys": [
    "adult",
    "also_known_as",
    "alternative_titles",
    "biography",
    "birthday",
    "budget",
    "cast",
    "character_names",
    "crew",
    "deathday",
    "general",
    "genres",
    "homepage",
    "images",
    "imdb_id",
    "name",
    "original_title",
    "overview",
    "plot_keywords",
    "production_companies",
    "production_countries",
    "releases",
    "revenue",
    "runtime",
    "spoken_languages",
    "status",
    "tagline",
    "title",
    "trailers",
    "translations"
  ]
}*/

require_once('HTTPRequest.php');
class TMDBApi extends HTTPRequest
{
	const KEY = '5c8533aacb1fa275a5113d0728268d5a';
	const BASEURL = 'http://api.themoviedb.org/3/';
	const IMGURL  = 'http://image.tmdb.org/t/p/';
	const PREF_LANG = 'fr';
	const FALLBACK_LANG = 'en';  //when data are not found in pref_lang, fallback onto this.
	const IMAGE_INCLUDE_LANGUAGE = false; // could be en or null or en,null to fallback
	
	const POSTER_SIZE_W780 = 'w780';
	const POSTER_SIZE_W500 = 'w500';
	const POSTER_SIZE_W396 = 'w396';
	const POSTER_SIZE_W342 = 'w342';
	const POSTER_SIZE_W185 = 'w185';
	const POSTER_SIZE_W154 = 'w124';
	const POSTER_SIZE_W92  = 'w92';
// 	const POSTER_SIZE_DEFAULT = self::POSTER_SIZE_W500;
 	const POSTER_SIZE_DEFAULT = 'original';
 	const CACHE_POSTER = true; // turn to false to disable poster cache.
	
	private $lang = self::PREF_LANG;
	private $image_include_language = self::IMAGE_INCLUDE_LANGUAGE;
	private $key = self::KEY;
	
	public static $Genres = array (
		28 => 
		array (
		'fr' => 'Action',
		'en' => 'Action',
		),
		12 => 
		array (
		'fr' => 'Aventure',
		'en' => 'Adventure',
		),
		16 => 
		array (
		'fr' => 'Animation',
		'en' => 'Animation',
		),
		35 => 
		array (
		'fr' => 'Comédie',
		'en' => 'Comedy',
		),
		80 => 
		array (
		'fr' => 'Crime',
		'en' => 'Crime',
		),
		105 => 
		array (
		'fr' => 'Catastrophe',
		'en' => 'Disaster',
		),
		99 => 
		array (
		'fr' => 'Documentaire',
		'en' => 'Documentary',
		),
		18 => 
		array (
		'fr' => 'Drame',
		'en' => 'Drama',
		),
		82 => 
		array (
		'fr' => 'Est',
		'en' => 'Eastern',
		),
		2916 => 
		array (
		'fr' => 'Érotique',
		'en' => 'Erotic',
		),
		10751 => 
		array (
		'fr' => 'Familial',
		'en' => 'Family',
		),
		10750 => 
		array (
		'fr' => 'Fan Film',
		'en' => 'Fan Film',
		),
		14 => 
		array (
		'fr' => 'Fantastique',
		'en' => 'Fantasy',
		),
		10753 => 
		array (
		'fr' => 'Film Noir',
		'en' => 'Film Noir',
		),
		10769 => 
		array (
		'fr' => 'Étranger',
		'en' => 'Foreign',
		),
		36 => 
		array (
		'fr' => 'Histoire',
		'en' => 'History',
		),
		10595 => 
		array (
		'fr' => 'Vacances',
		'en' => 'Holiday',
		),
		27 => 
		array (
		'fr' => 'Horreur',
		'en' => 'Horror',
		),
		10756 => 
		array (
		'fr' => 'Indie',
		'en' => 'Indie',
		),
		10402 => 
		array (
		'fr' => 'Musique',
		'en' => 'Music',
		),
		22 => 
		array (
		'fr' => 'Musical',
		'en' => 'Musical',
		),
		9648 => 
		array (
		'fr' => 'Mystère',
		'en' => 'Mystery',
		),
		10754 => 
		array (
		'fr' => 'Neo-noir',
		'en' => 'Neo-noir',
		),
		1115 => 
		array (
		'fr' => 'Road Movie',
		'en' => 'Road Movie',
		),
		10749 => 
		array (
		'fr' => 'Romance',
		'en' => 'Romance',
		),
		878 => 
		array (
		'fr' => 'Science-Fiction',
		'en' => 'Science Fiction',
		),
		10755 => 
		array (
		'fr' => 'Court-Métrage',
		'en' => 'Short',
		),
		9805 => 
		array (
		'fr' => 'Sport',
		'en' => 'Sport',
		),
		10758 => 
		array (
		'fr' => 'Sporting Event',
		'en' => 'Sporting Event',
		),
		10757 => 
		array (
		'fr' => 'Sports Film',
		'en' => 'Sports Film',
		),
		10748 => 
		array (
		'fr' => 'Suspense',
		'en' => 'Suspense',
		),
		10770 => 
		array (
		'fr' => 'Téléfilm',
		'en' => 'TV movie',
		),
		53 => 
		array (
		'fr' => 'Thriller',
		'en' => 'Thriller',
		),
		10752 => 
		array (
		'fr' => 'Guerre',
		'en' => 'War',
		),
		37 => 
		array (
		'fr' => 'Western',
		'en' => 'Western',
		),
		);
	
	public function __construct(
		$lang = self::PREF_LANG, 
		$include_image = self::IMAGE_INCLUDE_LANGUAGE,
		$key = self::KEY)
	{
		$this->lang = $lang;
		$this->image_include_language = $include_image;
		$this->key = $key;
		return $this;
	}
	
	public static function __New(
		$lang = self::PREF_LANG, 
		$include_image = self::IMAGE_INCLUDE_LANGUAGE,
		$key = self::KEY)
	{
		return new self($lang, $include_image, $key);
	}
	
	public function implodeGenres($lang=self::PREF_LANG)
	{
		$genre = array();
		foreach($this->data->genres as $g)
			$genre[] = self::$Genres[$g->id][$lang];
		return implode(', ', $genre);
	}
	
	public function implode($key)
	{
		$arr = array();
		foreach($this->data->$key as $v)
			$arr[] = $v->name;
		return implode(', ', $arr);
	}
	
	public function implodeProductionCountries($mode = 'iso_3166_1')
	{
		$arr = array();
		foreach($this->data->production_countries as $v)
			$arr[] = $v->$mode;
		return implode(', ', $arr);
	}
	
	//alias
	public function searchMovie($search, $year=null)
	{
		return $this->findMovie($search, $year);
	}
	
	public function findMovie($search, $year = null)
	{
// 		print 'called with "'.$search.'" ('.$year.')';
		$this->urlpath = 'search/movie';
		if(!is_null($year) && strlen((string)$year)==4)
			$this->query['year'] = $year;
		else
			if(isset($this->query['year']))
				unset($this->query['year']);
		$this->query['query'] = strtolower($search);
		$s = new TMDBSearchResult($this->sendRequest());
// 		exit(var_dump(is_null($year)));
// 		print_r($s);
		if($s->total_results == 0 && !is_null($year)) // if no result with this year, try to search without the parameter. (wrong year number...)
			return $this->findMovie($search);
		return $s;
	}
	
	public function searchTv($search, $fady = null)
	{
		return $this->findTv($search, $fady);
	}
	
	public function findTv($search, $firstairdate_year = null)
	{
		$this->urlpath = 'search/tv';
		if(!is_null($firstairdate_year) && count((int)$firstairdate_year)==4)
			$this->query['first_air_date_year'] = $firstairdate_year;
		$this->query['query'] = strtolower($search);
		return new TMDBSearchResult($this->sendRequest(), true);
	}
	
	protected function changeLang($lang = self::FALLBACK_LANG)
	{
		$this->lang = $lang;
		return $this->sendRequest();
	}
	
	protected function sendRequest()
	{
		$this	->addQuery('api_key', $this->key)
			->addQuery('language', $this->lang);
		if($this->image_include_language != false)
			$this->addQuery('include_image_language', $this->image_include_language);
		$this->setUrl(self::BASEURL);

		// (abstract)HTTPRequest::sendRequest();
		return parent::sendRequest();
	}
	
	protected function _Poster($url, $size = self::POSTER_SIZE_DEFAULT)
	{
		if(empty($url))
			return false;
		
		return self::IMGURL.$size.$url;
	}

	public function __get($name)
	{ // works on tv, seasons, episodes, movie.
		return $this->data->$name;
	}
		
	public function PosterURL($size = self::POSTER_SIZE_DEFAULT)
	{
		if(!isset($this->data->poster_path))
			return false;
		$url = $this->data->poster_path;
		return $this->_Poster($url, $size);
	}
	
	private function _Poster_Cache($url)
	{
		return sys_get_temp_dir().'/php_tmdbapi_poster_cache_'.md5($url).strrchr('.', $url);
	}
	
	public function getPoster($file, $size = self::POSTER_SIZE_DEFAULT)
	{
		if(!$url = $this->PosterURL($size))
			return false;
		if(self::CACHE_POSTER)
			if(file_exists($cache = $this->_Poster_Cache($url)) && !file_exists($file))
				copy($cache, $file);
			else
			{
				copy($url, $cache);
				copy($cache, $file);
			}
		else
			if(!file_exists($file))
				copy($url, $file);
		return $this;
	}
	
	final public function Credits()
	{
		$this->urlpath .= '/credits';
		return new TMDBCredits($this->sendRequest());
	}
	
	public function toArray()
	{
		return (array) $this->data;
	}
}

class TMDBSearchResult extends TMDBApi
{
	private $responsestring = '';
	public $total_results = 0;
	public $total_pages = 0;
	
	private $results = array();
	private $cursor = -1;
	
	private $result_type = 0; // 0= movie, 1= tv;
	
	public function __construct($result, $is_tv = false)
	{
		$this->total_results = $result->total_results;
		$this->total_pages = $result->total_pages;
		$this->results = $result->results;
		$this->result_type = $is_tv;
		if($this->total_results == (int)0) // if no results, return false;
			return false;
		return $this;
	}
	
	public function totalResults()
	{
		return (int) $this->total_results;
	}
	
	public function getBest($flush = false)
	{
		$this->cursor = 0;
		return $flush ? $this->results[0] : $this;
	}
	
	public function getNth($n, $flush = false)
	{
		$this->cursor = $n;
		return $flush ? $this->results[$n] : $this;
	}
	
	public function getAll($flush = false)
	{
		$this->cursor = -1;
		return $flush ? $this->results : $this;
	}
	
	public function __get($key)
	{
		$key = strtolower($key);
		if($this->cursor == -1)
		{
			$re = array();
			foreach($this->results as $r)
				$re[] = $r[$key];
			return $re;
		}
		return $this->results[$this->cursor]->$key;
	}
	
 	public function __call($name, $args)
 	{
 		if($this->cursor == -1)
 		{
 			$re = array();
 			foreach($this->results as $r)
 				$re[] = call_user_func(array(&$this, $name), $args);
 			return $re;
 		}
 		return call_user_func(array(&$this, $name), $args);
 	}
	
	public function toArray()
	{
		return (array)$this->results;
	}
	
	public function Poster($size = 'original')
	{
		if($this->cursor == -1)
		{
			$re = array();
			foreach($this->results as $r)
				$re[] = $this->_Poster($r->poster_path, $size);
			return $re;
		}
		$url = $this->results[$this->cursor]->poster_path;
		return $this->_Poster($url, $size);
	}
	
	public function Movie()
	{
		if($this->result_type)
			return 'Error, youre reaching Movie Method on a TV search result.';
		if($this->cursor == -1)
		{
			$re = array();
			foreach($this->results as $r)
				$re[] = new TMDBMovie($r->id);
			return $re;
		}
		return new TMDBMovie($this->results[$this->cursor]->id);
	}
	
	public function Tv()
	{
		if(!$this->result_type)
			return 'Error, youre reachin Tv method on a Movie search result.';
		if($this->cursor == -1)
		{
			$re = array();
			foreach($this->results as $r)
				$re[] = new TMDBTv($r->id);
			return $re;
		}
// 		print_r($this->results[$this->cursor]->id);
		return new TMDBTv($this->results[$this->cursor]->id);
	}
	 // Alias of Tv()
	public function TvShow()
	{
		return $this->Tv();
	}
}

class TMDBTv extends TMDBApi
{
	protected $data = array();
	public function __construct($id)
	{
		if(is_object($id))
			$id = $id->id;
		if(is_array($id))
			$id = $id['id'];
		$this->setPath('tv/'.$id);
// 		exit($this->urlpath);
		$this->data = $this->sendRequest();
		//print_r($this->sendRequest());
		return $this;
	}
	
	public function Poster($size = 'original')
	{
		$url = $this->data->poster_path;
		return $this->_Poster($url, $size);
	}
	
	public function Id()
	{
		return $this->data->id;
	}
	
	public function Season($season)
	{
		return new TMDBTvSeason($this->Id(), $season);
	}
	
	public function Episode($season, $ep)
	{
		if(!is_int($ep))
			throw new Exception('Please enter a valid Episode number in TMDBTv::Episode(season,ep)');
		return new TMDBTvSeasonEpisode($this->Id(), $season, $ep);
	}

}

class TMDBTvSeason extends TMDBTv
{
	private $tvid, $seasonid = 0;
	protected $data = array();
	public function __construct($id, $season)
	{
		$this->setUrlPath('tv/'.$id.'/season/'.$season);
		$this->data = $this->sendRequest();
		$this->tvid = $id;
		$this->seasonid = $season;
		$this->season_number = $season;
		
		return $this;
	}

	public function Episode($ep, $nodata = null)
	{
		if(!is_null($nodata))	throw new Notification('The second argument passed in TMDBTvSeason::Episode() is not required.');
		return new TMDBTvSeasonEpisode($this->tvid, $this->seasonid, $ep, $this->episodes[(int)$ep-1]);
	}

}

class TMDBTvSeasonEpisode extends TMDBTvSeason
{
	private $tvid, $seasonid, $epid = 0;
	protected $data = array();
	public function __construct($tvid, $seasonid, $ep, $data = array())
	{
		$this->tvid = $tvid;
		$this->seasonid = $seasonid;
		$this->epid = $ep;

		$this->setUrlPath('tv/'.$tvid.'/season/'.$seasonid.'/episode/'.$ep);
		if(!empty($data))
			$this->data = $data;
		else
			$this->data = $this->sendRequest();
		if(empty($this->name))
			$this->data = $this->changeLang(); //implicitely sendRequest()
// 		print_r($this->data);
		if(!isset($this->season_number))
			$this->season_number = $seasonid;
		if(!isset($this->data->episode_number))
			$this->episode_number = $ep;
		//print $this->urlpath;
		return $this;
	}

	
// 	public function Season($season = null)
// 	{
// 		if(!is_int($season))	$season = $this->seasonid;
// 		return new TMDBTvSeason($this->tvid, $season);
// 	}
}

class TMDBMovie extends TMDBApi
{
	protected $data = array();
	public function __construct($id)
	{
		if(is_object($id))
			$id = $id->id;
		if(is_array($id))
			$id = $id['id'];
		$this->setUrlPath('movie/'.$id);
		$this->data = $this->sendRequest();
		return $this;
	}

}

class TMDBCredits extends TMDBApi
{
	protected $data = array();
	public $crewByDept=array(), $crewByJob=array(), $crew=array(), $casting=array(), $characters = array();
	public function __construct($data)
	{
		$this->data = $data;
		$cast = array();
		if(isset($this->data->cast))
			$cast = array_merge($cast, $this->data->cast);
		if(isset($this->data->guest_stars))
			$cast = array_merge($cast, $this->data->guest_stars);
		foreach($cast as $c)
		{
			$this->casting[] = $c->name.' ('.$c->character.')';
			$this->characters[] = $c->character.' ('.$c->name.')';
		}
		foreach($this->data->crew as $c)
		{
			$this->crewByDept[strtolower($c->department)][] = $c->name . ' ('.$c->job.')';
			$this->crewByJob[strtolower($c->job)][] = $c->name;
		}
		return $this;
	}
	
	public function Cast()
	{
		return $this->data->cast;
	}
	
	//Alias of Cast();
	public function Actors()
	{
		return $this->Cast();
	}
	
	// Alias of GuestStars();
	public function Guests()
	{ 
		return $this->GuestStars();
	}
	
	public function GuestStars()
	{
		return $this->data->guest_stars;
	}
	
	public function Crew($key = null)
	{
// 		print_r($this->crewByJob);
		$key = strtolower($key);
		return !is_null($key) ? (
			array_key_exists($key, $this->crewByDept) ? 
				implode(', ', $this->crewByDept[$key]) :
				( array_key_exists($key, $this->crewByJob) ?
					implode(', ', $this->crewByJob[$key]) : '') )
				:
			$this->data->crew;
	}
	
	public function implodeFullCast()
	{
		return implode(', ', $this->casting);
	}
	
	public function implodeCharacters()
	{
		return implode(', ', $this->characters);
	}
	
	public function implodeActors()
	{
		return $this->implodeFullCast();
	}
	
	public function implodeGuestStars()
	{
		$cast = array();
		foreach($this->GuestStars() as $c)
			$cast[] = $c->name.' ('.$c->character.')';
		return implode(', ', $cast);
	}
	
	public function implodeWriters()
	{
		$return = array();
		if($this->Crew('Storyboard') != '')
			$return[] = $this->Crew('Storyboard');
		if($this->Crew('Story') != '')
			$return[] = $this->Crew('Story');
		if($this->Crew('Writer') != '')
			$return[] = $this->Crew('Writer');
		if($this->Crew('Author') != '')
			$return[] = $this->Crew('Author');
		return implode(', ', $return);
	}
	
}

?>