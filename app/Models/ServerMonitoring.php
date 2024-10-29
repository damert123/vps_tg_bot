<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerMonitoring extends Model
{
    protected $guarded = false;

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

}
