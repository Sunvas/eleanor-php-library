<?php
$ent=ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE | ENT_DISALLOWED;

return[
	/** Ошибка при подключении к БД */
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

	/** Ошибка в запросе */
	'query'=>function($error,$errno,$query)use($ent){
		$error=htmlspecialchars($error,$ent,Eleanor\CHARSET,false);

		return<<<HTML
SQL query failed: <b>{$error}</b> (error #{$errno})
HTML;
	},

	/** Ошибка в prepared statement */
	'prepared'=>function($error,$errno,$query,$params)use($ent){
		$error=htmlspecialchars($error,$ent,Eleanor\CHARSET,false);

		return<<<HTML
Prepared statement failed: <b>{$error}</b> (error #{$errno})
HTML;
	},

	/** Неизвестная ошибка */
	'default'=>function($error,$errno)use($ent){
		$error=htmlspecialchars($error,$ent,Eleanor\CHARSET,false);

		return<<<HTML
MySQL failed: <b>{$error}</b> (error #{$errno})
HTML;
	},
];