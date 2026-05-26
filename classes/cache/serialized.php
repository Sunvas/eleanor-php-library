<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\Cache;

use Eleanor\Classes\E;
use const Eleanor\BASE_TIME;

/** Cache engine based on files with serialized content */
class Serialized implements \Eleanor\Interfaces\Cache
{
	/** @param string $path Path to the directory where cache files will be stored, with trailing slash */
	function __construct(readonly string$path){}

	/** Store value by cache key
	 * @param string $k Already sanitized cache key
	 * @param mixed $v Value to store
	 * @param int $ttl Time To Live in seconds. When set to 0, the cache never expires */
	function Put(string$k,mixed$v,int$ttl=86400):void
	{
		$f=$this->path.$k.'.s';

		if(\is_string($v))
			$v.=' ';
		else
			$v=\serialize($v);

		$bytes=\file_put_contents($f,$v,\LOCK_EX);

		if($bytes===false)
		{
			new E('Unable to write to cache file',E::SYSTEM,file:$f,line:null)->Log();
			return;
		}

		if($ttl>0)
			$ttl+=time();
		else
			$ttl=BASE_TIME;

		if($bytes!==\strlen($v) or !\touch($f,$ttl))
		{
			new E('Unable to finalize cache file',E::SYSTEM,file:$f,line:null)->Log();
			\unlink($f);
		}
	}

	/** Retrieve value by cache key
	 * @param string $k Already sanitized cache key
	 * @return mixed Cached value, or null when the key is missing, expired, or unreadable */
	function Get(string$k):mixed
	{
		$f=$this->path.$k.'.s';
		$mtime=@\filemtime($f);

		if($mtime!==BASE_TIME and $mtime<\time())
			return null;

		$s=\file_get_contents($f);

		if($s===false)
		{
			new E('Unable to load cache file',E::SYSTEM,file:$f,line:null)->Log();
			return null;
		}

		return \str_ends_with($s,' ') ? \substr($s,0,-1) : \unserialize($s,['allowed_classes'=>false]);
	}

	/** Remove value by cache key
	 * @param string $k Already sanitized cache key */
	function Delete(string$k):void
	{
		$f=$this->path.$k.'.s';

		if(\is_file($f) and !\unlink($f))
			new E('Unable to delete cache file',E::SYSTEM,file:$f,line:null)->Log();
	}
}

return Serialized::class;