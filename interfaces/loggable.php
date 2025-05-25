<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.com/library
	library@eleanor-cms.com
*/
namespace Eleanor\Interfaces;

/** Something that can be logged */
interface Loggable
{
	/** Logging */
	function Log();
}

return Loggable::class;