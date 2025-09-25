<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\Cache;

/** Adapter of MemCached engine */
class MemCached implements \Eleanor\Interfaces\Cache
{
	static string $host='localhost';
	static int $port=11211;

	/** @var \Memcached Cache machine instance */
	readonly \Memcached $M;

	/** @param string $prefix Prefix for values in cache machine */
	function __construct(readonly string$prefix='')
	{
		#Since this cache engine is very specific, it is recommended to use correct values
		$this->M=new \Memcached;
		$this->M->addServer(static::$host,static::$port);
	}

	/** Storing value
	 * @param string $k Key
	 * @param mixed $v Value
	 * @param int $ttl Time To Live in seconds */
	function Put(string$k,mixed$v,int$ttl=86400):void
	{
		$this->M->set($this->prefix.$k,$v,$ttl);
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

return MemCached::class;