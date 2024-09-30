<?php

namespace CoreAmo;

use Logs\Logs;

use \Exception;

/**
$method = '/api/v4/leads/' . $lead_id;
$data = [
    'id' => $lead_id,
    'status_id' => 46017067,
    'responsible_user_id' => $amo_responsible_user_id
];
$res = CoreAmo::post_or_patch ($data, $method, 'PATCH');

$method = '/api/v4/leads';
$data = [
    [
        'name' => $site,
        'status_id' => 46017064,
        'responsible_user_id' => $callback_code - 100000000000000,
    ]
];
$res = CoreAmo::post_or_patch ($data, $method, 'POST');

$api_url = '/api/v4/users';
$amo_users = CoreAmo::get($api_url, $fullUrl = true/false);
 **/
date_default_timezone_set("Europe/Moscow");

class CoreAmo
{
    private static $client_id = '****';
    private static $client_secret = '****';
    private static $oauth_token = '******';
    private static $redirect_uri = 'https://test.php24.ru/l***/';
    private static $subdomain = '****';

    // auth
    public static function auth()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/tokens.json'), 1);
        if (time() - (int) $data['time'] > 82800) {
            $link = 'https://' . self::$subdomain . '.amocrm.ru/oauth2/access_token';
            $refresh_data = [
                'client_id' => self::$client_id,
                'client_secret' => self::$client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $data['refresh_token'],
                'redirect_uri' => self::$redirect_uri,
            ];
            $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
            curl_setopt($curl, CURLOPT_URL, $link);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($refresh_data));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $code = (int)$code;
            $errors = [
                400 => 'Bad request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not found',
                405 => 'Запрашиваемый HTTP-метод не поддерживается',
                500 => 'Internal server error',
                502 => 'Bad gateway',
                503 => 'Service unavailable',
            ];
            try {
                if ($code < 200 || $code > 204) {
                    throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
                }
            } catch (\Exception $e) {
                Logs::log('Ошибка аутентификации: ' . json_encode($out, JSON_UNESCAPED_UNICODE) . "\n");
                die('Ошибка аутентификации: ' . json_encode($out, JSON_UNESCAPED_UNICODE) . "\n");
                //die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
            }
            $response = json_decode($out, true);
            $response['time'] = time();
            file_put_contents(__DIR__ . '/tokens.json', json_encode($response));
            $data['access_token'] = $response['access_token'];
        }
        return $data;
    }
    // auth

    public static function get($url, $fullUrl = false)
    {
        $data = self::auth();
        if ($fullUrl) {
            $link = $url;
        } else {
            $link = 'https://' . self::$subdomain . '.amocrm.ru' . $url;
        }
        $access_token = $data['access_token'];
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        // echo print_r($headers).'<br><br>';
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        //echo 'GET ' . $out . "\n";
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            405 => 'Запрашиваемый HTTP-метод не поддерживается',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];
        try {
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch (\Exception $e) {
            Logs::log('Ошибка GET: ' . json_encode($out, JSON_UNESCAPED_UNICODE) . "\n");
            die('Ошибка GET: ' . json_encode($out, JSON_UNESCAPED_UNICODE) . "\n");
            //die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }
        $result = json_decode($out, true);
        return $result;
    }

    public static function post_or_patch($query_data, $url, $method)
    {
        // echo 'POST:<br>';
        // echo $url.'<br>';
        $data = self::auth();
        $link = 'https://' . self::$subdomain . '.amocrm.ru' . $url;
        $access_token = $data['access_token'];
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ];
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($query_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        echo $code . '<br>';
        // echo 'POST' . $out . "\n";
        curl_close($curl);
        $code = (int)$code;
        $errors = array(
            301 => 'Moved permanently',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            405 => 'Запрашиваемый HTTP-метод не поддерживается',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        );
        try {
            if ($code != 200 && $code != 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
            }
        } catch (Exception $E) {
            Logs::log('Ошибка POST: ' . json_encode($out, JSON_UNESCAPED_UNICODE) . $errors[$code] . "\n");
            die('Ошибка POST: ' . json_encode($out, JSON_UNESCAPED_UNICODE) . $errors[$code] . "\n");
            //die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode());
        }
        $result = json_decode($out, true);
        return $result;
    }

    public static function getFilterLeads($statuses)
    {
        $count_response = 0;
        $offset = 0;
        $all_leads = array();
        while (($count_response % 500) === 0) {
            $query_params = array(
                'status' => $statuses,
                'limit_rows' => 500,
                'limit_offset' => $offset
            );
            $LEADS = self::get(self::$subdomain, '/api/v2/leads?' . http_build_query($query_params));
            if (isset($LEADS['_embedded']['items'][0])) {
                $LEADS = $LEADS['_embedded']['items'];
                $count_response = count($LEADS);
                $offset += 500;
                $all_leads = array_merge($all_leads, $LEADS);
                sleep(1);
            }
        }
        return $all_leads;
    }

    public static function token()
    {
        $link = 'https://' . self::$subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

        /** Соберем данные для запроса */
        $data = [
            'client_id' => self::$client_id,
            'client_secret' => self::$client_secret,
            'grant_type' => 'authorization_code',
            'code' => self::$oauth_token,
            'redirect_uri' => self::$redirect_uri,
        ];

        /**
         * Нам необходимо инициировать запрос к серверу.
         * Воспользуемся библиотекой cURL (поставляется в составе PHP).
         * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
         */
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        //var_dump($out);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            405 => 'Запрашиваемый HTTP-метод не поддерживается',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch (\Exception $e) {
            Logs::log('Ошибка получения токена: ' . json_encode($out, JSON_UNESCAPED_UNICODE) . "\n");
            die('Ошибка получения токена: ' . json_encode($out, JSON_UNESCAPED_UNICODE) . $errors[$code] . "\n");
            //die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
         */
        $response = json_decode($out, true);
        $response['time'] = time();

        file_put_contents(__DIR__ . '/tokens.json', json_encode($response));
    }
}
