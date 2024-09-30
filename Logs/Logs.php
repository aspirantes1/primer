<?

namespace Logs;

class Logs
{
    private static $logs = [];
    public static function log($log, $fileName = 'logs.log', $count = 1000)
    {
        $str = date("Y-m-d H:i:s") . ' - ' . $log . "\n";
        self::$logs[] = $str;
        $file = file_exists(__DIR__ . '/' . $fileName) ? file(__DIR__ . '/' . $fileName) : [];
        $file[] = str_replace(PHP_EOL, ' ', $str) . "\n";
        if (count($file) > $count) unset($file[0]);
        file_put_contents(__DIR__ . '/' . $fileName, implode($file), LOCK_EX);
    }

    public static function openLogs()
    {
        foreach (self::$logs as $val) {
            echo $val . '<br/>';
        }
    }
}
