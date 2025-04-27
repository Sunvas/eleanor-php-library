<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Traits;

/** File and Line for Exceptions. Mainly to extend SPL exceptions (LogicException, RuntimeException) */
trait FL4E
{
	/** @url https://www.php.net/manual/en/class.exception.php */
	function __construct(string$message,int$code=0,?\Throwable$previous=null,?string$file=null,?int$line=null)
	{
		if($file!==null)
			$this->file=$file;

		if($line!==null)
			$this->line=$line;

		parent::__construct($message,$code,$previous);
	}
}

return FL4E::class;