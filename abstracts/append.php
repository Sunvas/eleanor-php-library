<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Abstracts;
use Eleanor;

/** Формирование строки при помощи последовательных вызовов методов с параметрами (fluent interface). Результат каждого
 * такого вызова конкатенируется с предыдущим. Пример: $Obj->Part1([params])->Part2([params])->Part3([params])
 * Преобразование объекта в строку, возвращает результат и обнуляет аккумулятор (свойство store). Пример: (string)$Obj
 * Через вызов объекта возможно получить единичный результат метода, не затрагивая общий аккумулятор.
 * Пример: $part=$Obj('Part1'[,params]); */
abstract class Append extends Eleanor\Basic implements \Stringable
{
	/** @var string Аккумулятор результата */
	public string $store='';

	/** @var bool Флаг первичного объекта: каждый fluent interface - это отдельный объект, клонированный с первичного */
	readonly bool $primary;

	/** @var array Названия свойств, которые должны стать ссылками на оригинальны свойства при клонировании  */
	protected static array $linking=[];

	function __construct()
	{
		$this->primary=true;
	}

	function __clone()
	{
		$this->primary=false;
	}

	/** Терминатор Fluent Interface, выдача результата */
	function __toString():string
	{
		$s=$this->store;
		$this->store='';
		return$s;
	}

	/** Единичное выполнение какого-нибудь шаблона, без изменения текущего буфера
	 * @param string $n Название шаблона
	 * @param mixed ...$params Переменные шаблона
	 * @return string */
	function __invoke(string$n,...$params):string
	{
		return$this->_($n,$params);
	}

	/** Реализация fluent interface шаблона
	 * @param string $n Название шаблона
	 * @param array $p Параметры шаблона
	 * @return static */
	function __call(string$n,array$p):static
	{
		if($this->primary)
		{
			$O=clone$this;

			foreach(static::$linking as $v)
				$O->$v=&$this->$v;

			return$O->__call($n,$p);
		}

		$this->store.=$this->_($n,$p);

		return$this;
	}

	/** Источник шаблонов
	 * @param string $n Название шаблона
	 * @param array $p Параметры шаблона */
	abstract protected function _(string$n,array$p):string;
}

return Append::class;