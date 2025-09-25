<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes\Cache;

use Eleanor\Classes\E;

/** Cache based on files with serialized content */
class Serialize implements \Eleanor\Interfaces\Cache
{
	/** @param ?string $path Path to folder where cache files will be stored, without trailing slash */
	function __construct(readonly ?string$path=null){}

	/** Storing value
	 * @param string $k Key
	 * @param mixed $v Value
	 * @param int $ttl Time To Live in seconds */
	function Put(string$k,mixed$v,int$ttl=86400):void
	{
		$f=$this->path."/{$k}.s";

		\file_put_contents($f,\is_string($v) ? $v.' ' : \serialize($v));
		\touch($f,$ttl+\time());
	}

	/** Retrieving value
	 * @param string $k Key
	 * @return mixed */
	function Get(string$k):mixed
	{
		$f=$this->path."/{$k}.s";

		if(!\is_file($f))
			return null;

		if(\filemtime($f)<\time())
		{
			$this->Delete($k);
			return null;
		}

		$s=\file_get_contents($f);

		return \str_ends_with($s,' ') ? \substr($s,0,-1) : \unserialize($s);
	}

	/** Removing value
	 * @param string $k key */
	function Delete(string$k):void
	{
		\unlink($this->path."/{$k}.s");
	}
}

return Serialize::class;