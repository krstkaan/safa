<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    /**
     * @OA\Get(
     *     path="/books",
     *     summary="Tüm kitapları listeleme",
     *     description="Sistemdeki tüm kitapları getirir. Pagination, arama ve filtreleme destekler.",
     *     tags={"Books"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Sayfa numarası (varsayılan: 1)",
     *         @OA\Schema(type="integer", example=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Sayfa başına kayıt sayısı (varsayılan: 10, maksimum: 100)",
     *         @OA\Schema(type="integer", example=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sıralama alanı (varsayılan: created_at)",
     *         @OA\Schema(type="string", enum={"id", "name", "author_id", "publisher_id", "created_at", "updated_at"}, example="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         required=false,
     *         description="Sıralama yönü (varsayılan: desc)",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="desc")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Kitap adında arama yapmak için kullanılır (case-insensitive)",
     *         @OA\Schema(type="string", example="harry potter")
     *     ),
     *     @OA\Parameter(
     *         name="author_id",
     *         in="query",
     *         required=false,
     *         description="Yazara göre filtreleme",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="publisher_id",
     *         in="query",
     *         required=false,
     *         description="Yayınevine göre filtreleme",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         required=false,
     *         description="Seviyeye göre filtreleme",
     *         @OA\Schema(type="string", enum={"ilkokul", "ortaokul", "ortak"}, example="ilkokul")
     *     ),
     *     @OA\Parameter(
     *         name="is_donation",
     *         in="query",
     *         required=false,
     *         description="Bağış durumuna göre filtreleme",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="with_relations",
     *         in="query",
     *         required=false,
     *         description="İlişkili verileri dahil et (author,publisher)",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Book")),
     *             @OA\Property(property="message", type="string", example="Kitaplar başarıyla listelendi."),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="total_pages", type="integer", example=5),
     *                 @OA\Property(property="has_next_page", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Yetkisiz erişim",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     * 
     * Tüm kitapları listeleme
     */
    public function index(Request $request): JsonResponse
    {
        // Pagination parametrelerini al ve validate et
        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 10)));
        
        // Sıralama parametrelerini al ve validate et
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Arama ve filtreleme parametrelerini al
        $name = $request->get('name');
        $authorId = $request->get('author_id');
        $publisherId = $request->get('publisher_id');
        $level = $request->get('level');
        $isDonation = $request->get('is_donation');
        $withRelations = $request->boolean('with_relations', false);
        
        // Geçerli sıralama alanlarını kontrol et
        $allowedSortFields = ['id', 'name', 'author_id', 'publisher_id', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        // Geçerli sıralama yönlerini kontrol et
        $allowedSortDirections = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedSortDirections)) {
            $sortDirection = 'desc';
        }
        
        // Query builder'ı oluştur
        $query = Book::query();
        
        // İlişkili verileri dahil et
        if ($withRelations) {
            $query->with(['author', 'publisher']);
        }
        
        // Arama varsa filtrele
        if (!empty($name)) {
            $query->where('name', 'ILIKE', '%' . $name . '%');
        }
        
        // Yazar filtrelemesi
        if (!empty($authorId)) {
            $query->where('author_id', $authorId);
        }
        
        // Yayınevi filtrelemesi
        if (!empty($publisherId)) {
            $query->where('publisher_id', $publisherId);
        }
        
        // Seviye filtrelemesi
        if (!empty($level)) {
            $allowedLevels = ['ilkokul', 'ortaokul', 'ortak'];
            if (in_array($level, $allowedLevels)) {
                $query->where('level', $level);
            }
        }
        
        // Bağış durumu filtrelemesi
        if ($isDonation !== null) {
            $query->where('is_donation', $isDonation);
        }
        
        // Toplam kayıt sayısını al (filtreleme sonrası)
        $total = $query->count();
        
        // Toplam sayfa sayısını hesapla
        $totalPages = ceil($total / $limit);
        
        // Offset hesapla
        $offset = ($page - 1) * $limit;
        
        // Kitapları getir
        $books = $query->orderBy($sortBy, $sortDirection)
            ->offset($offset)
            ->limit($limit)
            ->get();
        
        // Sonraki sayfa var mı kontrolü
        $hasNextPage = $page < $totalPages;
        
        $message = !empty($name) 
            ? "Kitaplar arama sonuçları başarıyla listelendi." 
            : "Kitaplar başarıyla listelendi.";
        
        return response()->json([
            'status' => 'success',
            'data' => $books,
            'message' => $message,
            'name_term' => $name,
            'filters' => [
                'author_id' => $authorId,
                'publisher_id' => $publisherId,
                'level' => $level,
                'is_donation' => $isDonation,
                'with_relations' => $withRelations
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next_page' => $hasNextPage
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/books/{id}",
     *     summary="Tekil kitap gösterme",
     *     description="Belirtilen ID'ye sahip kitabı getirir",
     *     tags={"Books"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Kitap ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="with_relations",
     *         in="query",
     *         required=false,
     *         description="İlişkili verileri dahil et (author,publisher)",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Book"),
     *             @OA\Property(property="message", type="string", example="Kitap başarıyla getirildi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Book].")
     *         )
     *     )
     * )
     * 
     * Tekil kitap gösterme
     */
    public function show(Request $request, Book $book): JsonResponse
    {
        $withRelations = $request->boolean('with_relations', false);
        
        if ($withRelations) {
            $book->load(['author', 'publisher']);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $book,
            'message' => 'Kitap başarıyla getirildi.'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/books",
     *     summary="Yeni kitap oluşturma",
     *     description="Yeni bir kitap kaydı oluşturur",
     *     tags={"Books"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "author_id", "publisher_id"},
     *             @OA\Property(property="name", type="string", example="Harry Potter ve Felsefe Taşı"),
     *             @OA\Property(property="type", type="string", example="Roman"),
     *             @OA\Property(property="language", type="string", example="Türkçe"),
     *             @OA\Property(property="page_count", type="integer", example=320),
     *             @OA\Property(property="is_donation", type="boolean", example=false),
     *             @OA\Property(property="barcode", type="string", example="9781234567890", description="Kitap barkodu (aynı kitaptan birden fazla kopya olabilir)"),
     *             @OA\Property(property="shelf_code", type="string", example="A1-B2"),
     *             @OA\Property(property="fixture_no", type="string", example="FIX001", description="Benzersiz demirbaş numarası"),
     *             @OA\Property(property="author_id", type="integer", example=1),
     *             @OA\Property(property="publisher_id", type="integer", example=1),
     *             @OA\Property(property="level", type="string", enum={"ilkokul", "ortaokul", "ortak"}, example="ilkokul")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Başarıyla oluşturuldu",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Book"),
     *             @OA\Property(property="message", type="string", example="Kitap başarıyla oluşturuldu.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Doğrulama hatası",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Doğrulama hatası."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     * 
     * Yeni kitap oluşturma
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'language' => 'nullable|string|max:255',
                'page_count' => 'nullable|integer|min:1',
                'is_donation' => 'boolean',
                'barcode' => 'nullable|string|max:255',
                'shelf_code' => 'nullable|string|max:255',
                'fixture_no' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('books', 'fixture_no')->whereNull('deleted_at')
                ],
                'author_id' => 'required|exists:authors,id',
                'publisher_id' => 'required|exists:publishers,id',
                'level' => 'required|in:ilkokul,ortaokul,ortak'
            ]);

            $book = Book::create($validated);

            // İlişkili verileri yükle
            $book->load(['author', 'publisher']);

            return response()->json([
                'status' => 'success',
                'data' => $book,
                'message' => 'Kitap başarıyla oluşturuldu.'
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/books/{id}",
     *     summary="Kitap güncelleme",
     *     description="Mevcut kitabın bilgilerini günceller",
     *     tags={"Books"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Kitap ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "author_id", "publisher_id"},
     *             @OA\Property(property="name", type="string", example="Harry Potter ve Sırlar Odası"),
     *             @OA\Property(property="type", type="string", example="Roman"),
     *             @OA\Property(property="language", type="string", example="Türkçe"),
     *             @OA\Property(property="page_count", type="integer", example=350),
     *             @OA\Property(property="is_donation", type="boolean", example=true),
     *             @OA\Property(property="barcode", type="string", example="9781234567891", description="Kitap barkodu (aynı kitaptan birden fazla kopya olabilir)"),
     *             @OA\Property(property="shelf_code", type="string", example="A1-B3"),
     *             @OA\Property(property="fixture_no", type="string", example="FIX002", description="Benzersiz demirbaş numarası"),
     *             @OA\Property(property="author_id", type="integer", example=1),
     *             @OA\Property(property="publisher_id", type="integer", example=1),
     *             @OA\Property(property="level", type="string", enum={"ilkokul", "ortaokul", "ortak"}, example="ortaokul")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla güncellendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Book"),
     *             @OA\Property(property="message", type="string", example="Kitap başarıyla güncellendi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Book].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Doğrulama hatası",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Doğrulama hatası."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     * 
     * Kitap güncelleme
     */
    public function update(Request $request, Book $book): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'language' => 'nullable|string|max:255',
                'page_count' => 'nullable|integer|min:1',
                'is_donation' => 'boolean',
                'barcode' => 'nullable|string|max:255',
                'shelf_code' => 'nullable|string|max:255',
                'fixture_no' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('books', 'fixture_no')->whereNull('deleted_at')->ignore($book->id)
                ],
                'author_id' => 'required|exists:authors,id',
                'publisher_id' => 'required|exists:publishers,id',
                'level' => 'required|in:ilkokul,ortaokul,ortak'
            ]);

            $book->update($validated);

            // İlişkili verileri yükle
            $book->load(['author', 'publisher']);

            return response()->json([
                'status' => 'success',
                'data' => $book,
                'message' => 'Kitap başarıyla güncellendi.'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/books/{id}",
     *     summary="Kitap silme (Soft Delete)",
     *     description="Belirtilen kitabı sistemden soft delete yapar. Kayıt fiziksel olarak silinmez, sadece deleted_at alanı güncellenir.",
     *     tags={"Books"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Kitap ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Kitap başarıyla silindi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Book].")
     *         )
     *     )
     * )
     * 
     * Kitap silme (Soft Delete)
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kitap başarıyla silindi.'
        ]);
    }
}