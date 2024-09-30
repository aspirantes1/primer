<?

use CoreAmo\CoreAmo;
use Logs\Logs;
use MCRM\Auth;
use MCRM\User;
use ChangeStatusGdeslon\CheckField;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Expose-Headers", "Access-Control-*");
header("Access-Control-Allow-Headers", "Access-Control-*, Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, HEAD');
header('Allow', 'GET, POST, PUT, DELETE, OPTIONS, HEAD');
date_default_timezone_set("Europe/Moscow");

// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 'on');
ini_set('error_log', __DIR__ . '/php_error.log');

include 'autoload.php';

Logs::log(json_encode($_REQUEST), 'request.log');

if (isset($_REQUEST['leads']['status'][0]['id'])) {
    $data = CheckField::loadLead($_REQUEST['leads']['status'][0]['id']);
}

if (empty($data)) {
    Logs::log('Пустой ответ от MCRM - ' . json_encode($_REQUEST, JSON_UNESCAPED_UNICODE), 'amo.log');
    die;
}

Logs::log(json_encode($data, JSON_UNESCAPED_UNICODE), 'amo.log');

// Получаем токен
// $response = Auth::auth('9230000000', '123');
// var_dump($response);


// Выбор пользователя
$userData = User::getUser($data['contact']['phone']);
if (empty($userData['check_count']))
    $userData['check_count'] = 0;
$count = $data['count'] - $userData['check_count'];
Logs::log(json_encode($userData, JSON_UNESCAPED_UNICODE), 'user.log');

if ($userData['status'] === 'success') {
    //Обновляем пользователя
    $userData = [
        'phone' => $data['contact']['phone'],
    ];
    $response = User::updateUser($userData);
    addOrder($data, $count);
    Logs::log('1 - ' . json_encode(json_decode($response, 1), JSON_UNESCAPED_UNICODE), 'userGet.log');
} else {
    // Регистрация нового клиента
    if (empty($data['contact']['last_name']) || $data['contact']['last_name'] === '')
        $data['contact']['last_name'] = 'noLastName';
    if (empty($data['contact']['gender']) || $data['contact']['gender'] === '')
        $data['contact']['gender'] = 'male';
    if (empty($data['contact']['email']) || $data['contact']['email'] === '')
        $data['contact']['email'] = 'noEmail';
    $dataRegUser = array(
        'card_number' => $data['contact']['id'],
        'phone' => $data['contact']['phone'],
        'first_name' => $data['contact']['first_name'],
        'last_name' => $data['contact']['last_name'] ?? '',
        'email' => $data['contact']['email'] ?? '',
        // 'father_name' => $data['count'],
        'gender' => $data['contact']['gender'] ?? '',
        'district_home' => '1',
        'district_work' => '1',
        'birth_date' => $data['contact']['birthday'] ?? date('d.m.Y', 1577818800),
    );
    $response = User::regUser($dataRegUser);
    addOrder($data, $count);
    Logs::log('2 - ' . json_encode(json_decode($response, 1), JSON_UNESCAPED_UNICODE), 'userGet.log');
}

function addOrder($data, $count)
{
    Logs::log(json_encode($count), 'order.log');
    $dataOrder = [
        'number' => $data['contact']['phone'],
        'order_sum' => 100,
        'order_pay_bonus' => 0,
        'order_pay_money' => 100,
    ];
    for ($i = 0; $i < $count; $i++) {
        $response2 = User::addOrder($dataOrder);
        Logs::log(json_encode(json_decode($response2, 1), JSON_UNESCAPED_UNICODE), 'order.log');
    }
}
