<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

use const Eleanor\DOMAIN;

/** Cache manager with automatic engine selection */
class Cache extends \Eleanor\Basic
{
	/** @var string Path to the directory where backup values are stored, with trailing slash */
	readonly string $backup;

	/** @var \Eleanor\Interfaces\Cache Cache engine instance */
	readonly \Eleanor\Interfaces\Cache $Engine;

	/** @const JSON flags used for cache backup files */
	protected const int JSON=JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	/** @param ?string $path Path to the directory where cache files are stored.
	 *     If null, $_SERVER['DOCUMENT_ROOT'].'/cache' is used.
	 *     If empty, the system temporary directory is used.
	 * @throws E */
	function __construct(?string$path=null)
	{
		$path??=\rtrim($_SERVER['DOCUMENT_ROOT'],\DIRECTORY_SEPARATOR).'/cache';
		$path=$path ? \rtrim($path,'/\\') : \sys_get_temp_dir();

		if(!\is_dir($path) and !\mkdir($path,0755,true) or !\is_writeable($path))
			throw new E('Cache folder is unreachable',E::SYSTEM,input:['path'=>$path]);

		$engines=__DIR__.'/cache/';
		$this->backup=$path.\DIRECTORY_SEPARATOR;

		if(\class_exists('\\Memcache',false) and \is_file($engines.'memcache.php'))
			try{
				$this->Engine=new Cache\Memcache(DOMAIN);
				return;
			}catch(E$E){
				$E->Log();
			}

		if(\class_exists('\\Memcached',false) and \is_file($engines.'memcached.php'))
			try{
				$this->Engine=new Cache\Memcached(DOMAIN);
				return;
			}catch(E$E){
				$E->Log();
			}

		if(\extension_loaded('shmop') and \is_file($engines.'shmop.php'))
			$this->Engine=new Cache\Shmop($this->backup);
		else
			$this->Engine=new Cache\Serialized($this->backup);
	}

	/** Store value by cache key.
	 * Passing null as value removes the cache entry.
	 * @param string $key Cache key
	 * @param mixed $value Value to store
	 * @param int $ttl Time To Live in seconds. When set to 0, the cache never expires
	 * @param bool $backup Whether to store a backup value to reduce the dog-pile effect
	 * @throws E */
	function Put(string$key,mixed$value=null,int$ttl=86400,bool$backup=false):void
	{
		if($value===null)
		{
			$this->Delete($key,$backup);
			return;
		}

		static::Sanitize($key);

		$this->Engine->Put($key,$value,$ttl);

		if($backup)
		{
			$json=\json_encode($value,static::JSON);

			if($json===false)
				new E('Unable to encode cache backup to JSON',E::SYSTEM,input:[
					'key'=>$key,
					'value'=>$value,
					'code'=>\json_last_error(),
				])->Log();
			elseif(\file_put_contents($this->backup.$key.'.json',$json,\LOCK_EX)!==\strlen($json))
				new E('Unable to store cache backup',E::SYSTEM,file:$this->backup.$key.'.json',line:null)->Log();
		}
	}

	/** Retrieve value by cache key.
	 * @param string $key Cache key
	 * @param int $ttl Backup restore TTL:
	 *     - -1 disables backup usage;
	 *     - 0 restores the backup as non-expiring and returns it;
	 *     - >0 restores backup for this many seconds and returns null, allowing the current caller to regenerate fresh cache.
	 * @return mixed Cached value, restored backup value, or null
	 * @throws E */
	function Get(string$key,int$ttl=-1):mixed
	{
		static::Sanitize($key);

		$value=$this->Engine->Get($key);

		if($value!==null or $ttl<0)
			return $value;

		$f=$this->backup.$key.'.json';

		if(!\is_file($f))
			return null;

		$json=\file_get_contents($f);

		if($json===false)
		{
			new E('Unable to load cache backup',E::SYSTEM,file:$f,line:null)->Log();
			return null;
		}

		$json=\json_decode($json,true);
		$code=\json_last_error();

		if($code!==\JSON_ERROR_NONE)
		{
			new E('Invalid cache backup',E::SYSTEM,file:$f,line:null,input:['code'=>$code])->Log();
			return null;
		}

		$this->Put($key,$json,$ttl);

		return $ttl>0 ? null : $json;
	}

	/** Remove value by cache key.
	 * @param string $key Cache key
	 * @param bool $backup Whether to remove the backup value too
	 * @throws E */
	function Delete(string$key,bool$backup=false):void
	{
		static::Sanitize($key);

		$this->Engine->Delete($key);

		if(!$backup)
			return;

		$f=$this->backup.$key.'.json';

		if(\is_file($f) and !\unlink($f))
			new E('Unable to delete cache backup',E::SYSTEM,file:$f,line:null)->Log();
	}

	protected const string ALLOWED_CHARS='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789._-';

	/** Validate the cache key.
	 * @param string $key Cache key
	 * @throws E */
	protected static function Sanitize(string$key):void
	{
		if($key==='' or \strspn($key,static::ALLOWED_CHARS)!==\strlen($key))
			throw new E('Invalid cache key',E::PHP,...\Eleanor\BugFileLine(static::class),input:['key'=>$key]);
	}
}

# Not required here because class name matches filename
return Cache::class;