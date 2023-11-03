<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class qandaUseKeyWord extends Model{


    public function qanda_key_word_records(){
        return $this->belongsTo(qandaKeyWordRecord::class, 'tag_id');
    }
    public function key_words_use_qanda(){
        return $this->hasMany(qandaKeyWordRecord::class, 'id');

    }


}
