<?php

// Определяем путь к файлу README.md
$readmeFile = 'README.md';

// Проверяем, существует ли файл
if (!file_exists($readmeFile)) {
    echo "Файл README.md не найден: $readmeFile\n";
    exit(1);
}

// Считываем и выводим содержимое файла
$readmeContent = file_get_contents($readmeFile);
echo $readmeContent;

// Завершаем выполнение скрипта
exit(0);
