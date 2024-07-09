### English
Localization (l10n) files for library components. Each file should return array where values can be either strings (for plain translation cases) or Closures (for complex translation cases). Each file name contains suffix with code of language.

I'm pretty sure that localization via functions like `__()` was designed by morons and is only used by imbeciles. I admit that even such a shitty approach can solve some primitive l10n cases. But when it comes to really complicated translation twists, translation files MUST support programming. Here are just 2 examples, which should be enough to show why the gettext format sucks:

1. No inheritance or translation reuse. That's obvious.
2. Impossibility of complicated sorted enumerations, especially when dealing with plurals. Just try to obtain a harmonious message with 2 variables defining number of apples and oranges, covering the following cases:
   - "There's nothing" (when both are 0);
   - "There is just 1 apple" or "There is just 1 orange" (when one variable equals 0 and other equals 1);
   - "There are just X apples" or "There are just X oranges" (when one variable equals 0 and other is greater than 1);
   - "There are X apples and 1 orange" or "There are X oranges and 1 apple" (when greater variable should come first);
   - "There are X oranges and apples each" (when both variable are the same);

---
### Русский
Языковые (l10n) файлы компонентов библиотеки. Каждый файл должен возвращать массив, где в качестве значений могут быть либо строки (для случая простого перевода) или Closure (для случая сложного перевода). Каждое имя файла содержит суффикс с кодом языка.

Я абсолютно уверен, что локализация через функции вроде `__()` была придумана дебилами, а используется исключительно имбецилами. Хотя я допускаю, что в примитивных случаях локализации, это дерьмище вполне может работать. Но когда приходится иметь дело с действительно сложными языковыми конструкциями, без программирования не обойтись. Вот 2 простых примера которых вполне достаточно, чтобы понять что формат gettext это отстой:

1. Невозможно наследование или повторное использования перевода. Это совершенно очевидно.
2. Невозможность сложных сортированных перечислений, особенно когда речь идет о множественном числе. Пример можно посмотреть выше в английской версии, только нужно учесть что для русского языка множественные существительные должны согласовываться с числом (2 яблока или апельсина, но 7 яблок или апельсинов). 