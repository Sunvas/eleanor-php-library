<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;

/** Специальная строка для передачи в шаблонизатор. Может быть адаптирована под нужны шаблонизатора */
class Stringable extends \Eleanor\Basic implements \Stringable
{
	/** @var Callable Генератор строки */
	protected $Callback;

	/** @param Callable $Callback Генератор строки. Все параметры функции должны иметь параметры по умолчанию
	 * @throws E */
	function __construct(callable$Callback)
	{
		if(!is_callable($Callback))
			throw new E('Callback is not callable',E::PHP,input:$Callback);

		$this->Callback=$Callback;
	}

	/** Нельзя бросить исключение из метода __toString(). Попытка это сделать закончится фатальной ошибкой.
	 * @return mixed */
	function __toString():string
	{
		return $this->__invoke();
	}

	/** Вызов объекта, как функцию */
	function __invoke(...$a):\Stringable
	{
		return call_user_func($this->Callback);
	}
}

return Stringable::class;