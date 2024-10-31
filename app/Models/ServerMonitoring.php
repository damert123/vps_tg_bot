<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ServerMonitoring extends Model
{
    protected $guarded = false;

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function getLastUpdateAttribute ($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }

}
