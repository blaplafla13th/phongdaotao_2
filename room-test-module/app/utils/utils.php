<?php

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;

function get_user()
{
    $jwt = request()->bearerToken();
    $user = json_decode(Redis::get("Auth:$jwt"));
    return $user;
}

function e_api()
{
    return new Client(
        ['http_errors' => false,]
    );
}
