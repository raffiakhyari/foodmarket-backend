<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'food_id', 'user_id', 'quantity',
        'status', 'payment_url'
    ];

    public function food(){
        return $this->hashOne(food::class, 'id', 'food_id'); //local key // foreign key
    }
    public function user(){
        return $this->hashOne(user::class, 'id', 'user_id'); //local key // foreign key
    }

    public function getCreatedAtAttribute($value){
        
        return Carbon::parse($value)-> timestamp;
    }

    public function getUpdatedAtAttribute($value){
        return Carbon::parse($value)-> timestamp;
    }

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['picturePath'] = $this -> picturePath;
        return $toArray;
    }

    // public function getPicturePathAttribute(){
    //     return url(''). Storage::url($this->attributes['picturePath']);
    // }

}
