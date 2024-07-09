<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Interfaces;

/** Кэш-машина */
interface Cache
{
	/** Запись значения
	 * @param string $k Ключ. Рекомендуется задавать в виде тег1_тег2 ...
	 * @param mixed $v Значение
	 * @param int $ttl Время жизни этой записи кэша в секундах */
	public function Put(string$k,mixed$v,int$ttl=0):void;

	/** Получение записи из кэша
	 * @param string $k Ключ
	 * @return mixed */
	public function Get(string$k):mixed;

	/** Удаление записи из кэша
	 * @param string $k Ключ */
	public function Delete(string$k):void;

	/** Удаление записей по тегу. Если имя тега пустое - удаляется весь кэш
	 * @param string $tag Тег */
	public function DeleteByTag(string$tag):void;
}

return Cache::class;