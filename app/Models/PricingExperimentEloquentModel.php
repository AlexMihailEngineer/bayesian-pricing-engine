<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingExperimentEloquentModel extends Model
{
	/**
	 * The table associated with the model.
	 * @var string
	 */
	protected $table = 'pricing_experiments';

	/**
	 * Use UUIDs instead of auto-incrementing integers for Domain integrity.
	 */
	public $incrementing = false;
	protected $keyType = 'string';

	protected $fillable = [
		'id',
		'product_id',
		'alpha',
		'beta',
		'price_point',
	];

	/**
	 * Ensure precision is maintained by treating these as strings 
	 * before they reach brick/math.
	 */
	protected $casts = [
		'alpha' => 'string',
		'beta' => 'string',
		'price_point' => 'string',
	];
}
