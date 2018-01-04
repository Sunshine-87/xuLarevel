<?php

namespace App\Http\Controllers;

class ApiCheckController extends Controller
{
    public function __construct() {
        $apiJson = require app_path().'\material\ApiList.php';
        $apiTemp = json_decode($apiJson, true);
        $this->apilist = $apiTemp['api'];
        $this->domain = $apiTemp['domain'];
    }

    public function runAll() {
        foreach ($this->apilist as $value) {
            $method = $value['method'];
            $url = $this->domain.$value['route'];
            $params = compact('method', 'url');
            $result = $this->p_curl($params);
            print_r($result);
            exit;
        }
    }

    public function request() {

    }

    private function p_curl($params) {
        $url = $params['url'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $result = compact('data', 'http_code');
        return $result;
    }
}