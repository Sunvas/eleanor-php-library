<?php
/**
	Eleanor PHP Library © 2025
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Interfaces;

/** Something than can be logged */
interface Loggable
{
	/** Logging */
	function Log();
}

return Loggable::class;