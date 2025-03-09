<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
use Eleanor;

/** Библиотека html примитивов */
class Html extends Eleanor\Basic
{
	/** @const ENT Компиляция ENT_* констант */
	const int ENT=ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE | ENT_DISALLOWED;

	/** Преобразование ассоциативного массива в параметры тега
	 * @param array $a Ассоциативный массив с параметрами название параметра=>значение параметра
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Params(array$a,int$mode=0b110):string
	{
		$params='';

		foreach($a as $k=>$v)
		{
			if($v===null or $v===false)
				continue;

			if(is_int($k))
				$params.=' '.$v;
			else
			{
				$params.=' '.$k;

				if($v!==true)
					$params.='="'.static::ParamValue($v,$mode).'"';
			}
		}

		return$params;
	}

	/** Обработка строки для безопасного её использования в качестве значения параметра тега
	 * @param \Closure|string $s Данные
	 * @param int $mode Режим работы:
	 *  1й бит включает применение htmlspecialchars_decode, чтобы привести строку к виду, в каком её видит пользователь
	 *  2й бит включает применение htmlspecialchars
	 *  3й бит отключается применение double encode в htmlspecialchars
	 * @param string $charset Кодировка
	 * @return string */
	static function ParamValue(\Closure|string$s,int$mode=0b11,string$charset=Eleanor\CHARSET):string
	{
		if($s instanceof \Closure)
			return call_user_func($s);

		if($mode & 0b1)
			$s=htmlspecialchars_decode($s,static::ENT);

		if($mode & 0b10)
			$s=htmlspecialchars($s,static::ENT,$charset,~$mode & 0b100);

		return$s;
	}

	/** Генерация тега
	 * @param string $tag Имя тега (не фильтруется)
	 * @param \Closure|string $content Надпись на кнопке (значение)
	 * @param array $params Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Tag(string$tag,\Closure|string$content='',array$params=[],int$mode=0b11):string
	{
		$params=static::Params($params,$mode);
		$content=static::ParamValue($content,$mode);

		return"<{$tag}{$params}>{$content}</{$tag}>";
	}

	/** Генерация <input type="radio" />
	 * @param ?string $name Имя
	 * @param string|int $value Значение
	 * @param bool $checked Флаг отмеченности
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Radio(?string$name,string|int$value,bool$checked=false,array$extra=[],int$mode=0b11):string
	{
		$params=static::Params($extra+[
			'type'=>'radio',
			'value'=>$value,
			'name'=>$name,
			'checked'=>$checked
		],$mode);

		return"<input{$params}>";
	}

	/** Генерация <textarea>
	 * @param ?string $name Имя
	 * @param string $content Значение
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Text(?string$name,string$content='',array$extra=[],int$mode=0b11):string
	{
		return static::Tag('textarea',$content,$extra+['name'=>$name],$mode);
	}

	/** Генерация <input type="checkbox">. Метод не содержит отдельного аргумента для значения, поскольку 99% чекбоксам
	 * не важно значение - важно чтобы они передались на сервер. Значение чекбокса можно установить через $extra.
	 * @param ?string $name Имя
	 * @param bool $checked Флаг отмеченности
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Check(?string$name,bool$checked=false,array$extra=[],int$mode=0b11):string
	{
		$params=static::Params($extra+[
			'type'=>'checkbox',
			'value'=>1,
			'name'=>$name,
			'checked'=>$checked
		],$mode);

		return"<input{$params}>";
	}

	/** Генерация <input> type по умолчанию равно text
	 * @param ?string $name Имя
	 * @param string|int|null $value Значение
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Input(?string$name,string|int|null$value=null,array$extra=[],int$mode=0b11):string
	{
		$params=static::Params($extra+[
			'value'=>$value,
			'type'=>'text',
			'name'=>$name,
		],$mode);

		return"<input{$params}>";
	}

	/** Генерация кнопок на основе <input>
	 * @param string $value Надпись на кнопке (значение)
	 * @param string $type Тип кнопки: submit, button, reset
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function InputButton(string$value='OK',string$type='submit',array$extra=[],int$mode=0b11):string
	{
		return static::Input(false,$value,$extra+['type'=>$type],$mode);
	}

	/** Генерация кнопок
	 * @param string $content Надпись на кнопке (значение)
	 * @param string $type Тип кнопки: submit, button, reset
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Button(string$content='OK',string$type='submit',array$extra=[],int$mode=0b11):string
	{
		return static::Tag('button',$content,$extra+['type'=>$type],$mode);
	}

	/** Генерация <option> для Select
	 * @param string $view Выводимое значение
	 * @param ?string $value Значение
	 * @param bool $selected Флаг отмеченности
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Option(string$view,?string$value=null,bool$selected=false,array$extra=[],int$mode=0b11):string
	{
		return static::Tag('option',$view,$extra+['value'=>$value,'selected'=>$selected],$mode);
	}

	/** Генерация <optgroup> для Select
	 * @param string $label Название группы
	 * @param string $options Перечень option-ов
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Optgroup(string$label,string$options,array$extra=[],int$mode=0b110):string
	{
		return static::Tag('optgroup',fn()=>$options,$extra+['label'=>$label],$mode);
	}

	/** Генерация <select> с одиночным выбором
	 * @param ?string $name Название select-а
	 * @param string $options Перечень option-ов
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Select(?string$name,string$options='',array$extra=[],int$mode=0b110):string
	{
		if(!$options and !isset($extra['disabled']))
		{
			$options=self::Option('');
			$extra['disabled']=true;
		}

		return static::Tag('select',fn()=>$options,$extra+['name'=>$name],$mode);
	}

	/** Генерация <select> с множественным выбором
	 * @param ?string $name Название select-а
	 * @param string $options Перечень option-ов
	 * @param array $extra Дополнительные параметры
	 * @param int $mode Смотри описание в ParamValue
	 * @return string */
	static function Items(?string$name,string$options='',array$extra=[],int$mode=0b110):string
	{
		return static::Select(!$name || str_ends_with($name,'[]') ? $name : $name.'[]',$options,$extra+['size'=>5,'multiple'=>true],$mode);
	}
}

return Html::class;