<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/authors",
     *     summary="Tüm yazarları listeleme",
     *     description="Sistemdeki tüm yazarları getirir. Pagination ve arama destekler.",
     *     tags={"Authors"},
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
     *         @OA\Schema(type="string", enum={"id", "name", "created_at", "updated_at"}, example="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         required=false,
     *         description="Sıralama yönü (varsayılan: desc)",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="desc")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="İsim alanında arama yapmak için kullanılır (case-insensitive)",
     *         @OA\Schema(type="string", example="orhan pamuk")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Author")),
     *             @OA\Property(property="message", type="string", example="Yazarlar başarıyla listelendi."),
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
     * Tüm yazarları listeleme
     */
    public function index(Request $request): JsonResponse
    {
        // Pagination parametrelerini al ve validate et
        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 10)));
        
        // Sıralama parametrelerini al ve validate et
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Arama parametresini al
        $search = $request->get('search');
        
        // Geçerli sıralama alanlarını kontrol et
        $allowedSortFields = ['id', 'name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        // Geçerli sıralama yönlerini kontrol et
        $allowedSortDirections = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedSortDirections)) {
            $sortDirection = 'desc';
        }
        
        // Query builder'ı oluştur
        $query = Author::query();
        
        // Arama varsa filtrele
        if (!empty($search)) {
            $query->where('name', 'ILIKE', '%' . $search . '%');
        }
        
        // Toplam kayıt sayısını al (filtreleme sonrası)
        $total = $query->count();
        
        // Toplam sayfa sayısını hesapla
        $totalPages = ceil($total / $limit);
        
        // Offset hesapla
        $offset = ($page - 1) * $limit;
        
        // Yazarları getir
        $authors = $query->orderBy($sortBy, $sortDirection)
            ->offset($offset)
            ->limit($limit)
            ->get();
        
        // Sonraki sayfa var mı kontrolü
        $hasNextPage = $page < $totalPages;
        
        $message = !empty($search) 
            ? "Yazarlar arama sonuçları başarıyla listelendi." 
            : "Yazarlar başarıyla listelendi.";
        
        return response()->json([
            'status' => 'success',
            'data' => $authors,
            'message' => $message,
            'search_term' => $search,
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
     *     path="/authors/{id}",
     *     summary="Tekil yazar gösterme",
     *     description="Belirtilen ID'ye sahip yazarı getirir",
     *     tags={"Authors"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Yazar ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Author"),
     *             @OA\Property(property="message", type="string", example="Yazar başarıyla getirildi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Author].")
     *         )
     *     )
     * )
     * 
     * Tekil yazar gösterme
     */
    public function show(Author $author): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $author,
            'message' => 'Yazar başarıyla getirildi.'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/authors",
     *     summary="Yeni yazar oluşturma",
     *     description="Yeni bir yazar kaydı oluşturur",
     *     tags={"Authors"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Orhan Pamuk")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Başarıyla oluşturuldu",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Author"),
     *             @OA\Property(property="message", type="string", example="Yazar başarıyla oluşturuldu.")
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
     * Yeni yazar oluşturma
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $author = Author::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $author,
                'message' => 'Yazar başarıyla oluşturuldu.'
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
     *     path="/authors/{id}",
     *     summary="Yazar güncelleme",
     *     description="Mevcut yazarın bilgilerini günceller",
     *     tags={"Authors"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Yazar ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Nazım Hikmet")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla güncellendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Author"),
     *             @OA\Property(property="message", type="string", example="Yazar başarıyla güncellendi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Author].")
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
     * Yazar güncelleme
     */
    public function update(Request $request, Author $author): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $author->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $author,
                'message' => 'Yazar başarıyla güncellendi.'
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
     *     path="/authors/{id}",
     *     summary="Yazar silme (Soft Delete)",
     *     description="Belirtilen yazarı sistemden soft delete yapar. Kayıt fiziksel olarak silinmez, sadece deleted_at alanı güncellenir.",
     *     tags={"Authors"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Yazar ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Yazar başarıyla silindi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Author].")
     *         )
     *     )
     * )
     * 
     * Yazar silme (Soft Delete)
     */
    public function destroy(Author $author): JsonResponse
    {
        $author->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Yazar başarıyla silindi.'
        ]);
    }
}