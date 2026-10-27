<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $service
 * @property string|null $request_body
 * @property int $response_code
 * @property string|null $response_body
 * @property string $ip_address
 * @property Carbon|null $created_at
 */
#[Table('api_request_logs')]
#[Fillable(['user_id', 'service', 'request_body', 'response_code', 'response_body', 'ip_address', 'created_at'])]
#[WithoutTimestamps]
class RequestLogModel extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
