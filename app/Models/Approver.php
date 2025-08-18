<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="Approver",
 *     type="object",
 *     title="Onaylayan",
 *     description="Fotokopi isteğini onaylayan kişi modeli",
 *     @OA\Property(property="id", type="integer", example=1, description="Benzersiz ID"),
 *     @OA\Property(property="name", type="string", example="Mehmet Demir", description="Onaylayan kişinin adı"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-18T14:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-18T14:30:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", example="2025-08-18T14:30:00.000000Z", nullable=true)
 * )
 */
class Approver extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['name'];

    public function printRequests()
    {
        return $this->hasMany(PrintRequest::class);
    }
}
