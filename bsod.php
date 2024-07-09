<?php
/**
	Eleanor PHP Library © 2024
	https://eleanor-cms.ru/library
	library@eleanor-cms.ru
*/
namespace Eleanor\Classes;
$l10n=new L10n('bsod');

return[
	/** Текстовая версия
	 * @param string $error Текст ошибки
	 * @param int|string $code Код ошибки, по которому ошибку легко идентифицировать программно
	 * @param ?string $file Путь к файлу, в котором возникла ошибка
	 * @param ?int $line Номер строки на которой возникла ошибка
	 * @param ?string $hint Подсказка для исправления
	 * @param ?array $payload данные, которые привели к сбою */
	'text'=>function($error,$code,$file,$line,$hint,$payload)use($l10n){
		if($line)
			$line="[{$line}]";

		if($file)
			$file=PHP_EOL.$l10n['file'].': '.$file.$line;

		if($hint)
			$hint=$l10n['hint'].': '.$hint;

		if($payload)
			$payload=str_repeat(PHP_EOL,3)
				.json_encode($payload,JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

		return<<<TEXT
{$l10n['error_occurred']}
=======
{$error}{$file}
=======
{$hint}{$payload}
TEXT;
	},

	/** JSON версия
	 * @param string $error Текст ошибки
	 * @param int|string $code Код ошибки, по которому ошибку легко идентифицировать программно
	 * @param ?string $file Путь к файлу, в котором возникла ошибка
	 * @param ?int $line Номер строки на которой возникла ошибка
	 * @param ?string $hint Подсказка для исправления
	 * @param ?array $payload данные, которые привели к сбою */
	'json'=>function($error,$code,$file,$line,$hint,$payload){
		$data=['ok'=>false]+compact('error','code','file','line','hint','payload');

		return json_encode($data,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	},

	/** HTML версия
	 * @param string $error Текст ошибки
	 * @param int|string $code Код ошибки, по которому ошибку легко идентифицировать программно
	 * @param ?string $file Путь к файлу, в котором возникла ошибка
	 * @param ?int $line Номер строки на которой возникла ошибка
	 * @param ?string $hint Подсказка для исправления
	 * @param ?array $payload данные, которые привели к сбою */
	'html'=>function($error,$code,$file,$line,$hint,$payload)use($l10n)
	{
		if($line)
			$line=<<<HTML
<span style="color:red" title="{$l10n['line']}">[{$line}]</span>
HTML;

		if($file)
			$file=<<<HTML
<code><span style="color:darkblue" title="{$l10n['file']}">{$file}</span>{$line}</code>
HTML;

		if($hint)
			$hint="<i title='{$l10n['hint']}'>{$hint}</i>";

		if($payload)
		{
			$payload=json_encode($payload,JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			$payload=<<<HTML
<script type="application/json">{$payload}</script>
HTML;
		}

		$charset=\Eleanor\CHARSET;
		$base=\Eleanor\SITEDIR;
		$year=idate('Y');
		$lang=L10n::$code;

		return<<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="{$charset}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="none">
<base href="{$base}">
<title>{$l10n['error_occurred']} :: {$error}</title>
<style>
body{color:black;background-color:whitesmoke;font-family:sans-serif}
header{font-size:1.6rem;padding:.5rem 0 .4rem}
main{margin:.5rem 0;padding:.75rem 0;border:darkgray dashed;border-width:2px 0 2px;font-size:1.1rem}
code+code{display:block; margin-top:.5rem}
footer{position:fixed;bottom:10px;right:10px;font-size:.8rem;}
a{text-decoration:none}
</style>
</head>
<body>
<header>{$l10n['error_occurred']}</header>
<main><code>{$error}</code>{$file}</main>
{$hint}{$payload}
<footer>Powered by <a href="https://eleanor-cms.ru/library" target="_blank">Eleanor PHP Library</a> &copy; {$year}</footer>
</body>
</html>
HTML;
	}
];