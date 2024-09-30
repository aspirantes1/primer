<?

namespace MCRM;

use MCRM\Auth;
use Logs\Logs;

class User
{
    private static $url = 'https://osmidisk.marketingcrm.online/api/v3';
    private static $apiKey = '******';
    private static $accessToken;

    private static function sendRequest($url, $method, $data = null)
    {
        // $url = $url . '?api_key=' . self::$apiKey;
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . self::$apiKey
        ];
        if (isset(self::$accessToken))
            $headers[] = 'x-access-token: ' . self::$accessToken;
        $options = [
            'http' => [
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
                'method' => $method
            ]
        ];
        if ($data) {
            $options['http']['content'] = json_encode($data);
        }
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $heads = get_headers($url);
        var_dump($url, $heads, $headers, $result);
        return $result;
    }

    public static function regUser($data)
    {
        $url = self::$url . '/user/register';
        $result = self::sendRequest($url, 'POST', $data);
        return $result;
    }

    public static function addOrder($data)
    {
        $url = self::$url . '/cash/order';
        $result = self::sendRequest($url, 'POST', $data);
        Logs::log(json_encode(json_decode($result, 1), JSON_UNESCAPED_UNICODE), 'addOrder.log', 100);
        return $result;
    }

    public static function getUser($number, $external_id = null)
    {
        $url = self::$url . '/user/get';
        $data = ['number' => $number];
        if ($external_id) {
            $data['external_id'] = $external_id;
        }
        $result = self::sendRequest($url, 'POST', $data);
        return json_decode($result, true);
    }

    public static function updateUser($userData)
    {
        self::$accessToken = Auth::token();
        $url = self::$url . '/user.update.json';
        $result = self::sendRequest($url, 'POST', $userData);
        if ($result === false) {
            return 'false';
        }
        return json_decode($result, true);
    }
}
