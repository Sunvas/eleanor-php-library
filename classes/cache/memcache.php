<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\Cache;

use Eleanor\Classes\E;

/** Adapter of MemCache engine */
class MemCache implements \Eleanor\Interfaces\Cache
{
	static string $host='localhost';
	static int $port=11211;

	/** @var \Memcache Cache machine instance */
	readonly \Memcache $M;

	/** @param string $prefix Prefix for values in cache machine
	 * @throws E */
	function __construct(readonly string$prefix='')
	{
		$this->M=new \Memcache;
		$connected=$this->M->connect(static::$host,static::$port);

		if(!$connected)
		{
			$this->M->close();
			throw new E('MemCache failure',E::SYSTEM,hint:'Try to delete the file library/classes/cache/memcache.php');
		}

		$this->M->setCompressThreshold(20000);
	}

	/** Storing value
	 * @param string $k Key
	 * @param mixed $v Value
	 * @param int $ttl Time To Live in seconds */
	function Put(string$k,mixed$v,int$ttl=86400):void
	{
		$this->M->set($this->prefix.$k,$v,\is_bool($v) || \is_int($v) || \is_float($v) ? 0 : \MEMCACHE_COMPRESSED,$ttl);
	}

	/** Retrieving value
	 * @param string $k Key
	 * @return mixed */
	function Get(string$k):mixed
	{
		return $this->M->get($this->prefix.$k);
	}

	/** Removing value
	 * @param string $k Key */
	function Delete(string$k):void
	{
		$this->M->delete($this->prefix.$k);
	}
}

return MemCache::class;