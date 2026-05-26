<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\Cache;

use Eleanor\Classes\E;
use const Eleanor\BASE_TIME;

/** Cache engine based on PHP shared memory functions */
class Shmop implements \Eleanor\Interfaces\Cache
{
	/** @var int Permissions for auxiliary cache files */
	protected const int PERM=0644;

	/** @var string One-character project identifier used by \ftok() */
	static string $project_id='e';

	/** @param string $path Path to the directory for auxiliary files, with trailing slash */
	function __construct(readonly string$path){}

	/** Store value by cache key
	 * @param string $k Already sanitized cache key
	 * @param mixed $v Value to store
	 * @param int $ttl Time To Live in seconds. When set to 0, the cache never expires */
	function Put(string$k,mixed$v,int$ttl=86400):void
	{
		$f=$this->path.$k.'.shm';

		if(!\is_file($f) and !\touch($f))
		{
			new E('Unable to create cache file',E::SYSTEM,file:$f,line:null)->Log();
			return;
		}

		$ipc=\ftok($f,static::$project_id);

		if($ipc>=0)
		{
			# Deleting old shared memory segment
			$old=@\shmop_open($ipc,'w',0,0);
			if($old!==false)
				\shmop_delete($old);
			unset($old);

			if(\is_string($v))
				$v.=' ';
			else
				$v=\serialize($v);

			$size=\strlen($v);
			$h=\shmop_open($ipc,'n',static::PERM,$size);
		}
		else
			$h=false;

		if($h===false)
		{
			\unlink($f);
			new E('Unable to create shared memory block',E::SYSTEM,file:$f,line:null)->Log();
			return;
		}

		if($ttl>0)
			$ttl+=time();
		else
			$ttl=BASE_TIME;

		if(\shmop_write($h,$v,0)!==$size or !\touch($f,$ttl))
		{
			\shmop_delete($h);
			\unlink($f);
			new E('Unable to store value in shared memory cache',E::SYSTEM,file:$f,line:null)->Log();
		}
	}

	/** Retrieve value by cache key
	 * @param string $k Already sanitized cache key
	 * @return mixed Cached value, or null when the key is missing, expired, or unreadable */
	function Get(string$k):mixed
	{
		$f=$this->path.$k.'.shm';

		if(!\is_file($f))
			return null;

		$ipc=\ftok($f,static::$project_id);
		$mtime=@\filemtime($f);

		if($ipc<0 or $mtime!==BASE_TIME and $mtime<\time())
		{
			$this->Delete($k,$ipc);
			return null;
		}

		$h=\shmop_open($ipc,'a',0,0);

		if($h===false)
		{
			new E('Unable to load shared memory block',E::SYSTEM,file:$f,line:null)->Log();
			return null;
		}

		$s=\shmop_read($h,0,\shmop_size($h));

		return \str_ends_with($s,' ') ? \substr($s,0,-1) : \unserialize($s,['allowed_classes'=>false]);
	}

	/** Remove value by cache key
	 * @param string $k Already sanitized cache key
	 * @param ?int $ipc Optional IPC key, used to avoid recalculating it */
	function Delete(string$k,?int$ipc=null):void
	{
		$f=$this->path.$k.'.shm';

		if(!\is_file($f))
			return;

		$ipc??=\ftok($f,static::$project_id);

		if($ipc>=0)
		{
			$h=@\shmop_open($ipc,'w',static::PERM,0);

			if($h===false)
				new E('Unable to open shared memory block',E::SYSTEM,file:$f,line:null)->Log();
			else
				\shmop_delete($h);
		}

		if(!\unlink($f))
			new E('Unable to delete cache file',E::SYSTEM,file:$f,line:null)->Log();
	}
}

return Shmop::class;