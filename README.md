# Minify-CSS-JS
Минификатор JS и CSS через API сайта https://www.toptal.com/

## Подключение и настройка
1. Скопировать файл `minify.php` в свой проект
2. Подключить `minify.php` и создать копию класса `Minify`

### Пример использование
Пример использования есть в файле `index.php`

```php
// Подключаем наш класс
require_once 'minify.php';

// Путь до корня сайта
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/';

// Инициализируем класс минификации
$minify = new Minify();

// Добавляем файлы для минификации
$minify->add($root_path . 'style.css');
$minify->add($root_path . 'main.js');

// Запускаем минификатор
$minify->minify();
```
