<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes\Cache;
use Eleanor, Eleanor\Classes\E, Eleanor\Classes\Files;

/** Cache based on files with serialized content */
class Serialize implements Eleanor\Interfaces\Cache
{
	/** @var int The timestamp before which cache files are considered as non obsolete */
	protected int $threshold=Eleanor\BASE_TIME;

	/** @var string Path to folder where cache is stored */
	readonly string $path;

	/** @param ?string $path Path to folder where cache is stored
	 * @throws E */
	function __construct(?string$path=null)
	{
		$this->path=$path ?? $_SERVER['DOCUMENT_ROOT'].Eleanor\SITEDIR.'cache/';

		if(!\is_dir($this->path))
			Files::MkDir($this->path);

		if(!\is_writeable($this->path))
			throw new E('Folder for %cache% is write-protected',E::SYSTEM,null,['destination'=>$this->path]);
	}

	/** Storing key=>value
	 * @param string $k Key. It is recommended to specify key as a concatenating of tags like tag1_tag2...
	 * @param mixed $v Value
	 * @param int $ttl Time To Live in seconds */
	function Put(string$k,mixed$v,int$ttl=0):void
	{
		$f=$this->path.$k.'.s';

		\file_put_contents($f,\serialize($v));
		\touch($f,$ttl>0 ? $ttl+\time() : $this->threshold);
	}

	/** Retrieving value by key
	 * @param string $k Key
	 * @return mixed */
	function Get(string$k):mixed
	{
		$f=$this->path.$k.'.s';

		if(!\is_file($f))
			return null;

		$m=\filemtime($f);

		if($m>$this->threshold && $m<\time())
		{
			$this->Delete($k);
			return null;
		}

		return \unserialize(\file_get_contents($f));
	}

	/** Removing value by key
	 * @param string $k Ключ */
	function Delete(string$k):void
	{
		Files::Delete($this->path.$k.'.s');
		\clearstatcache();
	}

	/** Removing value by tag, if key is empty - all cache will be erased
	 * @param string $tag Tag */
	function DeleteByTag(string$tag):void
	{
		$tag=\str_replace('..','',$tag);

		if($tag!='')
			$tag.='*';

		$files=\glob($this->path."*{$tag}.s");

		if($files)
			foreach($files as $f)
				Files::Delete($f);

		\clearstatcache();
	}
}

return Serialize::class;