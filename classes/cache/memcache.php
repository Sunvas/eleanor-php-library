<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\Cache;

use Eleanor\Classes\E;

/** Adapter for Memcache cache engine */
class Memcache implements \Eleanor\Interfaces\Cache
{
	/** @var string Memcache server host */
	static string $host='localhost';
	
	/** @var int Memcache server port */
	static int $port=11211;

	/** @var \Memcache Memcache client instance */
	readonly \Memcache $M;

	/** @param string $prefix Prefix for cache keys
	 * @throws E */
	function __construct(readonly string$prefix='')
	{
		$this->M=new \Memcache;
		$connected=$this->M->connect(static::$host,static::$port);

		if(!$connected)
		{
			$this->M->close();
			throw new E('Memcache server is unavailable',E::SYSTEM,
				hint:'Try removing the file library/classes/cache/memcache.php',
				input:['host'=>static::$host,'port'=>static::$port]
			);
		}

		$this->M->setCompressThreshold(20000);
	}

	/** Store value by cache key
	 * @param string $k Cache key
	 * @param mixed $v Value to store
	 * @param int $ttl Time To Live in seconds. When set to 0, the cache never expires */
	function Put(string$k,mixed$v,int$ttl=86400):void
	{
		if(!$this->M->set($this->prefix.$k,$v,\is_string($v) || \is_array($v) ? \MEMCACHE_COMPRESSED : 0,$ttl))
			new E('Unable to store value in Memcache',E::SYSTEM,input:[
				'key'=>$k,
				'value'=>$v,
			])->Log();
	}

	/** Retrieve value by cache key.
	 * Do not store false in Memcache because it is treated as a missing value.
	 * @param string $k Cache key
	 * @return mixed Cached value, or null when the key is missing or unreadable */
	function Get(string$k):mixed
	{
		$v=$this->M->get($this->prefix.$k);
		return $v===false ? null : $v;
	}

	/** Remove value by cache key
	 * @param string $k Cache key */
	function Delete(string$k):void
	{
		$this->M->delete($this->prefix.$k);
	}
}

return Memcache::class;