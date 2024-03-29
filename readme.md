## Установка

Для установки и развёртывания проекта понадобится docker и compose (v2) для него

Установка:
```shell
docker compose build
```
протестировано на версии докера v20.10.22 и compose v2.16.0

## Развёртывание

Любыми средствами, например через консоль:
```shell
docker compose up -d
```

## Результат

Для получение результата используется CLI интерфейс. 
Для соединения с ним следует обращаться к контейнеру `cli` 

для скачивания данных и сохранение в БД/ФС:
```shell
docker compose run cli cars download
```

Картинки складываются в папку `storage` в корне проекта
Данные в БД `solaf` в таблицу `car`

для получения значений вычисления:
```shell
docker compose run cli cars summary
```

## Запуск тестов

Для ручного запуска тестов можно использовать схожий метод:
```shell
docker compose run cli composer test
```

Т.к. в `PHPUnit` я так и не понял, как без странны библиотек 
подменять (to mock) существующие в рантайме классы и методы 
(а понял только, как только создавать такой обект и передавать его вовнутрь),
то не стал городить огород автомоных подключений. 
Правда из-за этого тесты не только юнит, вышли.

По этой же причине не стал тестировать верхний уровень, 
обойдясь тестами библиотек.

## Дополнительно

Т.к. для выкачивания используется RabbitMQ, то доступна консоль (solaf/solaf):
http://localhost:15672/

Так же используется PostgreSQL и для него есть доступный pgadmin4 (pgadmin4@pgadmin.org/admin):
http://127.0.0.1:5050/browser/

## Возможные проблемы

При запуске не поднимается postgres/rabbit, жалуется на порт? 
Можно изменить публичный порт через соотвествующие переменные окружения (либо через docker-compose.yml)

Проблемы с первым запуском из-за прав?
В корне проекта создаётся папака `.data` и в ней 2 директории: 
`postgres` и `rabbitmq`. У всех следует назначить права ugo+rwx.
