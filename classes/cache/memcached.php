<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\Cache;

use Eleanor\Classes\E;

/** Adapter for Memcached cache engine */
class Memcached implements \Eleanor\Interfaces\Cache
{
	/** @var string Memcached server host */
	static string $host='localhost';

	/** @var int Memcached server port */
	static int $port=11211;

	/** @var \Memcached Memcached client instance */
	readonly \Memcached $M;

	/** @param string $prefix Prefix for cache keys
	 * @throws E */
	function __construct(readonly string$prefix='')
	{
		$this->M=new \Memcached;

		# Since this cache engine is highly environment-specific, valid configuration values are recommended
		if(!$this->M->addServer(static::$host,static::$port))
			throw new E('Memcached server is unavailable',E::SYSTEM,
				hint:'Try removing the file '.__FILE__,
				input:['host'=>static::$host,'port'=>static::$port]
			);
	}

	/** Store value by cache key
	 * @param string $k Cache key
	 * @param mixed $v Value to store
	 * @param int $ttl Time To Live in seconds. When set to 0, the cache never expires */
	function Put(string$k,mixed$v,int$ttl=86400):void
	{
		if(!$this->M->set($this->prefix.$k,$v,$ttl))
			new E('Unable to store value in Memcached',E::SYSTEM,input:[
				'key'=>$k,
				'value'=>$v,
			])->Log();
	}

	/** Retrieve value by cache key
	 * @param string $k Cache key
	 * @return mixed Cached value, or null when the key is missing or unreadable */
	function Get(string$k):mixed
	{
		$v=$this->M->get($this->prefix.$k);
		$c=$this->M->getResultCode();

		if($c===\Memcached::RES_SUCCESS)
			return $v;

		if($c!==\Memcached::RES_NOTFOUND)
			new E('Memcached returned unexpected code '.$c,E::SYSTEM,
				hint:'Error code list is available at https://www.php.net/manual/en/memcached.getresultcode.php',
				input:['key'=>$k],
			)->Log();

		return null;
	}

	/** Remove value by cache key
	 * @param string $k Cache key */
	function Delete(string$k):void
	{
		$this->M->delete($this->prefix.$k);
	}
}

return Memcached::class;