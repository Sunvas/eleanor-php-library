### English
# PHP Abstract Classes Directory

This directory is intended for placing abstract PHP classes within the Eleanor PHP Library.

## How the autoloader works

The autoloader operates based on the class name that needs to be loaded. For instance, when the `Eleanor\Abstracts\TestClass` class is requested, the autoloader attempts to load a file whose name is derived from the class name: it’s converted to lowercase and formatted as kebab-case. The autoloader will look for files like `testclass.php` or `test-class.php`.

## Rules and requirements

1. **File naming**. File names must use kebab-case (lowercase words separated by hyphens). Example: `camel-case-class.php`.
2. **Returning the fully qualified class name**. If the class name doesn’t match the file name, the file must return a string containing the fully qualified name of the class it defines. Based on this string, the autoloader either loads the class directly (if it’s already in the `Eleanor\Abstracts` namespace) or creates an alias for it within `Eleanor\Abstracts`.  
   Example of what the file should contain:
   ```php
   <?php

   namespace Vendor\Package;

   abstract class ClassName
   {
       // ...
   }

   return \Vendor\Package\ClassName::class;
   ```
3. **Class name and file name correspondence**. If a class name matches its file name (after the kebab-case conversion), the file doesn’t need to return a string with the fully qualified class name — the autoloader will handle the class on its own.
4. **Namespace and aliases**. Classes don’t have to belong to the `Eleanor\Abstracts` namespace. If a class is defined in a different namespace, the autoloader creates an alias for it in `Eleanor\Abstracts` to ensure it’s accessible via the unified autoloading mechanism.
5. **Subdirectories and namespace hierarchy**. Subdirectories are supported — each subdirectory adds another level to the `Eleanor\Abstracts` namespace. For example, a `test` subdirectory corresponds to the `Eleanor\Abstracts\Test` namespace.
   **Example** Let’s say the `Eleanor\Abstracts\Test\SomeFeature` class is being requested. The autoloader will check for these files:
- `./test/somefeature.php`;
- `./test/some-feature.php`.

If `./test/some-feature.php` is found and contains the following:
```php
<?php

namespace External\Library;

abstract class FeatureClass
{
    // ...
}

return \External\Library\FeatureClass::class;
```
then an alias will be created: `Eleanor\Abstracts\Test\SomeFeature` will point to the `\External\Library\FeatureClass` class.

---
### Русский

# Каталог абстрактных классов PHP

Этот каталог предназначен для размещения абстрактных PHP-классов в рамках библиотеки Eleanor PHP Library.

## Правила работы автозагрузчика

При запросе класса (например, `Eleanor\Abstracts\TestClass`) автозагрузчик ищет файл в каталоге абстрактных классов, преобразуя имя класса в формат имени файла: он приводит к нижнему регистру и применяет стиль kebab-case. Например, для класса `Eleanor\Abstracts\TestClass` будут проверены пути `testclass.php` и `test-class.php`.

## Основные правила и требования

1. **Именование файлов**. Имя файла записывается в формате kebab-case (слова в нижнем регистре, разделённые дефисом). Например: `camel-case-class.php`.
2. **Возврат полного имени класса**. Если имя класса не соответствует имени файла, то файл обязан вернуть строку с полным (квалифицированным) именем класса, который он определяет. На основе этой строки автозагрузчик либо загружает класс напрямую (если он уже находится в пространстве имён `Eleanor\Abstracts`), либо создаёт для него алиас в `Eleanor\Abstracts`.
   Пример завершающей строки:
    ```php
    return \Vendor\Package\ClassName::class;
    ```
3. **Соответствие имени класса и имени файла**. Если имя класса (с учётом пространства имён) соответствует имени файла (после преобразования в kebab-case), то файл может не содержать возвращающей строки — автозагрузчик самостоятельно обработает класс из этого файла.
4. **Пространство имён и алиасы**. Класс не обязан принадлежать пространству имён `Eleanor\Abstracts`. Если класс определён в другом пространстве имён, автозагрузчик создаст для него алиас внутри `Eleanor\Abstracts`, чтобы обеспечить совместимость и доступность через единый механизм автозагрузки.
5. **Подкаталоги и иерархия пространств имён**. Поддерживается организация классов через подкаталоги. Каждый подкаталог добавляет очередной уровень в пространство имён `Eleanor\Abstracts`. Например, если класс размещён в подкаталоге `test`, то его алиас будет создан в пространстве имён `Eleanor\Abstracts\Test`.

   **Пример**. Пусть запрашивается класс `Eleanor\Abstracts\Test\SomeFeature`. Автозагрузчик проверит наличие файлов `./test/somefeature.php` или `./test/some-feature.php`. Если найден файл `./test/some-feature.php`, содержащий:
    ```php
    <?php
    namespace External\Library;

    abstract class FeatureClass { /* ... */ }

    return \External\Library\FeatureClass::class;
    ```
   то будет создан алиас: `Eleanor\Abstracts\Test\SomeFeature` для доступа к классу `\External\Library\FeatureClass`.