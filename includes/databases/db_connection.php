<?php

function env_var(array $keys, $default = null) {
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
    }
    return $default;
}

$host = env_var(['MYSQLHOST', 'MYSQL_HOST', 'DB_HOST', 'DATABASE_HOST', 'HOST']);
$user = env_var(['MYSQLUSER', 'MYSQL_USER', 'DB_USER', 'DATABASE_USER', 'USER']);
$password = env_var(['MYSQLPASSWORD', 'MYSQL_PASSWORD', 'DB_PASSWORD', 'PASSWORD', 'MYSQL_PASS'], '');
$database = env_var(['MYSQLDATABASE', 'MYSQL_DATABASE', 'DATABASE_NAME', 'DB_NAME', 'DATABASE'], '');
$port = env_var(['MYSQLPORT', 'MYSQL_PORT', 'DATABASE_PORT', 'DB_PORT'], 3306);

$databaseUrl = env_var(['DATABASE_URL', 'MYSQL_URL']);
if (!$host && $databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        $host = $parts['host'] ?? $host;
        $user = $parts['user'] ?? $user;
        $password = $parts['pass'] ?? $password;
        $database = isset($parts['path']) ? ltrim($parts['path'], '/') : $database;
        $port = $parts['port'] ?? $port;
    }
}

if (!$host || !$user || $database === '' || !$port) {
    die("Database connection configuration is incomplete. Please set the correct env vars for host, user, password, database, and port.");
}

if (!function_exists('mysqli_connect')) {
    die("PHP extension 'mysqli' is not available. Enable mysqli in Railway or update the build config.");
}

$conn = mysqli_connect($host, $user, $password, $database, $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
