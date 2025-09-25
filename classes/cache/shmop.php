<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\Cache;

use Eleanor\Classes\E;

/** Cache based on Shared Memory Functions */
class Shmop implements \Eleanor\Interfaces\Cache
{
	/** @var string One-character string for second argument of \ftok function */
	static string $project_id='e';

	/** @param ?string $path Path to folder where auxiliary files are is stored, without trailing slash */
	function __construct(readonly ?string$path=null){}

	/** Storing value
	 * @param string $k Key
	 * @param mixed $v Value
	 * @param int $ttl Time To Live in seconds */
	function Put(string$k,mixed$v,int$ttl=86400):void
	{
		$f=$this->path."/{$k}.shm";

		if(!\file_exists($f))
		{
			$h=fopen($f,'w');
			fclose($h);
		}

		$ipc=\ftok($f,static::$project_id);

		if($ipc<0)
			goto Err;

		if(\is_string($v))
			$v.=' ';
		else
			$v=\serialize($v);

		$h=\shmop_open($ipc,'c',0644,\strlen($v));

		if($h===false)
		{
			Err:
			\unlink($f);
		}
		else
		{
			\shmop_write($h,$v,0);
			\touch($f,$ttl+\time());
		}
	}

	/** Retrieving value
	 * @param string $k Key
	 * @return mixed */
	function Get(string$k):mixed
	{
		$f=$this->path."/{$k}.shm";

		if(!\file_exists($f))
			return null;

		$ipc=\ftok($f,static::$project_id);

		if(\filemtime($f)<\time() || $ipc<0)
		{
			$this->Delete($k);
			return null;
		}

		$h=\shmop_open($ipc,'a',0,0);

		if($h===false)
			return null;

		$s=\shmop_read($h,0,0);

		if(\str_starts_with($s,"\0"))
			return null;

		return \str_ends_with($s,' ') ? \substr($s,0,-1) : \unserialize($s);
	}

	/** Removing value
	 * @param string $k Key */
	function Delete(string$k):void
	{
		$f=$this->path."/{$k}.shm";

		if(!\file_exists($f))
			return;

		$ipc=\ftok($f,static::$project_id);

		if($ipc>0)
		{
			$h=\shmop_open($ipc,'w',0,0);
			\shmop_delete($h);
		}

		\unlink($f);
	}
}

return Shmop::class;