<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryExisting extends Model
{
    // use SoftDeletes;

    protected $connection;

    public $table = 'categories';

    public $timestamps = false;
    

    // protected $dates = ['deleted_at'];


    public $fillable = [
        'name'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        //
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        //
    ];

    function __construct() {
        parent::__construct();
        $this->connection = env('CDB_DB_CONNECTION');
    }

    public function getTableColumns() {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    
}
