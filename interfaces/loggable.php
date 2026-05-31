<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Interfaces;

/** Object that can write itself to a log */
interface Loggable
{
	/** Write object information to the configured log target. May be a no-op when logging is disabled. */
	function Log():void;
}

# Not required here because interface name matches filename.
return Loggable::class;