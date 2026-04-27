<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;

class Presensi extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

    public function index()
    {
        $response = $this->client->get('presensi');
        
        $presensiData = is_array($response) ? $response : [];

        // In original CI4 this view expects variables
        return view('data_presensi', [
            'presensi' => $presensiData,
        ]);
    }

    public function cetak()
    {
        $response = $this->client->get('presensi');
        
        $presensiData = is_array($response) ? $response : [];

        return view('cetak_presensi', [
            'presensi' => $presensiData,
        ]);
    }
}
