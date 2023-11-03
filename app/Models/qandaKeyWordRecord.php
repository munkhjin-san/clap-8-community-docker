<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class qandaKeyWordRecord extends Model{

    public function qanda_use_key_words(){
        return $this->hasMany(qandaUseKeyWord::class, 'id');
    }
    public function key_words_use_qanda(){
        return $this->hasMany(qandaUseKeyWord::class, 'tag_id');

    }

}