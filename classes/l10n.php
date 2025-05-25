<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Classes;
use Eleanor,
	Eleanor\Enums\DateFormat;
use function Eleanor\BugFileLine;

/** Localization */
class L10n extends Eleanor\Basic implements \ArrayAccess, \Eleanor\Interfaces\L10n
{
	/** @var string Common language code */
	static string $code='ru';

	/** @var array L10n values from file */
	protected readonly array $data;

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

		$data=Eleanor\AwareInclude($file);

		if(!\is_array($data))
			throw new E('Wrong file format '.$file,
				E::PHP,...BugFileLine()
			);

		$this->data=$data;
	}

	/** Human representation of the date
	 * @param int|string $d Date in plain format, or timestamp, or 0 or '' (for current date)
	 * @param DateFormat $t
	 * @return string */
	static function Date(int|string$d,DateFormat$t=DateFormat::HumanDateTime):string
	{
		$class=__NAMESPACE__.'\\l10n\\'.static::$code;
		return \call_user_func([$class,'Date'],$d,$t);
	}

	/** Static obtaining value from existed l10n pool
	 * @param array $l10n Pool of values
	 * @param mixed $d Default value
	 * @return mixed */
	static function Item(array$l10n,mixed$d=null):mixed
	{
		return $l10n[static::$code] ?? $l10n[''] ?? $d;
	}

	/** Set value
	 * @param string|int $k Key
	 * @param mixed $v Value */
	function offsetSet(mixed$k,mixed$v): void
	{
		$this->data[$k]=$v;
	}

	/** Checking availability
	 * @param string|int $k Key
	 * @return bool */
	function offsetExists(mixed$k): bool
	{
		return isset($this->data[$k]);
	}

	/** Deleting value
	 * @param string|int $k Key */
	function offsetUnset(mixed$k): void
	{
		unset($this->data[$k]);
	}

	/** Obtaining value
	 * @param int|string $k Key
	 * @throws E
	 * @return mixed */
	function offsetGet(mixed$k):mixed
	{
		return $this->data[$k] ?? throw new E(
			"Unable to get translation key '{$k}'",
			E::PHP,...BugFileLine()
		);
	}
}

return L10n::class;