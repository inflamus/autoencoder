<?php

class Notify
{
	const CLIPROG = 'notify-send';
	const VERSION = '0.7.5';
	protected $_CLI = '';
	protected $title = '', $body = '';
	protected $options = array();
	
	public static $OPTS = array(
		'-u' => '--urgency', 	//Specifies the urgency level (low, normal, critical).
		'-t' => '--expire-time', //Specifies the timeout in milliseconds at which to expire the notification
		'-a' => '--app-name', 	//Specifies the app name for the icon
		'-i' => '--icon', 	//Specifies an icon filename or stock icon to display.
		'-c' => '--category', 	//Specifies the notification category.
		'-h' => '--hint',	//Specifies basic extra data to pass. Valid types are int, double, string and byte.
		'-v' => '--version',	//Version of the Package
		);
		
	public function __construct($title='', $body='')
	{
		exec('which '.escapeshellarg(self::CLIPROG), $out);
		$this->_CLI = !empty($out[0]) ? $out[0] : self::CLIPROG;
		
		if($title != '')
			$this->Title($title);
		if($body != '')
			$this->Body($body);
		return $this;
	}
	
	public static function __New($title = '', $body = '')
	{
		return new self($title, $body);
	}
	
	public static function Fire($title, $body)
	{
		return self::__New($title, $body)->Exec();
	}
	
	public static function Send($title, $body)
	{
		return self::Fire($title, $body);
	}
	
	public function Option($opt, $val = '')
	{
		if(strlen($opt) == 1) // Handles u, a, t
			$opt = '-'.$opt;
		if(strpos($opt, ' ') !== false) // handle the string as a cli arg
		{
			list($opt, $val) = explode(' ', $opt);
			if(in_array(substr($val, 0,1), array('"', "'")))
				$val = substr($val, 1);
			if(in_array(substr($val, -1), array('"', "'")))
				$val = substr($val, 0, -1);
		}
		if(strpos($opt, '=') !== false) // handle string as cli with full args
		{
			list($opt, $val ) = explode('=', $opt);
			if(in_array(substr($val, 0,1), array('"', "'")))
				$val = substr($val, 1);
			if(in_array(substr($val, -1), array('"', "'")))
				$val = substr($val, 0, -1);
		}
		// test arguments
		if(in_array($opt, array_values(self::$OPTS))) // switch full opts to simple
			$opt = array_flip(self::$OPTS)[$opt];
		if(in_array($opt, array_keys(self::$OPTS))) // right opts
			$this->options[$opt] = $val != '' ? $opt.' '.escapeshellarg($val) : $opt;
		return $this;
	}
	
	// Urgency : level 1 - critical, 2 - normal, 3 - low
	public function Urgency($level = 'normal')
	{
		$Level = array(
			1 => 'critical',
			2 => 'normal',
			3 => 'low',
		);
		if(is_int($level) && $level <=3 && $level >= 1)
			$level = $Level[$level];
		if(is_string($level) && in_array($level, $Level))
			$this->Option('-u', $level);
		return $this;
	}
	
	public function Priority($level = 'normal')
	{
		return $this->Urgency($level);
	}
	
	public function Expire($s) // in seconds
	{
		//Parse something like 20s, 50s, 100s ....
		// and transform it in milliseconds
		if(is_string($s))
		{
			if(preg_match('/([0-9]+)([smhdj]?)/', $s, $m))
				switch($m[2])
				{
					default:
					case 's':
						$s = (int)trim($s);
					break;
					case 'm':
						$s = (int)trim($s)*60;
					break;
					case 'h':
						$s = (int)trim($s)*3600;
					break;
					case 'd':
					case 'j':
						$s = (int)trim($s)*3600*24;
					break;
				}
		}
		if(is_int($s))
			return $this->Option('-t', $s*1000);
		else
			throw New Exception('Couldn\'t parse the time in seconds');
		return $this;
	}
	
	public function During($s)
	{
		return $this->Expire($s);
	}
	
	public function Category($c)
	{
		return $this->Option('-c', implode(',', (array)$c));
	}
	
	public function Icon($i, $appname = '')
	{
		if($appname != '')
			$this->AppName($appname);
		return $this->Option('-i', implode(',', (array)$i));
	}
	
	public function AppName($a)
	{
		return $this->Option('-a', $a);
	}
	
	public function Title($title = '')
	{
		$this->title = nl2br(trim($title));
		return $this;
	}
	
	public function Body($body = '')
	{
		$this->body = nl2br(trim($body));
		return $this;
	}
	
	public function __toString()
	{
		return $this->_CLI.' '.
			implode(' ', $this->options).' '.
			escapeshellarg($this->title).' '.
			escapeshellarg($this->body)
			;
	}

	public function Exec()
	{
		exec((string) $this);
		return $this;
	}
}

?>
