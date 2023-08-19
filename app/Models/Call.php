<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasFactory;

    protected $primaryKey = 'CallID';

    public function caller()
    {
        return $this->belongsTo(User::class, 'SenderID');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'RecipientID');
    }


}
