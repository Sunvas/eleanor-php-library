<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes\Cache;
use Eleanor, Eleanor\Classes\E, Eleanor\Classes\Files;

/** Кэш-машина Serialize */
class Serialize implements Eleanor\Interfaces\Cache
{
	/** @var int Временная метка, до которой файлы кэша считаются никогда не устаревающими */
	protected int $threshold=Eleanor\BASE_TIME;

	/** @var string Путь файлам кэша */
	protected string $path;

	/** @param ?string $path Путь к файлам кэша
	 * @throws E */
	function __construct(?string$path=null)
	{
		$this->path=$path ?? $_SERVER['DOCUMENT_ROOT'].Eleanor\SITEDIR.'cache/';

		if(!is_dir($this->path))
			Files::MkDir($this->path);

		if(!is_writeable($this->path))
			throw new E('Folder for %cache% is write-protected',E::SYSTEM,null,['destination'=>$this->path]);
	}

	/** Запись значения
	 * @param string $k Ключ. Рекомендуется задавать в виде тег1_тег2 ...
	 * @param mixed $v Значение
	 * @param int $ttl Время жизни этой записи кэша в секундах */
	function Put(string$k,mixed$v,int$ttl=0):void
	{
		$f=$this->path.$k.'.s';

		file_put_contents($f,serialize($v));
		touch($f,$ttl>0 ? $ttl+time() : $this->threshold);
	}

	/** Получение записи из кэша
	 * @param string $k Ключ  */
	function Get(string$k):mixed
	{
		$f=$this->path.$k.'.s';

		if(!is_file($f))
			return null;

		$m=filemtime($f);

		if($m>$this->threshold && $m<time())
		{
			$this->Delete($k);
			return null;
		}

		return unserialize(file_get_contents($f));
	}

	/** Удаление записи из кэша
	 * @param string $k Ключ */
	function Delete(string$k):void
	{
		Files::Delete($this->path.$k.'.s');
		clearstatcache();
	}

	/** Удаление записей по тегу. Если имя тега пустое - удаляется весь кэш
	 * @param string $tag Тег */
	function DeleteByTag(string$tag):void
	{
		$tag=str_replace('..','',$tag);

		if($tag!='')
			$tag.='*';

		$files=glob($this->path."*{$tag}.s");

		if($files)
			foreach($files as $f)
				Files::Delete($f);

		clearstatcache();
	}
}

return Serialize::class;