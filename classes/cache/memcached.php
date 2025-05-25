<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes\Cache;
use Eleanor;

/** Adapter of MemCached engine */
class MemCached implements Eleanor\Interfaces\Cache
{
	/** @var array Keys in the cache */
	private array $names;

	/** @var \Memcached object */
	readonly \Memcached $M;

	/** @param string $u Uniqueness for cache-engine */
	function __construct(readonly string$u='')
	{
		#Host and port contants
		$host='Eleanor\\Classes\\Cache\\MEMCACHED_HOST';
		$port='Eleanor\\Classes\\Cache\\MEMCACHED_PORT';

		#Since this cache engine is very specific, it is recommended to use correct values
		$this->M=new \Memcached($u);
		$this->M->addServer(\defined($host) ? \constant($host) : 'localhost',\defined($port) ? \constant($port) : 11211);

		$this->names=$this->Get('');
	}

	function __destruct()
	{
		$this->Put('',$this->names);
	}

	/** Setting key=>value
	 * @param string $k Key. It is recommended to specify key as a concatenating of tags like tag1_tag2...
	 * @param mixed $v Value
	 * @param int $ttl Time To Live in seconds */
	function Put(string$k,mixed$v,int$ttl=0):void
	{
		$r=$this->M->set($this->u.$k,$v,$ttl);

		if($r)
			$this->names[$k]=$ttl+\time();
	}

	/** Retrieving value by key
	 * @param string $k Key
	 * @return mixed */
	function Get(string$k):mixed
	{
		if(!isset($this->names[$k]))
			return null;

		$r=$this->M->get($this->u.$k);

		if($r===false)
		{
			unset($this->names[$k]);
			$r=null;
		}

		return$r;
	}

	/** Removing value by key
	 * @param string $k Ключ */
	function Delete(string$k):void
	{
		unset($this->names[$k]);

		$this->M->delete($this->u.$k);
	}

	/** Removing value by tag, if key is empty - all cache will be erased
	 * @param string $tag Tag */
	function DeleteByTag(string$tag):void
	{
		foreach($this->names as $k=>$v)
			if($tag=='' or !\str_contains($k,$tag))
				$this->Delete($k);
	}
}

return MemCached::class;