<?php

return [
    'driver' => env('DB_DUMP_DRIVER', 'native'),
    'binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
    'compose_binary' => env('DB_DUMP_COMPOSE_BINARY', PHP_OS_FAMILY === 'Windows'
        ? (getenv('ProgramFiles') ?: 'C:/Program Files').'/Docker/Docker/resources/cli-plugins/docker-compose.exe'
        : null),
    'docker_binary' => env('DB_DUMP_DOCKER_BINARY', PHP_OS_FAMILY === 'Windows'
        ? (getenv('ProgramFiles') ?: 'C:/Program Files').'/Docker/Docker/resources/bin/docker.exe'
        : 'docker'),
    'docker_service' => env('DB_DUMP_DOCKER_SERVICE', 'mysql'),
    'docker_host' => env('DB_DUMP_DOCKER_HOST', '127.0.0.1'),
    'docker_port' => env('DB_DUMP_DOCKER_PORT', '3306'),
];
