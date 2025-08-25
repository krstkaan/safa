<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *   schema="Publisher",
 *   type="object",
 *   required={"name"},
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(property="name", type="string", maxLength=255),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
 * )
 */
class Publisher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}

