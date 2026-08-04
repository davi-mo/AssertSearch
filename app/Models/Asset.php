<?php

namespace App\Models;

use App\Observers\AssetObserver;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'name', 'description'])]
#[ObservedBy(AssetObserver::class)]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';
}
