<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Abstracts;
use Eleanor;

/** Формирование строки при помощи последовательных вызовов методов с параметрами (fluent interface). Результат каждого
 * такого вызова соединяется с предыдущим. Пример: $Obj->Part1([params])->Part2([params])->Part3([params])
 * Преобразование объекта в строку, возвращает результирующую строку и
 * обнуляет аккумулятор (свойство s). Пример: (string)$Obj
 * Через вызов объекта возможно получить единичный результат метода, не затрагивая общий аккумулятор.
 * Пример: $part=$Obj('Part1'[,params]); */
abstract class Append extends Eleanor\BaseClass implements \Stringable
{
	/** @var string Аккумулятор результата */
	public string $store='';

	/** @var bool Флаг выполненного клонирования
	 * Смысл состоит в том, что каждый fluent interface - отдельный независимый объект. */
	public bool $cloned=false;

	/** @var array Названия свойств, которые должны стать ссылками на оригинальны свойства при клонировании  */
	protected static array $linking=[];

	/** Терминатор Fluent Interface, выдача результата */
	public function __toString():string
	{
		$s=$this->store;
		$this->store='';
		return$s;
	}

	/** Единичное выполнение какого-нибудь шаблона, без изменения текущего буфера
	 * @param string $n Название шаблона
	 * @params Переменные шаблона
	 * @return mixed */
	public function __invoke(string$n,...$params):mixed
	{
		return$this->_($n,$params);
	}

	public function __clone()
	{
		$this->cloned=true;
	}

	/** Реализация fluent interface шаблона
	 * @param string $n Название шаблона
	 * @param array $p Параметры шаблона
	 * @return static */
	public function __call(string$n,array$p):static
	{
		if(!$this->cloned)
		{
			$O=clone$this;

			foreach(static::$linking as $v)
				$O->$v=&$this->$v;

			return$O->__call($n,$p);
		}

		$r=$this->_($n,$p);

		if(is_scalar($r) or $r instanceof \Stringable)
			$this->store.=$r;

		return$this;
	}

	/** Источник шаблонов
	 * @param string $n Название шаблона
	 * @param array $p Параметры шаблона */
	abstract protected function _(string$n,array$p);
}

return Append::class;