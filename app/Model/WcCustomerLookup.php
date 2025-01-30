<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WcCustomerLookup extends Model
{
    protected $connection = 'mysql_wp';
    protected $table = 'wc_customer_lookup';
}
