<?php

namespace App\Service;

use GuzzleHttp\Client;

class ApiService
{

    public function sendVerificationCode($options)
    {
        $client = new Client();
        $response = $client->post(getenv('TWILIO_AUTHY_BASE_URL').'start', $options);
        $res_json = json_decode($response->getBody()->getContents());
        return $res_json;
    }
    
    public function verifyCodeAndSaveUser($options)
    {
        $client = new Client();
        $response = $client->get(getenv('TWILIO_AUTHY_BASE_URL').'check', $options);
        $res_json = json_decode($response->getBody()->getContents());
        return $res_json;
    }
}
