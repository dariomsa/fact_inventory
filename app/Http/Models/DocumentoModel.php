<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoModel extends Model {

    protected $table = 'documento';

    public function cliente(){
    	return $this->belongsTo('App\Http\Models\ClienteModel');
    }
    public function doc_sustento(){
		return $this->hasone('App\Http\Models\Doc_x_docsusModel','doc_id','id');
	}

}