<?php

namespace App\Models;

use CodeIgniter\Model;

class BusinessProfileModel extends Model
{
    protected $table          = 'business_profiles';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'business_id',
        'what_it_does',
        'customers_served',
        'products_offered',
        'objectives_summary',
        'differentiator',
        'differentiation_delivery',
        'customer_outcome',
        'purchase_reason',
        'acquisition_channels',
    ];
    protected $validationRules = [
        'business_id'       => 'required|is_natural_no_zero',
        'what_it_does'      => 'required',
        'customers_served'  => 'required',
        'products_offered'  => 'required',
        'objectives_summary' => 'required',
    ];
}
