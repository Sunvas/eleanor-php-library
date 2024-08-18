<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes\Cache;
use Eleanor;

/** Кэш-машина MemCache */
class MemCache implements Eleanor\Interfaces\Cache
{
	/** @var string Уникализация кэш машины */
	private string $u;

	/** @var array Ключи находящихся в кэше */
	private array $names;

	/** @var \Memcache Объект MemCache-a */
	public \Memcache $M;

	/** @param string $u Уникализации кэша (на одной кэш машине может быть запущено несколько копий Eleanor)
	 * @throws Eleanor\Classes\EE */
	public function __construct(string$u='')
	{
		$this->u=$u;
		$this->M=new \Memcache;

		#Константы хоста и порта
		$host='Eleanor\\Classes\\Cache\\MEMCACHE_HOST';
		$port='Eleanor\\Classes\\Cache\\MEMCACHE_PORT';

		$connected=$this->M->connect(defined($host) ? constant($host) : 'localhost', defined($port) ? constant($port) : 11211);

		if(!$connected)
		{
			$this->M->close();
			throw new Eleanor\Classes\EE('MemCache failure',Eleanor\Classes\EE::SYSTEM,null,
				['hint'=>'try to delete the file Library/classes/cache/memcache.php']);
		}

		$this->M->setCompressThreshold(20000,0.2);

		$this->names=(array)($this->Get('') ?? []);
	}

	public function __destruct()
	{
		$this->Put('',$this->names);
		$this->M->close();
	}

	/** Запись значения
	 * @param string $k Ключ. Рекомендуется задавать в виде тег1_тег2 ...
	 * @param mixed $v Значение
	 * @param int $ttl Время жизни этой записи кэша в секундах */
	public function Put(string$k,mixed$v,int$ttl=0):void
	{
		$r=$this->M->set($this->u.$k,$v,is_bool($v) || is_int($v) || is_float($v) ? 0 : MEMCACHE_COMPRESSED,$ttl);

		if($r)
			$this->names[$k]=$ttl+time();
	}

	/** Получение записи из кэша
	 * @param string $k Ключ
	 * @return mixed */
	public function Get(string$k):mixed
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

	/** Удаление записи из кэша
	 * @param string $k Ключ */
	public function Delete(string$k):void
	{
		unset($this->names[$k]);
		$this->M->delete($this->u.$k);
	}

	/** Удаление записей по тегу. Если имя тега пустое - удаляется вешь кэш
	 * @param string $tag Тег */
	public function DeleteByTag(string$tag):void
	{
		if($tag)
		{
			foreach($this->names as $k=>$v)
				if($tag=='' or !str_contains($k,$tag))
					$this->Delete($k);
		}
		else
			$this->M->flush();
	}
}

return MemCache::class;