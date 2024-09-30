<?

namespace MCRM;

use Exception;

class Auth
{
    private static $webhookUrl = 'https://osmidisk.marketingcrm.online/user/auth';
    private static $apiKey = '*****';

    public static function auth($phone, $password)
    {
        $requestData = array(
            // 'phone' => $phone,
            // 'password' => $password
        );

        $requestDataJson = json_encode($requestData);

        $options = array(
            'http' => array(
                'method'  => 'POST',
                'ignore_errors' => true,
                'header'  => "Content-Type: application/json\r\n" .
                    "x-api-key: " . self::$apiKey . "\r\n" .
                    "Content-Length: " . strlen($requestDataJson) . "\r\n",
                'content' => $requestDataJson
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents(self::$webhookUrl . '?api_key=' . self::$apiKey, false, $context);

        return $result;
    }
    public static function token()
    {
    }
}
