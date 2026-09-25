<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionEligibility extends Model
{
    protected $table = 'session_eligibility';

    protected $fillable = ['session_id', 'user_id', 'group_id'];
}