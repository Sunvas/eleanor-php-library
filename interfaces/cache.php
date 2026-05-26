<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Interfaces;

/** Interface for cache engines */
interface Cache
{
	/** Store value by key.
	 * @param string $k Cache key. It is recommended to compose keys from logical tags, for example: tag1_tag2_...
	 * @param mixed $v Value to cache
	 * @param int $ttl Time To Live in seconds. When set to 0, the cache never expires */
	function Put(string$k,mixed$v,int$ttl=86400):void;

	/** Retrieve value by key.
	 * @param string $k Cache key
	 * @return mixed Cached value, or null when the key is missing, expired, or unreadable */
	function Get(string$k):mixed;

	/** Removing value by key
	 * @param string $k Cache key */
	function Delete(string$k):void;
}

# Not required here because interface name matches filename.
return Cache::class;