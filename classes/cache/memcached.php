<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes\Cache;
use Eleanor;

/** Кэш-машина MemCached */
class MemCached implements Eleanor\Interfaces\Cache
{
	/** @var string Уникализация кэш машины */
	private string $u;

	/** @var array Ключи находящихся в кэше */
	private array $names;

	/** @var \Memcached Объект MemCached-a */
	public \Memcached $M;

	/** @param string $u Уникализации кэша (на одной кэш машине может быть запущено несколько копий Eleanor) */
	public function __construct(string$u='')
	{
		$this->u=$u;

		#Константы хоста и порта
		$host='Eleanor\\Classes\\Cache\\MEMCACHED_HOST';
		$port='Eleanor\\Classes\\Cache\\MEMCACHED_PORT';

		#Поскольку данная кеш-машина весьма специфична, рекомендую прописать значения самостоятельно.
		$this->M=new \Memcached($u);
		$this->M->addServer(defined($host) ? constant($host) : 'localhost',defined($port) ? constant($port) : 11211);

		$this->names=$this->Get('');
	}

	public function __destruct()
	{
		$this->Put('',$this->names);
	}

	/** Запись значения
	 * @param string $k Ключ. Рекомендуется задавать в виде тег1_тег2 ...
	 * @param mixed $v Значение
	 * @param int $ttl Время жизни этой записи кэша в секундах */
	public function Put(string$k,mixed$v,int$ttl=0):void
	{
		$r=$this->M->set($this->u.$k,$v,$ttl);

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
		foreach($this->names as $k=>$v)
			if($tag=='' or !str_contains($k,$tag))
				$this->Delete($k);
	}
}

return MemCached::class;