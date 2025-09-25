<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Interfaces;

/** Something that can be logged */
interface Loggable
{
	/** Logging */
	function Log();
}

#Not necessary here, since interface name equals filename
return Loggable::class;