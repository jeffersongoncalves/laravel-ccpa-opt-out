<?php

namespace JeffersonGoncalves\CcpaOptOut\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use JeffersonGoncalves\CcpaOptOut\Concerns\HasOptOuts;

class User extends Authenticatable
{
    use HasOptOuts;

    protected $guarded = [];

    public $timestamps = false;
}
