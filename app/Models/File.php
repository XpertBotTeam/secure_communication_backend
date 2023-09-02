<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{

    use HasFactory;

    protected $primaryKey = 'FileID';

    protected $fillable = [
        'FileName',
        'FileSize',
        'FileType',
        'FileContent',
        'SenderID',
        'RecipientID',
    ];


    public function sender()
    {
        return $this->belongsTo(User::class, 'SenderID');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'RecipientID');
    }

}
