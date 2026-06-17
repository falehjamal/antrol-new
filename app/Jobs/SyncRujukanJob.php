<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class SyncRujukanJob extends Job
{
    protected $id_online;

    public function __construct($id_online)
    {
        $this->id_online = $id_online;
    }

    public function handle()
    {
        Log::info('START JOB SYNC RUJUKAN '.$this->id_online);

        $url = 'http://10.0.108.249:82/bpjs?id='.$this->id_online;
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->get($url);
            json_decode($response->getBody(), true);
            Log::info('OK');
        } catch (\Exception $e) {
            Log::error('SyncRujukanJob error: '.$e->getMessage());
        }
    }
}
