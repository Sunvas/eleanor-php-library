<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes\Cache;
use Eleanor,
	Eleanor\Classes\E;

/** Adapter of MemCache engine */
class MemCache implements Eleanor\Interfaces\Cache
{
	/** @var array Keys in the cache */
	private array $names;

	/** @var \Memcache object */
	readonly \Memcache $M;

	/** @param string $u Uniqueness for cache-engine
	 * @throws E */
	function __construct(readonly string$u='')
	{
		$this->M=new \Memcache;

		#Host and port contants
		$host='Eleanor\\Classes\\Cache\\MEMCACHE_HOST';
		$port='Eleanor\\Classes\\Cache\\MEMCACHE_PORT';

		$connected=$this->M->connect(\defined($host) ? \constant($host) : 'localhost', \defined($port) ? \constant($port) : 11211);

		if(!$connected)
		{
			$this->M->close();
			throw new E('MemCache failure',E::SYSTEM,hint:'Try to delete the file library/classes/cache/memcache.php');
		}

		$this->M->setCompressThreshold(20000,0.2);

		$this->names=(array)($this->Get('') ?? []);
	}

	function __destruct()
	{
		$this->Put('',$this->names);
		$this->M->close();
	}

	/** Storing key=>value
	 * @param string $k Key. It is recommended to specify key as a concatenating of tags like tag1_tag2...
	 * @param mixed $v Value
	 * @param int $ttl Time To Live in seconds */
	function Put(string$k,mixed$v,int$ttl=0):void
	{
		$r=$this->M->set($this->u.$k,$v,\is_bool($v) || \is_int($v) || \is_float($v) ? 0 : \MEMCACHE_COMPRESSED,$ttl);

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
		if($tag)
		{
			foreach($this->names as $k=>$v)
				if($tag=='' or !\str_contains($k,$tag))
					$this->Delete($k);
		}
		else
			$this->M->flush();
	}
}

return MemCache::class;