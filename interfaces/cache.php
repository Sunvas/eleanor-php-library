<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Interfaces;

/** Interface for cache engines */
interface Cache
{
	/** Storing key=>value
	 * @param string $k Key. It is recommended to specify key as a concatenating of tags like tag1_tag2...
	 * @param mixed $v Value
	 * @param int $ttl Time To Live in seconds */
	function Put(string$k,mixed$v,int$ttl=0):void;

	/** Retrieving value by key
	 * @param string $k Key
	 * @return mixed */
	function Get(string$k):mixed;

	/** Removing value by key
	 * @param string $k Ключ */
	function Delete(string$k):void;

	/** Removing value by tag, if key is empty - all cache will be erased
	 * @param string $tag Tag */
	function DeleteByTag(string$tag):void;
}

return Cache::class;