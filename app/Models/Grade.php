<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *   schema="Grade",
 *   title="Grade",
 *   description="Sınıf seviyesi kaydı. 1'den 8'e kadar olan seviyeleri tutar.",
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(
 *      property="name",
 *      type="integer",
 *      description="Sınıf seviyesi (1-8 arası bir sayı)",
 *      example=5
 *   ),
 *   @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="updated_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class Grade extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Bu sütun artık 'integer' tipinde 1-8 arası bir sayı tutuyor.
    ];

    /**
     * Bu sınıf seviyesi ile ilişkili kitapları alır.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function books(): BelongsToMany
    {
        // 'book_grade' pivot tablosu üzerinden Book modeli ile Çoka-Çok ilişki kurulur.
        return $this->belongsToMany(Book::class, 'book_grade');
    }
}