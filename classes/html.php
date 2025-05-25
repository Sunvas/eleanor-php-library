<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes;
use Eleanor;

/** Generating of html primitives */
class Html extends Eleanor\Basic
{
	/** @const ENT Compilation of ENT_* constants */
	const int ENT=ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE | ENT_DISALLOWED;

	/** Converting associative array to parameters of tag
	 * @param array $a Associative array for tag parameters as key=>value
	 * @param int $mode See description in ParamValue method
	 * @return string */
	static function Params(array$a,int$mode=0b110):string
	{
		$params='';

		foreach($a as $k=>$v)
		{
			if($v===null or $v===false)
				continue;

			if(\is_int($k))
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

	/** Preparing string for safe use it as a tag parameter value
	 * @param \Closure|string $s Data
	 * @param int $mode Working mode:
	 *  1 bit applies htmlspecialchars_decode, to make the string look the user sees it.
	 *  2 bit applies htmlspecialchars
	 *  3 bit disables applying of double encode in htmlspecialchars
	 * @param string $charset Кодировка
	 * @return string */
	static function ParamValue(\Closure|string$s,int$mode=0b11,string$charset=Eleanor\CHARSET):string
	{
		if($s instanceof \Closure)
			return \call_user_func($s);

		if($mode & 0b1)
			$s=\htmlspecialchars_decode($s,static::ENT);

		if($mode & 0b10)
			$s=\htmlspecialchars($s,static::ENT,$charset,~$mode & 0b100);

		return$s;
	}

	/** Generating of tag
	 * @param string $tag Tag name (not escaped)
	 * @param \Closure|string $content Tag's content
	 * @param array $attrs Attributes
	 * @param int $mode See description in ParamValue
	 * @return string */
	static function Tag(string$tag,\Closure|string$content='',array$attrs=[],int$mode=0b11):string
	{
		$attrs=static::Params($attrs,$mode);
		$content=static::ParamValue($content,$mode);

		return"<{$tag}{$attrs}>{$content}</{$tag}>";
	}

	/** Generating <input type="radio" />
	 * @param ?string $name
	 * @param string|int $value
	 * @param bool $checked
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
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

	/** Generating <textarea>
	 * @param ?string $name
	 * @param string $content
	 * @param array $extra Extra parameters
	 * @param int $mode See description in ParamValue
	 * @return string */
	static function Text(?string$name,string$content='',array$extra=[],int$mode=0b11):string
	{
		return static::Tag('textarea',$content,$extra+['name'=>$name],$mode);
	}

	/** Generating <input type="checkbox">. The method does not contain a separate argument for the value, because for
	 * 99% of checkboxes value has no importance. The checkbox value can be set via $extra.
	 * @param ?string $name
	 * @param bool $checked
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
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

	/** Generating <input>
	 * @param ?string $name
	 * @param string|int|null $value
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
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

	/** Generating buttons via <input>
	 * @param string $value Caption
	 * @param string $type Button's type: submit, button, reset
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
	 * @return string */
	static function InputButton(string$value='OK',string$type='submit',array$extra=[],int$mode=0b11):string
	{
		return static::Input(false,$value,$extra+['type'=>$type],$mode);
	}

	/** Generating <button>
	 * @param string $content Caption
	 * @param string $type Button's type: submit, button, reset
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
	 * @return string */
	static function Button(string$content='OK',string$type='submit',array$extra=[],int$mode=0b11):string
	{
		return static::Tag('button',$content,$extra+['type'=>$type],$mode);
	}

	/** Generating <option> for <select>
	 * @param string $content
	 * @param ?string $value
	 * @param bool $selected
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
	 * @return string */
	static function Option(string$content,?string$value=null,bool$selected=false,array$extra=[],int$mode=0b11):string
	{
		return static::Tag('option',$content,$extra+\compact('value','selected'),$mode);
	}

	/** Generating <optgroup> for <select)
	 * @param string $label
	 * @param string $options List of <option>s
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
	 * @return string */
	static function Optgroup(string$label,string$options,array$extra=[],int$mode=0b110):string
	{
		return static::Tag('optgroup',fn()=>$options,$extra+['label'=>$label],$mode);
	}

	/** Generating <select> with single selection
	 * @param ?string $name
	 * @param string $options List of <option>s
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
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

	/** Generating <select> with multi selection
	 * @param ?string $name
	 * @param string $options List of <option>s
	 * @param array $extra Extra attributes
	 * @param int $mode See description in ParamValue
	 * @return string */
	static function Items(?string$name,string$options='',array$extra=[],int$mode=0b110):string
	{
		return static::Select(!$name || \str_ends_with($name,'[]') ? $name : $name.'[]',$options,$extra+['size'=>5,'multiple'=>true],$mode);
	}
}

return Html::class;