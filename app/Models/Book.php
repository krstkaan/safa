<?php

namespace App\Models;

use App\Models\Author;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *   schema="Book",
 *   title="Book",
 *   description="Kitap kaydı. Kitapların seviyesi (İlkokul, Ortaokul, Ortak) bulunur.",
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(property="name", type="string", example="Kitap Adı"),
 *   @OA\Property(property="type", type="string", description="Kitabın türü (roman, hikaye vb.)", example="Roman"),
 *   @OA\Property(property="language", type="string", description="Kitabın dili", example="Türkçe"),
 *   @OA\Property(property="page_count", type="integer", description="Sayfa sayısı", example=150),
 *   @OA\Property(property="barcode", type="string", description="Barkod numarası (aynı kitaptan birden fazla kopya olabilir)", example="9786053435345"),
 *   @OA\Property(property="shelf_code", type="string", description="Raf konumu", example="R12-S3"),
 *   @OA\Property(property="fixture_no", type="string", description="Benzersiz demirbaş numarası", example="FIX001"),
 *   @OA\Property(
 *      property="level",
 *      type="string",
 *      enum={"ilkokul", "ortaokul", "ortak"},
 *      description="Kitabın seviyesi",
 *      example="ilkokul"
 *   ),
 *   @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="updated_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class Book extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'barcode',
        'author_id',
        'publisher_id',
        'language',
        'page_count',
        'is_donation',
        'shelf_code',
        'fixture_no',
        'level',
    ];

    /**
     * Bu kitabın yazarını alır.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * Bu kitabın yayınevini alır.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }
}