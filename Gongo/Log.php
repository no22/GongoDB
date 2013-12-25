<?php
class Gongo_Log
{
	static $log = null;
	protected $path = null;

	static function get($path = null)
	{
		if (is_null(self::$log)) {
			$path = is_null($path) ? 'log.txt' : $path ;
			self::$log = Gongo_Locator::get('Gongo_Log', $path);
		}
		return self::$log;
	}

	public function __construct($path = null)
	{
		$this->path = $path;
	}

	function path($path = null)
	{
		if (is_null($path)) return $this->path;
		$this->path = $path;
		return $this;
	}

	public function add($text, $email = null)
	{
		$text = is_string($text) ? $text : print_r($text, true) ;
		$log = date('Y-m-d H:i:s ') . $text . "\n";
		if (!is_null($email)) return error_log($log, 1, $email);
		if (!$this->path) return error_log($log, 0);
		return file_put_contents($this->path, $log, FILE_APPEND | LOCK_EX);
	}
}
