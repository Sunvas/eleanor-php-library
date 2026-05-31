<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Traits;

/** Override file and line information for exception classes.
 * Intended for classes extending \Exception, mainly SPL exceptions such as \LogicException, \RuntimeException and \BadMethodCallException. */
trait FL4E
{
	/** @see https://www.php.net/manual/en/class.exception.php */
	function __construct(string$message,int$code=0,?\Throwable$previous=null,?string$file=null,?int$line=null)
	{
		if($file!==null)
			$this->file=$file;

		if($line!==null)
			$this->line=$line;

		parent::__construct($message,$code,$previous);
	}
}

# Not required here because trait name matches filename.
return FL4E::class;