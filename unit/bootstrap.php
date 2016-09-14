<?php

require __DIR__ . '/../vendor/autoload.php';

$dsn = strtr('mysql:dbname=%dbname%;hostname=%host%;port=%port%',
    [
        '%dbname%' => $GLOBALS['DB_NAME'],
        '%host%'   => isset($GLOBALS['DB_HOSTNAME']) ? $GLOBALS['DB_HOSTNAME'] : '127.0.0.1',
        '%port%'   => isset($GLOBALS['DB_PORT'])     ? $GLOBALS['DB_PORT']     : 3306,
    ]
);

$connection = new \PDO($dsn, $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD']);

$schema =<<<SQL_EOF
CREATE TABLE IF NOT EXISTS testing_insert_table (
    id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    height DECIMAL(8,4) NOT NULL,
    unit ENUM("METER", "CENTIMETER", "FEET", "INCH"),
    date DATETIME NOT NULL,
    PRIMARY KEY(id)
);
SQL_EOF;

if ($connection->exec($schema) === false) {
    throw new Exception('Could not create schema : ' . $connection->errorInfo()[2]);
}
