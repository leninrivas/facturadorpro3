<?php

namespace App\Models\Tenant;
 

class Cash extends ModelTenant
{
    protected $with = ['cash_documents'];

    protected $table = 'cash';

    protected $fillable = [
        'user_id',
        'date_opening',
        'time_opening',
        'date_closed',
        'time_closed',
        'beginning_balance',
        'final_balance',
        'income', 
        'state', 
 
    ];
 

  
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cash_documents()
    {
        return $this->hasMany(CashDocument::class);
    }
 
 
}