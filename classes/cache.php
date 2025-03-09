<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;

/** Поддержка кэша */
class Cache extends \Eleanor\Basic
{
	/** @var string Путь к файлам "вечного" хранилища. О том, что такое "вечный кэш", читайте ниже */
	readonly string $storage;

	/** @var \Eleanor\Interfaces\Cache Объект кэш-машины */
	public \Eleanor\Interfaces\Cache $Engine;

	/** @const Параметры создаваемого json файла */
	protected const int JSON=JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	/** @param ?string $path Путь для хранения файлов для внутренней кэш-машины (когда отсутствуют внешние)
	 * @param ?string $storage Путь сохранения "вечного" кэша. Вечный кэш это неустаревающие данные в JSON формате, которые, при необходимости, возможно править вручную.
	 * @throws E */
	function __construct(?string$path=null,?string$storage=null)
	{
		$cachedir=__DIR__.'/cache/';

		if(class_exists('\\Memcache',false) and is_file($cachedir.'memcache.php'))
			$this->Engine=new \Eleanor\Classes\Cache\MemCache(crc32(__DIR__));
		elseif(class_exists('\\Memcached',false) and is_file($cachedir.'memcached.php'))
			$this->Engine=new \Eleanor\Classes\Cache\MemCached(crc32(__DIR__));
		else
			$this->Engine=new \Eleanor\Classes\Cache\Serialize($path);

		$this->storage=$storage ? rtrim($storage,'/\\') : $_SERVER['DOCUMENT_ROOT'].\Eleanor\SITEDIR.'cache/storage';

		if(!is_dir($this->storage))
			Files::MkDir($this->storage);

		if(!is_writeable($this->storage))
			throw new E('Folder for %cache/storage% is write-protected',E::SYSTEM);
	}

	/** Запись данных в кэш
	 * @param string $key Ключ (имя ячейки хранения кэша
	 * @param mixed $value Хранимые данные
	 * @param int $ttl Время хранения в секундах
	 * @param bool $eternal Запись в качестве "вечного" кэша
	 * @param int $hopeless Время безнадежного устаревания кэша. Используется для предотвращения dog-pile effect. Если меньше $ttl, то преобразуется в значение $ttl*2
	 * По умолчанию в два раза больше $ttl.
	 * @throws E */
	function Put(string$key,mixed$value=false,int$ttl=0,bool$eternal=false,int$hopeless=0):void
	{
		if($hopeless<$ttl)
			$hopeless=$ttl*2;

		if($value===false)
			$this->Delete($key,true);
		else
			$this->Engine->Put($key,$hopeless>0 ? [$value,$ttl,time()+$ttl] : [$value],$hopeless);

		if($eternal)
		{
			if(preg_match('#^[a-z\d.\-_]+$#i',$key)===0)
				throw new E('Invalid eternal key',E::PHP,input:$key);

			file_put_contents($this->storage."/{$key}.json",json_encode($value,static::JSON));
		}
	}

	/** Получение данных из кэша
	 * @param string $key Имя ячейки хранения кэша
	 * @param bool $eternal Флаг добывания значения из "вечного" кэша
	 * @throws E
	 * @return mixed */
	function Get(string$key,bool$eternal=false):mixed
	{
		if($out=$this->Engine->Get($key))
		{
			if(is_array($out) and isset($out[1]) and $out[2]<time())
			{
				$this->Put($key,$out[0],$out[1]);

				return null;
			}

			return$out[0];
		}

		if(!$eternal or !$key)
			return$out;

		if(preg_match('#^[a-z\d.\-_]+$#i',$key)>0)
			throw new E('Invalid eternal key',E::PHP,input:$key);

		$file=$this->storage."/{$key}.json";

		if(!is_file($file))
			return null;

		$out=json_decode(file_get_contents($file),true);

		$this->Put($key,$out);

		return$out;
	}

	/** Удаление данных из кэша
	 * @param string $key Имя ячейки хранения кэша
	 * @param bool $eternal Флаг удаления кэша из таблицы "вечного" хранения
	 * @throws E */
	function Delete(string$key,bool$eternal=false):void
	{
		$this->Engine->Delete($key);

		if($eternal)
		{
			if(preg_match('#[\s\#"\'\\\\/:*?<>|%]+#',$key)>0)
				throw new E('Invalid eternal key',E::PHP,input:$key);

			$file=$this->storage."/{$key}.php";

			if(is_file($file))
				Files::Delete($file);
		}
	}

	/** Пометка кэша устаревшим для его регенерации. В отличие от Delete, не влечет за собой появление dog-pile effect
	 * @param string $key Имя ячейки хранения кэша */
	function Obsolete(string$key):void
	{
		if(false!==$out=$this->Engine->Get($key))
			if(is_array($out) and isset($out[1]) and 0<$ttl=($out[2]-time()))
			{
				$out[2]=0;
				$this->Engine->Put($key,$out,$ttl);
			}
			else
				$this->Engine->Delete($key);
	}
}

return Cache::class;