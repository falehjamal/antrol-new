<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $table = 'api_request_logs';

    public $timestamps = false;

    protected $fillable = [
        'method',
        'endpoint',
        'full_url',
        'client_ip',
        'user_agent',
        'client_username',
        'request_headers',
        'request_body',
        'response_status',
        'response_code',
        'response_body',
        'duration_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
