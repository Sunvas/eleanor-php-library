<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

use Eleanor\Enums\DateFormat;
use function Eleanor\BugFileLine;

/** Localization loader and accessor */
class L10n extends \Eleanor\Basic implements \ArrayAccess, \Eleanor\Interfaces\L10n
{
	/** @var string Common language code */
	static string $code='en';

	/** @var array L10n values from file */
	protected array $data;

	/** Filename of l10n file must be [name]-[code].php or [code].php (in case when name is empty). Contents:
	 * <?php
	 * return [
	 *  'param1'=>'string value',
	 *  'param2'=>fn($v)=>"complex {$v} value",
	 *   ...
	 * ];
	 * @param string $name Name of l10n file
	 * @param string $source Source folder for l10n files, must end with /
	 * @throws E */
	function __construct(string$name,string$source=__DIR__.'/../l10n/')
	{
		$file=$source.($name ? $name.'-' : '').static::$code.'.php';

		if(!\is_file($file))
			throw new E('Missing file '.$file,
				E::PHP,...BugFileLine()
			);

		$data=\Eleanor\AwareInclude($file);

		if(!\is_array($data))
			throw new E('Wrong file format '.$file,
				E::PHP,...BugFileLine()
			);

		$this->data=$data;
	}

	/** Human-readable date formatting
	 * @param int|string $d Date in plain format, or timestamp, or 0 or '' (for current date)
	 * @param DateFormat $f
	 * @return string */
	static function Date(int|string$d,DateFormat$f=DateFormat::HumanDateTime):string
	{
		$class=__NAMESPACE__.'\\L10n\\'.static::$code;
		return $class::Date($d,$f);
	}

	/** Get localized value from existing l10n data
	 * @param array $l10n Localization values indexed by language code
	 * @param mixed $d Default value returned when localization is not found
	 * @param string $f Fallback l10n key
	 * @return mixed */
	static function Item(array$l10n,mixed$d=null,string$f=''):mixed
	{
		return $l10n[static::$code] ?? $l10n[$f] ?? $d;
	}

	function offsetSet(mixed$offset,mixed$value):void
	{
		$this->data[$offset]=$value;
	}

	function offsetExists(mixed$offset):bool
	{
		return \array_key_exists($offset,$this->data);
	}

	function offsetUnset(mixed$offset):void
	{
		unset($this->data[$offset]);
	}

	function offsetGet(mixed$offset):mixed
	{
		return $this->data[$offset] ?? throw new E(
			"Unable to get translation key '{$offset}'",
			E::PHP,...BugFileLine()
		);
	}
}

# Not required here because class name matches filename
return L10n::class;