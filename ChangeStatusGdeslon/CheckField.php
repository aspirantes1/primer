<?

namespace ChangeStatusGdeslon;

use CoreAmo\CoreAmo;
use Logs\Logs;

class CheckField
{
    private static $contact;
    private static $countLeads;
    public static function loadLead($id)
    {
        $method = "/api/v4/leads/$id";
        $data = [
            'with' => 'contacts'
        ];
        $amo = CoreAmo::get($method . '?' . http_build_query($data));
        if (isset($amo['_embedded']['contacts'][0]['id']))
            self::$contact = self::getContact($amo['_embedded']['contacts'][0]['id']);
        $amo['contact'] = self::$contact ?? '';
        $amo['count'] = self::$countLeads ?? 0;
        return $amo;
    }
    private static function getContact($id)
    {
        $method = "/api/v4/contacts/$id";
        $data = [
            'with' => 'leads'
        ];
        $amo = CoreAmo::get($method . '?' . http_build_query($data));
        if (isset($amo['_embedded']['leads'])) {
            self::$countLeads = self::checkLeads($amo['_embedded']['leads']);
            foreach ($amo['custom_fields_values'] as $key => $val) {
                if ($val['field_code'] === 'PHONE')
                    $amo['phone'] = $amo['custom_fields_values'][$key]['values'][0]['value'];
                if ($val['field_code'] === 'EMAIL')
                    $amo['email'] = $amo['custom_fields_values'][$key]['values'][0]['value'];
                if ($val['field_name'] === 'Пол')
                    $amo['gender'] = $amo['custom_fields_values'][$key]['values'][0]['value'];
                if ($val['field_name'] === 'День рождения')
                    $amo['birthday'] = date('d.m.Y', $amo['custom_fields_values'][$key]['values'][0]['value']);
            }
            unset($amo['custom_fields_values']);
        }
        return $amo;
    }
    private static function checkLeads($arr)
    {
        $countLeads = 0;
        foreach ($arr as $key => $val) {
            $id = $arr[$key]['id'];
            $method = "/api/v4/leads/$id";
            $data = [
                // 'with' => 'leads'
            ];
            $amo = CoreAmo::get($method . '?' . http_build_query($data));
            if (isset($amo['status_id']) && $amo['status_id'] === 142)
                $countLeads++;
            usleep(200000);
        }
        return $countLeads;
    }
}
