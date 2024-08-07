<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;

/** Поддержка кэша */
class Cache extends \Eleanor\BaseClass
{
	/** @var string Путь к файлам "вечного" хранилища. О то, что такое "вечный кэш", читайте ниже */
	public string $storage;

	/** @var \Eleanor\Interfaces\Cache Объект кэш-машины */
	public \Eleanor\Interfaces\Cache $Engine;

	/** Конструктор кэширующего класса
	 * @param ?string $path Путь для хранения файлов для внутренней кэш-машины (когда отсутствуют внешние)
	 * @param ?string $storage Путь сохранения "вечного" кэша. Вечный отличается тем, что его легко править и он
	 * не удаляется вместе с основным. Как правило, в вечном кэше хранятся сгенерированные данные ключ=>значение при
	 * недоступном генераторе.
	 * @throws EE*/
	public function __construct(?string$path=null,?string$storage=null)
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
			throw new EE('Folder for %cache/storage% is write-protected',EE::SYSTEM);
	}

	/** Запись данных в кэш
	 * @param string $key Ключ (имя ячейки хранения кэша
	 * @param mixed $value Хранимые данные
	 * @param int $ttl Время хранения в секундах
	 * @param bool $eternal Запись в качестве "вечного" кэша
	 * @param int $hopeless Время безнадежного устаревания кэша. Используется для предотвращения dog-pile effect. Если меньше $ttl, то преобразуется в значение $ttl*2
	 * По умолчанию в два раза больше $ttl.
	 * @throws EE */
	public function Put(string$key,mixed$value=false,int$ttl=0,bool$eternal=false,int$hopeless=0):void
	{
		if($hopeless<$ttl)
			$hopeless=$ttl*2;

		if($value===false)
			$this->Delete($key,true);
		else
			$this->Engine->Put($key,$hopeless>0 ? [$value,$ttl,time()+$ttl] : [$value],$hopeless);

		if($eternal)
		{
			if(preg_match('#^[a-z\d.\-_]+$#i',$key)>0)
				throw new EE('Invalid eternal key',EE::PHP,null,[ 'input'=>$key ]);

			file_put_contents($this->storage."/{$key}.php",'<?php return '.var_export($value,true).';');
		}
	}

	/** Получение данных из кэша
	 * @param string $key Имя ячейки хранения кэша
	 * @param bool $eternal Флаг добывания значения из "вечного" кэша
	 * @throws EE
	 * @return mixed */
	public function Get(string$key,bool$eternal=false):mixed
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
			throw new EE('Invalid eternal key',EE::PHP,null,[ 'input'=>$key ]);

		$file=$this->storage."/{$key}.php";

		if(!is_file($file))
			return null;

		$out=\Eleanor\AwareInclude($file);

		$this->Put($key,$out);

		return$out;
	}

	/** Удаление данных из кэша
	 * @param string $key Имя ячейки хранения кэша
	 * @param bool $eternal Флаг удаления кэша из таблицы "вечного" хранения
	 * @throws EE */
	public function Delete(string$key,bool$eternal=false):void
	{
		$this->Engine->Delete($key);

		if($eternal)
		{
			if(preg_match('#[\s\#"\'\\\\/:*?<>|%]+#',$key)>0)
				throw new EE('Invalid eternal key',EE::PHP,null,[ 'input'=>$key ]);

			$file=$this->storage."/{$key}.php";

			if(is_file($file))
				Files::Delete($file);
		}
	}

	/** Пометка кэша устаревшим для его перегенерации. В отличие от Delete, не влечет за собой появление dog-pile effect
	 * @param string $key Имя ячейки хранения кэша */
	public function Obsolete(string$key):void
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