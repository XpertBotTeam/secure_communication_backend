<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $primaryKey = 'MessageID';

    public function sender()
    {
        return $this->belongsTo(User::class, 'SenderID');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'RecipientID');
    }


}
