<?php

class Storage
{
	public $data = array();
	private $file = null;
	public function __construct($file = null)
	{
		if(!is_null($file))
			$this->open($file);
		return $this;
	}

	public function __get($name)
	{
		if(!isset($data->$name))
			throw new Exception('Unable to find '.$name.' in the self::$data object.');
		return $data->$name;
	}
	
	public function __set($name, $value)
	{
		$data[$name] = $value;
		return $this;
	}
	
	public function open($file)
	{
		$this->file = $file;
		if(!is_readable($file))
			throw new Exception('Unable to read file '.$file);
		$this->data = unserialize(file_get_contents($file));
		return $this;
	}
	
	public function setFile($file)
	{
		$this->file = $file;
		return $this;
	}
	
	public function save($file = null)
	{
		if(is_null($file))
			$file = $this->file;
		if(is_file($file) && !is_writable($file))
			throw new Exception('Unable to write into file '.$file);
		if(!file_put_contents($file, serialize($this->data)))
			throw new Exception('An error occurend writing the file '.$file);
		return $this;
	}
	
	public static function Fopen($file)
	{
		return new self ($file);
	}
	
	public static function File($file)
	{
		return new self ($file);
	}
}

?>
