### English
I'm sick of with all these overbloated fancy php frameworks that are so overpowered that they don't fit into regular shared hosting anymore.

Any such shitty framework requires to be... INSTALLED! You have to not only perform enigmatic manipulations in the command line, but you also have to make changes to the server configuration files (access to which is usually limited). Because without setting up the rewrite engine features, all links will be clumsy.

The next ugly lame thing which natively embedded into any framework is fucking "router". Links like example.com/index.php/controller/action/param (where action is the method of class controller in the separate file) are usually designed by shit-coders obsessed with the idea of wrapping the wrapped wrapper around another wrapper.

Overwhelmingly, even a small site written on a framework is a crazy redundant jumble of abstractions.

I'm sure that PHP is now powerful enough to write code without using crutches in the form of frameworks.

So here is Eleanor PHP Library which doesn't teach you how to code properly, but does some of the dirty work for you:
1. No installation required: just download, integrate and do what the fuck you want.
2. Basic tools included: template engine, cache machines, mysql driver, localization support (see ./l10n/readme.md) and other features are available out of the box.
3. It logs errors and keeps log files organized (errors are grouped and old log files are archived).
4. It provides delayed object creation, so for example connection to MySQL server won't be established until it is really needed.
5. Coherent integration for third-party classes/interfaces/enums available.

Key feature: it's fucking lightweight and readable. So no puzzles under the hood.  

---
### Русский
Я заебался со всеми этими раздутыми модными php-фреймворки, которые разбухли настолько, что уже не вписываются в рамки обычного виртуального хостинга.

Каждый, сука, каждый фреймворк требует УСТАНОВКИ! Приходится, блядь, не только выполнять в терминале загадочные манипуляции, но и править конфигурационные файлы сервера (доступ к которым обычно ограничен) потому что без настройки rewrite engine, все ссылки будут омерзительно корявыми.

Следующая уродская вещь, которая обязательно встроена в каждый фреймворк - это сраный "роутер". Ссылки вида example.com/index.php/controller/action/param (где action это метод класса controller внутри отдельного файла) обычно создаются говнокодерами, которые считают что говнокод обёрнутый в несколько обёрток, перестаёт быть говнокодом.

В большинстве случаев даже небольшой сайт, написанный на фреймворке, представляет собой безумно-избыточную мешанину абстракций.

Я уверен, что в PHP сейчас достаточно мощи, чтобы писать код без использования костылей в виде фреймворков.

Поэтому вот Eleanor PHP Library, которая не учит как правильно кодить, но берет на себя некоторую грязную работу:
1. Установка не требуется: просто скачай, интегрируй и делай что хочешь.
2. Основные инструменты уже включены в комплект: шаблонизатор, кэш-машины, драйвер MySQL, поддержка локализации (смотри ./l10n/readme.md) и другие функции доступны сразу из коробки.
3. Логирование ошибок содержит лог-файлы организованными (ошибки группируются, а старые логи архивируются).
4. Поддерживается отложенное создание объектов, когда, например, соединение с сервером MySQL не устанавливается до тех пока, пока в нём нет необходимости.
5. Доступна гармоничная интеграция сторонних классов/интерфейсов/перечислений.

Ключевая особенность: библиотека чертовски легкая и читабельная. Без головоломок под капотом.