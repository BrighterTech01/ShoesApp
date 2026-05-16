<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/schema.php';

function create_pdo(string $dsn, ?string $databaseName = null): PDO
{
    try {
        return new PDO(
            $dsn,
            (string) app_config('db.username'),
            (string) app_config('db.password'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        $label = $databaseName ? 'database "' . $databaseName . '"' : 'MySQL server';
        throw new RuntimeException(
            'Unable to connect to the ' . $label . '. Check backend/config/app.php MySQL settings. Original error: ' . $exception->getMessage(),
            0,
            $exception
        );
    }
}

function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = (string) app_config('db.host', 'localhost');
    $port = (string) app_config('db.port', '3306');
    $name = (string) app_config('db.name', 'shoesap');
    $charset = (string) app_config('db.charset', 'utf8mb4');
    $autoCreateDatabase = (bool) app_config('db.auto_create_database', true);

    $server = create_pdo("mysql:host={$host};port={$port};charset={$charset}");

    if ($autoCreateDatabase) {
        try {
            $server->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE utf8mb4_unicode_ci',
                str_replace('`', '``', $name),
                $charset
            ));
        } catch (PDOException $exception) {
            // Shared hosting often forbids CREATE DATABASE; continue and try the existing database.
        }
    }

    $connection = create_pdo("mysql:host={$host};port={$port};dbname={$name};charset={$charset}", $name);
    ensure_schema($connection);

    return $connection;
}

function ensure_schema(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    foreach (database_schema_statements() as $statement) {
        $pdo->exec($statement);
    }

    seed_default_data($pdo);
    $initialized = true;
}
