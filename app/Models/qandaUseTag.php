<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class qandaUseTag extends Model{

    public function qanda_tag_records(){
        return $this->belongsTo(qandaTagRecord::class, 'tag_id');
    }
    public function tags_use_qanda(){
        return $this->hasMany(qandaTagRecord::class, 'id');

    }


}
