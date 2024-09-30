<?

spl_autoload_register(function ($class) {
    $class = str_replace(__NAMESPACE__, '', $class);
    $class = str_replace('\\', '/', $class);
    $slash = ($class[0] == '/') ? '' : '/';
    include_once __DIR__ . $slash . $class . '.php';
});