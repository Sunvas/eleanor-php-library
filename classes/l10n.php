<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor, Eleanor\Enums\DateFormat;
use function Eleanor\BugFileLine;

/** Реализация языковой поддержки */
class L10n extends Eleanor\BaseClass implements \ArrayAccess, \Eleanor\Interfaces\L10n
{
	/** @var string Системный язык */
	public static string $code='ru';

	/** @var array Массив с языковыми значениями из файла */
	protected array $data=[];

	/** Формат имени языкового файла [name]-[code].php или [code].php (если имя пустое) Структура языкового файла:
	 * <?php
	 * return [
	 *  'param1'=>'string value',
	 *  'param2'=>fn($v)=>"complex {$v} value",
	 *   ...
	 * ];
	 * @param string $name Имя языкового файла
	 * @param string $source Каталог-источник языковых файлов, обязательно с / в конце
	 * @throws EE */
	public function __construct(string$name,string$source=__DIR__.'/../l10n/')
	{
		$file=$source.($name ? $name.'-' : '').static::$code.'.php';

		if(!is_file($file))
			throw new EE('Missing file '.$file,
				EE::PHP,null,BugFileLine()
			);

		$data=Eleanor\AwareInclude($file);

		if(!is_array($data))
			throw new EE('Wrong file format '.$file,
				EE::PHP,null,BugFileLine()
			);

		$this->data=$data;
	}

	/** Представление даты для человека
	 * @param int|string $d Дата в обычном машинном формате, либо timestamp, 0 либо пустая строка - текущая дата
	 * @param DateFormat $t
	 * @return string */
	public static function Date(int|string$d,DateFormat$t=DateFormat::HumanDateTime):string
	{
		$class=__NAMESPACE__.'\\l10n\\'.static::$code;
		return call_user_func([$class,'Date'],$d,$t);
	}

	/** Установка языковой переменной
	 * @param string|int $k Имя переменной
	 * @param mixed $v Языковое значение */
	public function offsetSet(mixed$k,mixed$v): void
	{
		$this->data[$k]=$v;
	}

	/** Проверка существования языковой переменной
	 * @param string|int $offset Имя переменной
	 * @return bool */
	public function offsetExists(mixed$offset): bool
	{
		return isset($this->data[$offset]);
	}

	/** Удаление языковой переменной
	 * @param string|int $k Имя переменной */
	public function offsetUnset(mixed$k): void
	{
		unset($this->data[$k]);
	}

	/** Получение языковой переменной
	 * @param int|string $k Имя переменной
	 * @throws EE
	 * @return mixed */
	public function offsetGet(mixed$k):mixed
	{
		return $this->data[$k] ?? throw new EE(
			"Unable to get translation key '{$k}'",
			EE::PHP,null,BugFileLine()
		);
	}
}

return L10n::class;