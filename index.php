<?php
header('Content-Type: text/html; charset=utf-8');

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
