#PHP MySQL Clustering CLOPE

###Установка:

Установите на ваш сервер. 
    
```
git clone git://github.com/infozor/php_mysql_clustering_clope.git
```

Выполните скрипт для создания таблиц `clope.sql`, расположенный в директории `_DB` проекта.


###Базовые особенности:

В системе реализован масштабируемый алгоритм кластеризации CLOPE [http://www.basegroup.ru/library/analysis/clusterization/clope/][1]. 


Для теста системы используется класс `Test` и файл `sources.txt`, расположенный в директории `buf`, с выборками транзакций (первые 100 из теста mashroom)


Тест кластеризации запускается из файла `start.php`, находящийся в корне проекта.

    $Test = new Test();
    $Test->execute();
    

Функция `execute()` класса `Test` производит подготовку данных и выполняет кластеризацию используя класс `Clope`.

        $Clope = new Clope();
        $Clope->clustering();

###Онлайн-тест:
    
Онлайн-тест системы расположен на сайте [inforub.ru][2]

Для запуска теста необходимо нажать ссылку `Start`, находящуюся в самом верху страницы.

Начнёт выполнятся скрипт ~ 15 сек.

После успешной кластеризации управление передаётся по url [inforub.ru/result.php][3] и выполняется файл `result.php`

В файле делаются вызовы:

    $Result = new Result();
    $Result->execute(); 




Результат выдаётся на страницу в виде списка транзакций и образованных кластеров, двигайтесь вниз страницы.


  [1]: http://www.basegroup.ru/library/analysis/clusterization/clope/
  [2]: http://inforub.ru
  [3]: http://inforub.ru/result.php
