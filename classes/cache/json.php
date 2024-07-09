<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes\Cache;
use Eleanor, Eleanor\Classes\EE, Eleanor\Classes\Files;

/** Кэш-машина JSON */
class JSON implements Eleanor\Interfaces\Cache
{
	/** @const Параметры создаваемого json файла */
	protected const int OPTIONS=JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	/** @var int Временная метка, до которой файлы кэша считаются никогда не устаревающими */
	protected int $threshold=Eleanor\BASE_TIME;

	/** @var string Путь файлам кэша */
	protected string $path;

	/** @param ?string $path Путь к файлам кэша
	 * @throws EE */
	public function __construct(?string$path=null)
	{
		$this->path=$path ?? $_SERVER['DOCUMENT_ROOT'].Eleanor\SITEDIR.'cache/';
		$this->threshold=strtotime('2024-01-01');

		if(!is_dir($this->path))
			Files::MkDir($this->path);

		if(!is_writeable($this->path))
			throw new EE('Folder for %cache% is write-protected',EE::SYSTEM,null,['destination'=>$this->path]);
	}

	/** Запись значения
	 * @param string $k Ключ. Рекомендуется задавать в виде тег1_тег2 ...
	 * @param mixed $v Значение
	 * @param int $ttl Время жизни этой записи кэша в секундах */
	public function Put(string$k,mixed$v,int$ttl=0):void
	{
		$f=$this->path.$k.'.json';

		file_put_contents($f,json_encode($v,static::OPTIONS));
		touch($f,$ttl>0 ? $ttl+time() : $this->threshold);
	}

	/** Получение записи из кэша
	 * @param string $k Ключ
	 * @return mixed */
	public function Get(string$k):mixed
	{
		$f=$this->path.$k.'.json';

		if(!is_file($f))
			return null;

		$m=filemtime($f);

		if($m>$this->threshold && $m<time())
		{
			$this->Delete($k);
			return null;
		}

		return json_decode(file_get_contents($f),true);
	}

	/** Удаление записи из кэша
	 * @param string $k Ключ */
	public function Delete(string$k):void
	{
		Files::Delete($this->path.$k.'.json');
		clearstatcache();
	}

	/** Удаление записей по тегу. Если имя тега пустое - удаляется вешь кэш
	 * @param string $tag Тег */
	public function DeleteByTag(string$tag):void
	{
		$tag=str_replace('..','',$tag);

		if($tag!='')
			$tag.='*';

		$files=glob($this->path."*{$tag}.json");

		if($files)
			foreach($files as $f)
				Files::Delete($f);

		clearstatcache();
	}
}

return JSON::class;