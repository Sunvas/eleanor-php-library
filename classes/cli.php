<?php
# Eleanor PHP Library © 2026 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;

/** Class provides convenient text formatting for terminal output using ANSI escape sequences. It supports foreground
 * and background colors, as well as additional text styles for improving readability and highlighting important
 * information in command-line applications.
 *
 * Supported foreground colors:
 * black, red, green, yellow, blue, purple, cyan, white
 *
 * Background colors are specified by prefixing the color name with an underscore:
 * _red, _blue, _green, etc.
 *
 * Bright variants of colors can be enabled by writing the color name in uppercase:
 * RED, _BLUE, WHITE, etc.
 *
 * Supported text styles:
 * bold, underline, strikethrough
 *
 * The class implements a fluent interface, allowing multiple formatting methods to be chained together in a compact and expressive way:
 * new CLI()->bold->green('Hello world!')->Write();
 *
 * Use 'reset' to clear all previously applied styles and colors.
 * Multiple styles and colors may be combined to create expressive and visually distinct console output.*/

class CLI extends \Eleanor\Abstracts\Append
{
	/** @var int Current string length */
	protected(set) int $length=0;

	/** @var bool Flag of opened style tag */
	protected bool $opened=false;

	/** @var int Length of previous string. Used to paint over characters remaining from the previous output. */
	protected int $prev_len=0;

	/** @param string $text Init text
	 * @param string $style Styles or colors applied to text */
	function __construct(string$text='',string$style='')
	{
		parent::__construct();

		if($style)
			$this->storage=$this->_($style,$text);
		elseif($text)
			$this->concat($text);
	}

	function __toString():string
	{
		$s=parent::__toString().($this->opened ? 'm' : '');

		$this->length=0;
		$this->opened=false;

		return $s;
	}

	function __get(string$n):static
	{
		return $this->__call($n,[]);
	}

	/** Add text without modification of style
	 * @param string $text
	 * @return static */
	function Concat(string$text):static
	{
		if($this->opened)
		{
			$this->length++;
			$this->storage.='m';
			$this->opened=false;
		}

		$this->length+=\strlen($text);
		$this->storage.=$text;

		return $this;
	}

	/** Styler
	 * @param string $n Style or color
	 * @param string ...$a Text
	 * @return string
	 * @throws E */
	protected function _(string$n,...$a):string
	{
		$code=match($n){
			#Colors for text
			'black'=>30,
			'red'=>31,
			'green'=>32,
			'yellow'=>33,
			'blue'=>34,
			'purple'=>35,
			'cyan'=>36,
			'white'=>37,

			#High Intensity colors for text
			'BLACK'=>90,
			'RED'=>91,
			'GREEN'=>92,
			'YELLOW'=>93,
			'BLUE'=>94,
			'PURPLE'=>95,
			'CYAN'=>96,
			'WHITE'=>97,

			#Colors for background
			'_black'=>40,
			'_red'=>41,
			'_green'=>42,
			'_yellow'=>43,
			'_blue'=>44,
			'_purple'=>45,
			'_cyan'=>46,
			'_white'=>47,

			#High Intensity colors for background
			'_BLACK'=>100,
			'_RED'=>101,
			'_GREEN'=>102,
			'_YELLOW'=>103,
			'_BLUE'=>104,
			'_PURPLE'=>105,
			'_CYAN'=>106,
			'_WHITE'=>107,

			#Style
			'bold'=>1,
			'underline'=>4,
			'strikethrough'=>9,

			'reset'=>0,
			default=>throw new E('Unknown CLI style',E::PHP,...\Eleanor\BugFileLine($this),input:['name'=>$n])
		};

		# If text is provided
		if($a)
		{
			$c=$this->opened ? ",{$code}m" : "\e[{$code}m";
			$s=\join('',$a);

			$this->length+=\strlen($s);
			$this->opened=false;

			return $c.$s;
		}

		# Open style sequence without writing text yet
		$s=$this->opened ? ',' : "\e[";
		$this->opened=true;

		return $s.$code;
	}

	/** Writing content to a stream
	 * @param bool $eol Option to terminate string
	 * @param resource $stream  of an opened stream
	 * @return static */
	function Write(bool$eol=true,mixed$stream=\STDOUT):static
	{
		$length=$this->length;
		$spaces=$this->prev_len>$length ? \str_repeat(' ',$this->prev_len-$length) : '';

		\fwrite($stream, $this.$spaces.($eol ? \PHP_EOL : "\r"));

		$this->prev_len=$eol ? 0 : $length;

		return $this;
	}
}

# Not required here because class name matches filename
return CLI::class;