

# canoe test

## decisions and design

## stack

simple php 8.2, laravel, mysql

the test hints at a web app, for which php-laravel is usually my default choice.  


### data model

![ER Diagram](docs/er.png)

I added two extra entities to support the app:
    
 - `alias`: simple name holder connected to the fund - a straighfoward way to allow a fund to contain multiple names in a relational model

 - `duplicate_fund_logs`: the idea there is to store a record referencing every fund that has a potential duplicate, alongside the "problematic" name. This will work essentially as a cache for retrieving data about "which funds may contain duplicates ; what are the duplicate names ; which are the duplicate name funds relationships". 

### api




### duplicate name service



## scalability



## running

```
docker compose up -d

docker exec -it canoe-test-laravel.test-1 /bin/bash

composer install

vendor/bin/phpunit

```
