<?php
/*
NOt used !
Use OpenSubtitles instead
*/
exit('Not used. Use OpenSubtitles instead.'); 
require('HTTPRequest.php');
class SubtitleSeekerAPI extends HTTPRequest
{
	const KEY = "24430affe80bea1edf0e8413c3abf372a64afff2"; // got from autosub-bootlib project
	const BASEURL = 'http://api.subtitleseeker.com/';
	const DEFAULT_LANGUAGES = 'English,French';
		
	public function __construct()
	{
		
		return $this;
	}
	
	public function Search($q, $search_in = 'titles')
	{
		$this	->setUrlPath('search/')
			->addQuery('q', $q);
		return new SubtitleSeekerSearchResult($this->sendRequest())
	}

	protected function sendRequest()
	{
		$this	->setUrl(self::BASEURL)
			->addQuery('api_key',self::KEY)
			->addQuery('return_type', 'json'); // no deal.
		
		return $this->HandleErrors(parent::sendRequest());
	}
	
	private HandleErrors($result)
	{
		if($result->results->got_errors == 0)
			return $result;
		$errors = (array)$result->results->errors;
		$err_str = '';
		foreach($errors as $e)
			$err_str .= $e['short'].' : '.$e['msg']."\n";
		throw new Exception($err_str);
		return false;
	}
}

class SubtitleSeekerSearchResult extends SubtitleSeekerAPI
{
	public $search_term, $search_in, $returned_items, $total_matches, $html_search_url = '';
	public $items = array();
	private $cursor = -1;
	
	public function __construct($result)
	{
		$result = $result->results;
		$this->search_term = $result->search_term;
		$this->search_in = $result->search_in;
		$this->returned_items = $result->returned_items;
		$this->total_matches = $result->total_matches;
		$this->html_search_url = $result->html_search_url;
		$this->items = $result->items;
		if($this->return_items == (int)0)
			return false; //return false if no results;
		return $this;
	}
	
	public function getBest($flush = false)
	{
		$this->cursor = 0;
		return $flush ? $this->items[0] : $this;
	}
	
	public function getNth($n, $flush = false)
	{
		$this->cursor = $n;
		return $flush ? $this->items[$n] : $this;
	}
	/*
	public function getAll($flush = false)
	{
		$this->cursor = -1;
		return $flush ? $this->results : $this;
	}*/
	
	public function Build($language)
	{
		if($this->items[$this->cursor]->title_type == 'tv_titles')
			return $this->Tv();
		else
			return $this->Movie();
		return false;
	}
	
	public function Movie($languages = parent::DEFAULT_LANGUAGES)
	{
		return new SubtitleSeekerMovie($this->items[$this->cursor], $this->search_term, $languages)
	}
	
	public function Tv($languages = parent::DEFAULT_LANGUAGES)
	{
		return new SubtitleSeekerTv($this->items[$this->cursor], $this->search_term, $languages);
	}
}


class SubtitleSeekerMovie extends SubtitleSeekerAPI
{
	public function __construct($imdb, $release, $languages = parent::DEFAULT_LANGUAGES)
	{
		//because we can pass directly the imdbid
		if($imdb instanceof stdObject)
			$imdb = $imdb->imdb;
		
		$this	->setPath('get/title_releases/')
			->addQuery('imdb', $imdb)
			->addQuery('q', $release)
			->addQuery('languages', $languages);
		$result = $this->sendRequest();
		if($result->results->total_matches>0)
		{
			$release_id = $result->results->items[0]->release_id;
		}
		
		return $this;
	}
}

class SubtitleSeekerTv extends SubtitleSeekerAPI
{
	public function __construct($item, $thi)
	{
		
	}
}
