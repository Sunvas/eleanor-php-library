<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

use const Eleanor\PUNYCODE;

/** Wrapper for cache engines */
class Cache extends \Eleanor\Basic
{
	/** @var string The path where backup of values will be stored */
	readonly string $backup;

	/** @var \Eleanor\Interfaces\Cache Object of cache engine */
	readonly \Eleanor\Interfaces\Cache $Engine;

	/** @const For "eternal cache" files */
	protected const int JSON=JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	/** @param ?string $path The path where cache files will be stored
	 * @throws E */
	function __construct(?string$path=null)
	{
		$this->backup=$path ? \rtrim($path,'/\\') : \rtrim($_SERVER['DOCUMENT_ROOT'],\DIRECTORY_SEPARATOR).'/cache';

		if(!\is_dir($this->backup) and !\mkdir($this->backup,0744,true) or !\is_writeable($this->backup))
			throw new E('Cache folder is unreachable',E::SYSTEM,input:$this->backup);

		$cachedir=__DIR__.'/cache/';

		if(\class_exists('\\Memcache',false) and \is_file($cachedir.'memcache.php'))
			$this->Engine=new Cache\MemCache(PUNYCODE);
		elseif(\class_exists('\\Memcached',false) and \is_file($cachedir.'memcached.php'))
			$this->Engine=new Cache\MemCached(PUNYCODE);
		elseif(\extension_loaded('shmop') and \is_file($cachedir.'shmop.php'))
			$this->Engine=new Cache\Shmop($path);
		else
			$this->Engine=new Cache\Serialize($path);
	}

	/** Storing value
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds
	 * @param bool $backup Flag of storing backup version of value for preventing dog-pile effect
	 * @throws E */
	function Put(string$key,mixed$value=false,int$ttl=0,bool$backup=false):void
	{
		if($value===false)
		{
			$this->Delete($key,$backup);
			return;
		}

		$this->Engine->Put($key,$value,$ttl);

		if($backup)
		{
			static::Sanitize($key);

			\file_put_contents($this->backup."/{$key}.json",\json_encode($value,static::JSON));
		}
	}

	/** Retrieving value
	 * @param string $key
	 * @param bool|int $backup Flag for retrieving value from backup
	 * @return mixed
	 * @throws E */
	function Get(string$key,bool|int$backup=false):mixed
	{
		$value=$this->Engine->Get($key);

		if($value!==null or !$backup)
			return $value;

		static::Sanitize($key);

		$file=$this->backup."/{$key}.json";

		if(!\is_file($file))
			return null;

		$renew=$backup===true;
		$value=\json_decode(\file_get_contents($file),true);

		$this->Put($key,$value,$renew ? 86400 : $backup);

		return $renew ? $value : null;
	}

	/** Removing value
	 * @param string $key
	 * @param bool $backup Flag for removing backup value
	 * @throws E */
	function Delete(string $key,bool$backup=false):void
	{
		$this->Engine->Delete($key);

		if($backup)
		{
			static::Sanitize($key);
			unlink($this->backup."/{$key}.json");
		}
	}

	/** @throws E */
	protected static function Sanitize(string$key):void
	{
		if(\preg_match('#[^a-z\d.\-_]+#i',$key,$m)>0)
			throw new E('Invalid eternal key',E::PHP,...\Eleanor\BugFileLine(static::class),input:['key'=>$key,'wrong'=>$m[0]]);
	}
}

#Not necessary here, since class name equals filename
return Cache::class;