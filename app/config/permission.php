<?php
return [
    'host'  =>  $_ENV['DB_HOST'],
    'port'  =>  "3306",
    'name'  =>  $_ENV['DB_NAME'],
    'user'  =>  $_ENV['DB_USER'],
    'pass'  =>  $_ENV['DB_PASS'],
    'type'  =>  "mysql",
    'prep'  =>  "1"
];

