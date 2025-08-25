<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes;

/** Wrapper for cache engines */
class Cache extends \Eleanor\Basic
{
	/** @var string The path to the “eternal" cache files. Read about “eternal" cache in the constructor */
	readonly string $storage;

	/** @var \Eleanor\Interfaces\Cache Object of cache engine */
	readonly \Eleanor\Interfaces\Cache $Engine;

	/** @const For "eternal cache" files */
	protected const int JSON=JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	/** @param ?string $storage The path of storing “eternal” cache. Eternal cache is timeless data in JSON format, which can be manually edited if necessary.
	 * @param ?string $path Path of storing files of internal cache engine (when no external cache engines available)
	 * @throws E */
	function __construct(?string$storage=null,?string$path=null)
	{
		$cachedir=__DIR__.'/cache/';

		if(\class_exists('\\Memcache',false) and \is_file($cachedir.'memcache.php'))
			$this->Engine=new Cache\MemCache(\crc32(__DIR__));
		elseif(\class_exists('\\Memcached',false) and \is_file($cachedir.'memcached.php'))
			$this->Engine=new Cache\MemCached(\crc32(__DIR__));
		else
			$this->Engine=new Cache\Serialize($path);

		$this->storage=$storage ? \rtrim($storage,'/\\') : \rtrim($_SERVER['DOCUMENT_ROOT'],\DIRECTORY_SEPARATOR).'/cache/storage';

		if(!\is_dir($this->storage))
			Files::MkDir($this->storage);

		if(!\is_writeable($this->storage))
			throw new E('Folder for %cache/storage% is write-protected',E::SYSTEM);
	}

	/** Storing key=>value to cache
	 * @param string $key Key. It is recommended to specify key as a concatenating of tags like tag1_tag2...
	 * @param mixed $value Stored data
	 * @param int $ttl Time To Live in seconds
	 * @param bool $eternal Flag of storing like "eternal" cache
	 * @param int $hopeless Time of hopeless cache obsoletion. Used to prevent dog-pile effect. If less than $ttl, it is converted to $ttl*2
	 * По умолчанию в два раза больше $ttl.
	 * @throws E */
	function Put(string$key,mixed$value=false,int$ttl=0,bool$eternal=false,int$hopeless=0):void
	{
		if($hopeless<$ttl)
			$hopeless=$ttl*2;

		if($value===false)
			$this->Delete($key,true);
		else
			$this->Engine->Put($key,$hopeless>0 ? [$value,$ttl,\time()+$ttl] : [$value],$hopeless);

		if($eternal)
		{
			if(\preg_match('#^[a-z\d.\-_]+$#i',$key)===0)
				throw new E('Invalid eternal key',E::PHP,input:$key);

			\file_put_contents($this->storage."/{$key}.json",\json_encode($value,static::JSON));
		}
	}

	/** Retrieving value by key from cache
	 * @param string $key
	 * @param bool $eternal Flag for retrieving value from the “eternal” cache
	 * @throws E
	 * @return mixed */
	function Get(string$key,bool$eternal=false):mixed
	{
		if($out=$this->Engine->Get($key))
		{
			if(\is_array($out) and isset($out[1]) and $out[2]<\time())
			{
				$this->Put($key,$out[0],$out[1]);

				return null;
			}

			return$out[0];
		}

		if(!$eternal or !$key)
			return$out;

		if(\preg_match('#[^a-z\d.\-_]+#i',$key,$m)>0)
			throw new E('Invalid eternal key',E::PHP,input:['key'=>$key,'wrong'=>$m[0]]);

		$file=$this->storage."/{$key}.json";

		if(!\is_file($file))
			return null;

		$out=\json_decode(\file_get_contents($file),true);

		$this->Put($key,$out);

		return$out;
	}

	/** Removing value by key from cache
	 * @param string $key
	 * @param bool $eternal Flag for removing value from the “eternal” cache
	 * @throws E */
	function Delete(string$key,bool$eternal=false):void
	{
		$this->Engine->Delete($key);

		if($eternal)
		{
			if(\preg_match('#[\s\#"\'\\\\/:*?<>|%]+#',$key)>0)
				throw new E('Invalid eternal key',E::PHP,input:$key);

			$file=$this->storage."/{$key}.php";

			if(\is_file($file))
				Files::Delete($file);
		}
	}

	/** Marking the cache obsolete to regenerate it. Unlike Delete, it does not cause dog-pile effect.
	 * @param string $key Имя ячейки хранения кэша */
	function Obsolete(string$key):void
	{
		if(false!==$out=$this->Engine->Get($key))
			if(\is_array($out) and isset($out[1]) and 0<$ttl=($out[2]-\time()))
			{
				$out[2]=0;
				$this->Engine->Put($key,$out,$ttl);
			}
			else
				$this->Engine->Delete($key);
	}
}

return Cache::class;