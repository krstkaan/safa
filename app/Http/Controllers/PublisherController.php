<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;


class PublisherController extends Controller
{
    /**
     * @OA\Get(
     *     path="/publishers",
     *     summary="Tüm yayınevlerini listeleme",
     *     description="Sistemdeki tüm yayınevlerini getirir. Pagination ve arama destekler.",
     *     tags={"Publishers"},
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
     *         @OA\Schema(type="string", example="penguin")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Publisher")),
     *             @OA\Property(property="message", type="string", example="Yayınevleri başarıyla listelendi."),
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
     * Tüm yayınevlerini listeleme
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
        $query = Publisher::query();
        
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
        
        // Yayınevlerini getir
        $publishers = $query->orderBy($sortBy, $sortDirection)
            ->offset($offset)
            ->limit($limit)
            ->get();
        
        // Sonraki sayfa var mı kontrolü
        $hasNextPage = $page < $totalPages;
        
        $message = !empty($search) 
            ? "Yayınevleri arama sonuçları başarıyla listelendi." 
            : "Yayınevleri başarıyla listelendi.";
        
        return response()->json([
            'status' => 'success',
            'data' => $publishers,
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
     *     path="/publishers/{id}",
     *     summary="Tekil yayınevi gösterme",
     *     description="Belirtilen ID'ye sahip yayınevini getirir",
     *     tags={"Publishers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Yayınevi ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Publisher"),
     *             @OA\Property(property="message", type="string", example="Yayınevi başarıyla getirildi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Publisher].")
     *         )
     *     )
     * )
     * 
     * Tekil yayınevi gösterme
     */
    public function show(Publisher $publisher): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $publisher,
            'message' => 'Yayınevi başarıyla getirildi.'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/publishers",
     *     summary="Yeni yayınevi oluşturma",
     *     description="Yeni bir yayınevi kaydı oluşturur",
     *     tags={"Publishers"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Penguin Random House")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Başarıyla oluşturuldu",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Publisher"),
     *             @OA\Property(property="message", type="string", example="Yayınevi başarıyla oluşturuldu.")
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
     * Yeni yayınevi oluşturma
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $publisher = Publisher::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $publisher,
                'message' => 'Yayınevi başarıyla oluşturuldu.'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/publishers/{id}",
     *     summary="Yayınevi güncelleme",
     *     description="Mevcut yayınevinin bilgilerini günceller",
     *     tags={"Publishers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Yayınevi ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="HarperCollins Publishers")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla güncellendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Publisher"),
     *             @OA\Property(property="message", type="string", example="Yayınevi başarıyla güncellendi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Publisher].")
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
     * Yayınevi güncelleme
     */
    public function update(Request $request, Publisher $publisher): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $publisher->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $publisher,
                'message' => 'Yayınevi başarıyla güncellendi.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/publishers/{id}",
     *     summary="Yayınevi silme (Soft Delete)",
     *     description="Belirtilen yayınevini sistemden soft delete yapar. Kayıt fiziksel olarak silinmez, sadece deleted_at alanı güncellenir.",
     *     tags={"Publishers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Yayınevi ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Yayınevi başarıyla silindi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Publisher].")
     *         )
     *     )
     * )
     * 
     * Yayınevi silme (Soft Delete)
     */
    public function destroy(Publisher $publisher): JsonResponse
    {
        $publisher->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Yayınevi başarıyla silindi.'
        ]);
    }
}