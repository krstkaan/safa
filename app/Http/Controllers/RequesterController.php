<?php

namespace App\Http\Controllers;

use App\Models\Requester;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RequesterController extends Controller
{
    /**
     * @OA\Get(
     *     path="/requesters",
     *     summary="Tüm talep edenleri listeleme",
     *     description="Sistemdeki tüm talep edenleri getirir. Pagination ve arama destekler.",
     *     tags={"Requesters"},
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
     *         @OA\Schema(type="string", example="ahmet")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Requester")),
     *             @OA\Property(property="message", type="string", example="Talep edenler başarıyla listelendi."),
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
     * Tüm talep edenleri listeleme
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
        $query = Requester::query();
        
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
        
        // Talep edenleri getir
        $requesters = $query->orderBy($sortBy, $sortDirection)
            ->offset($offset)
            ->limit($limit)
            ->get();
        
        // Sonraki sayfa var mı kontrolü
        $hasNextPage = $page < $totalPages;
        
        $message = !empty($search) 
            ? "Talep edenler arama sonuçları başarıyla listelendi." 
            : "Talep edenler başarıyla listelendi.";
        
        return response()->json([
            'status' => 'success',
            'data' => $requesters,
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
     *     path="/requesters/{id}",
     *     summary="Tekil talep eden gösterme",
     *     description="Belirtilen ID'ye sahip talep edeni getirir",
     *     tags={"Requesters"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Talep eden ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Requester"),
     *             @OA\Property(property="message", type="string", example="Talep eden başarıyla getirildi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Requester].")
     *         )
     *     )
     * )
     * 
     * Tekil talep eden gösterme
     */
    public function show(Requester $requester): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $requester,
            'message' => 'Talep eden başarıyla getirildi.'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/requesters",
     *     summary="Yeni talep eden oluşturma",
     *     description="Yeni bir talep eden kaydı oluşturur",
     *     tags={"Requesters"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Ahmet Yılmaz")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Başarıyla oluşturuldu",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Requester"),
     *             @OA\Property(property="message", type="string", example="Talep eden başarıyla oluşturuldu.")
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
     * Yeni talep eden oluşturma
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $requester = Requester::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $requester,
                'message' => 'Talep eden başarıyla oluşturuldu.'
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
     *     path="/requesters/{id}",
     *     summary="Talep eden güncelleme",
     *     description="Mevcut talep edenin bilgilerini günceller",
     *     tags={"Requesters"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Talep eden ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Mehmet Demir")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla güncellendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Requester"),
     *             @OA\Property(property="message", type="string", example="Talep eden başarıyla güncellendi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Requester].")
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
     * Talep eden güncelleme
     */
    public function update(Request $request, Requester $requester): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $requester->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $requester,
                'message' => 'Talep eden başarıyla güncellendi.'
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
     *     path="/requesters/{id}",
     *     summary="Talep eden silme (Soft Delete)",
     *     description="Belirtilen talep edeni sistemden soft delete yapar. Kayıt fiziksel olarak silinmez, sadece deleted_at alanı güncellenir.",
     *     tags={"Requesters"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Talep eden ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Talep eden başarıyla silindi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Requester].")
     *         )
     *     )
     * )
     * 
     * Talep eden silme (Soft Delete)
     */
    public function destroy(Requester $requester): JsonResponse
    {
        $requester->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Talep eden başarıyla silindi.'
        ]);
    }
}