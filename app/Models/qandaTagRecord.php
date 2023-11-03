<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class qandaTagRecord extends Model{

    public function qanda_use_tags(){
        return $this->hasMany(qandaUseTag::class, 'id');
    }
    public function tags_use_qanda(){
        return $this->hasMany(qandaUseTag::class, 'tag_id');

    }

}