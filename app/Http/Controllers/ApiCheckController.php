<?php

namespace App\Http\Controllers;
//use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ApiCheckController extends Controller
{
    public function __construct() {
        $apiJson = require app_path().'\material\ApiList.php';
        $this->data = array();
        $this->result = array('data' => &$this->data, 'status' => false);
        $apiTemp = json_decode($apiJson, true);
        if (is_null($apiTemp)) {
            throw   Exception('json格式错误');
        }
        $this->apilist = $apiTemp['api'];
        $this->domain = $apiTemp['domain'];
    }

    public function apiList() {
        $this->data = $this->apilist;
        return Response::json($this->result, 200);
    }

    public function runAll() {
        foreach ($this->apilist as $value) {
            $this->api_exec($value, 0);
        }
        return Response::json($this->result, 200);
    }

    public function runOne($key) {
        if (is_numeric($key)) {
            if (isset($this->apilist[$key])) {
                $api = $this->apilist[$key];
                $this->api_exec($api, 1);
            }
        }
        return Response::json($this->result, 200);
    }

    private function api_exec($api, $type = 0) {
        $data = array();
        $success = false;
        $method = $api['method'];
        $name = $api['name'];
        $route = $api['route'];
        $url = $this->domain.$api['route'];
        $args = isset($api['args']) ? $api['args'] : array();
        $params = compact('method', 'url', 'args');
        $curl_rs = $this->p_curl($params);
        $curl_data = json_decode($curl_rs['data'], true);
        if ($curl_rs['http_code'] == 200 && $curl_data !== null) {
            $success = true;
        }
        if ($success == false) {
            $data = $curl_rs['data'];
        }
        if ($method == 'post') {
            $data = $curl_rs['data'];
        }
        if ($type == 0) {
            $this->data[] = compact('url', 'name', 'success', 'method', 'data');
        } else {
            $this->data = compact('url', 'name', 'success', 'method', 'data');
        }

        $this->result['status'] = true;
    }

    private function p_curl($params) {
        $url = $params['url'];
        $method = $params['method'];
        $args = $params['args'];


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        }
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);


        $result = compact('data', 'http_code');
        return $result;
    }
}