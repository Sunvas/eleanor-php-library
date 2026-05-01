<?php
$ent=ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE | ENT_DISALLOWED;

return[
	/** Error when connecting to the DB. */
	'connect'=>function($error,$errno,$db)use($ent){
		$error=$errno ? htmlspecialchars($error,$ent,Eleanor\CHARSET,false) : '';

		if($error)
			$error=<<<HTML
: <b>{$error}</b> (error #<b>{$errno}</b>)
HTML;
		else
			$error='.';

		return<<<HTML
Can't connect to database {$db}{$error}
HTML;
	},

	/** Error in query */
	'query'=>function($error,$errno,$query)use($ent){
		$error=htmlspecialchars($error,$ent,Eleanor\CHARSET,false);

		return<<<HTML
SQL query failed: <b>{$error}</b> (error #{$errno})
HTML;
	},

	/** Error in prepared statement */
	'prepared'=>function($error,$errno,$query,$params)use($ent){
		$error=htmlspecialchars($error,$ent,Eleanor\CHARSET,false);

		return<<<HTML
Prepared statement failed: <b>{$error}</b> (error #{$errno})
HTML;
	},

	/** Error in value: it is expected that each value passed to DB should be of a primitive type (scalar or null) */
	'value'=>function($type,$name){
		return<<<HTML
<code>{$type}</code> was passed as value of <code>{$name}</code>
HTML;
	},

	/** Unknown error */
	'default'=>function($error,$errno)use($ent){
		$error=htmlspecialchars($error,$ent,Eleanor\CHARSET,false);

		return<<<HTML
MySQL failed: <b>{$error}</b> (error #{$errno})
HTML;
	},
];