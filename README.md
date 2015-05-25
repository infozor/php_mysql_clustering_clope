
PHP MySQL Clustering CLOPE

Установка:

    Установите на ваш сервер. ( git clone git://github.com/infozor/php_mysql_clustering_clope.git )

    Выполните скрипт для создания таблиц _DB/.clope.sql

Базовые особенности:

    Для теста системы используется класс Test и файл buf/sources.txt с выборками транзакций (первые 100 из теста mashroom)
    Функция execute класса Test() производит подготовку данных и выполняет кластеризацию используя класс Clope.
    
