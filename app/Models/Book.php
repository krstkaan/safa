<?php

namespace App\Models;

use App\Models\Author;
use App\Models\Publisher;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *   schema="Book",
 *   title="Book",
 *   description="Kitap kaydı. Bir kitabın birden çok sınıf seviyesi olabilir.",
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(property="name", type="string", example="Kitap Adı"),
 *   @OA\Property(property="type", type="string", description="Kitabın türü (roman, hikaye vb.)", example="Roman"),
 *   @OA\Property(property="language", type="string", description="Kitabın dili", example="Türkçe"),
 *   @OA\Property(property="page_count", type="integer", description="Sayfa sayısı", example=150),
 *   @OA\Property(property="barcode", type="string", description="Barkod numarası", example="9786053435345"),
 *   @OA\Property(property="shelf_code", type="string", description="Raf konumu", example="R12-S3"),
 *   @OA\Property(
 *      property="grades",
 *      type="array",
 *      description="Kitabın uygun olduğu sınıf seviyeleri",
 *      @OA\Items(ref="#/components/schemas/Grade")
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
        // 'grade', // Bu satır kaldırıldı çünkü ilişki artık pivot tablo üzerinden yönetiliyor.
        'type',
        'language',
        'page_count',
        'is_donation',
        'barcode',
        'shelf_code',
        'fixture_no',
        'author_id', // Yazar ilişkisi Bire-Çok olarak kalıyor.
        'publisher_id',
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

    /**
     * Bu kitabın ait olduğu sınıf seviyelerini alır.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function grades(): BelongsToMany
    {
        // 'book_grade' pivot tablosu üzerinden Grade modeli ile Çoka-Çok ilişki kurulur.
        return $this->belongsToMany(Grade::class, 'book_grade');
    }
}