<?php

namespace App\Http\Controllers;

use App\Models\Approver;
use App\Models\PrintRequest;
use App\Models\Requester;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PrintRequestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/print-requests",
     *     summary="Tüm fotokopi isteklerini listeleme",
     *     description="Sistemdeki tüm fotokopi isteklerini talep eden ve onaylayan bilgileriyle birlikte getirir. Pagination destekler.",
     *     tags={"Print Requests"},
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
     *         description="Sıralama alanı (varsayılan: requested_at)",
     *         @OA\Schema(type="string", enum={"id", "document_name", "requested_at", "approved_at", "requester_id", "approver_id", "color_copies", "bw_copies", "created_at", "updated_at"}, example="requested_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         required=false,
     *         description="Sıralama yönü (varsayılan: desc)",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PrintRequest")),
     *             @OA\Property(property="message", type="string", example="Fotokopi istekleri başarıyla listelendi."),
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
     * Tüm fotokopi isteklerini listeleme
     */
    public function index(Request $request): JsonResponse
    {
        // Pagination parametrelerini al ve validate et
        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 10)));
        
        // Sıralama parametrelerini al ve validate et
        $sortBy = $request->get('sort_by', 'requested_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Geçerli sıralama alanlarını kontrol et
        $allowedSortFields = ['id', 'document_name', 'requested_at', 'approved_at', 'requester_id', 'approver_id', 'color_copies', 'bw_copies', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'requested_at';
        }
        
        // Geçerli sıralama yönlerini kontrol et
        $allowedSortDirections = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedSortDirections)) {
            $sortDirection = 'desc';
        }
        
        // Toplam kayıt sayısını al
        $total = PrintRequest::count();
        
        // Toplam sayfa sayısını hesapla
        $totalPages = ceil($total / $limit);
        
        // Offset hesapla
        $offset = ($page - 1) * $limit;
        
        // Fotokopi isteklerini getir
        $printRequests = PrintRequest::with(['requester', 'approver'])
            ->orderBy($sortBy, $sortDirection)
            ->offset($offset)
            ->limit($limit)
            ->get();
        
        // Sonraki sayfa var mı kontrolü
        $hasNextPage = $page < $totalPages;
        
        return response()->json([
            'status' => 'success',
            'data' => $printRequests,
            'message' => 'Fotokopi istekleri başarıyla listelendi.',
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
     *     path="/print-requests/{id}",
     *     summary="Tekil fotokopi isteği gösterme",
     *     description="Belirtilen ID'ye sahip fotokopi isteğini talep eden ve onaylayan bilgileriyle birlikte getirir",
     *     tags={"Print Requests"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fotokopi isteği ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/PrintRequest"),
     *             @OA\Property(property="message", type="string", example="Fotokopi isteği başarıyla getirildi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\PrintRequest].")
     *         )
     *     )
     * )
     * 
     * Tekil fotokopi isteği gösterme
     */
    public function show(PrintRequest $printRequest): JsonResponse
    {
        $printRequest->load(['requester', 'approver']);
        
        return response()->json([
            'status' => 'success',
            'data' => $printRequest,
            'message' => 'Fotokopi isteği başarıyla getirildi.'
        ]);
    }

    /**
     * Yeni fotokopi isteği oluşturma
     * 
     * @OA\Post(
     *     path="/print-requests",
     *     summary="Yeni fotokopi isteği oluştur",
     *     description="Yeni bir fotokopi isteği kaydeder",
     *     tags={"Print Requests"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"requester_id","approver_id","color_copies","bw_copies","requested_at"},
     *             @OA\Property(property="requester_id", type="integer", example=1, description="Talep eden kişi ID"),
     *             @OA\Property(property="approver_id", type="integer", example=1, description="Onaylayan kişi ID"),
     *             @OA\Property(property="color_copies", type="integer", example=5, description="Renkli kopya sayısı"),
     *             @OA\Property(property="bw_copies", type="integer", example=10, description="Siyah-beyaz kopya sayısı"),
     *             @OA\Property(property="requested_at", type="string", format="date-time", example="2025-08-18 14:30:00", description="İstek tarihi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fotokopi isteği başarıyla oluşturuldu",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/PrintRequest"),
     *             @OA\Property(property="message", type="string", example="Fotokopi isteği başarıyla oluşturuldu.")
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
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'requester_id' => 'required|exists:requesters,id',
                'approver_id' => 'required|exists:approvers,id',
                'color_copies' => 'required|integer|min:0',
                'bw_copies' => 'required|integer|min:0',
                'requested_at' => 'required|date',
            ]);

            // En az bir tür kopya gerekli
            if ($validated['color_copies'] == 0 && $validated['bw_copies'] == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'En az bir renkli veya siyah-beyaz kopya sayısı girilmelidir.',
                    'errors' => ['copies' => ['En az bir renkli veya siyah-beyaz kopya sayısı girilmelidir.']]
                ], 422);
            }

            $printRequest = PrintRequest::create($validated);
            $printRequest->load(['requester', 'approver']);

            return response()->json([
                'status' => 'success',
                'data' => $printRequest,
                'message' => 'Fotokopi isteği başarıyla oluşturuldu.'
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
     *     path="/print-requests/{id}",
     *     summary="Fotokopi isteği güncelleme",
     *     description="Mevcut fotokopi isteğinin bilgilerini günceller",
     *     tags={"Print Requests"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fotokopi isteği ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"requester_id","approver_id","color_copies","bw_copies","requested_at"},
     *             @OA\Property(property="requester_id", type="integer", example=1, description="Talep eden kişi ID"),
     *             @OA\Property(property="approver_id", type="integer", example=1, description="Onaylayan kişi ID"),
     *             @OA\Property(property="color_copies", type="integer", example=5, description="Renkli kopya sayısı"),
     *             @OA\Property(property="bw_copies", type="integer", example=10, description="Siyah-beyaz kopya sayısı"),
     *             @OA\Property(property="requested_at", type="string", format="date-time", example="2025-08-18 14:30:00", description="İstek tarihi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla güncellendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/PrintRequest"),
     *             @OA\Property(property="message", type="string", example="Fotokopi isteği başarıyla güncellendi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\PrintRequest].")
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
     * Fotokopi isteği güncelleme
     */
    public function update(Request $request, PrintRequest $printRequest): JsonResponse
    {
        try {
            $validated = $request->validate([
                'requester_id' => 'required|exists:requesters,id',
                'approver_id' => 'required|exists:approvers,id',
                'color_copies' => 'required|integer|min:0',
                'bw_copies' => 'required|integer|min:0',
                'requested_at' => 'required|date',
            ]);

            // En az bir tür kopya gerekli
            if ($validated['color_copies'] == 0 && $validated['bw_copies'] == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'En az bir renkli veya siyah-beyaz kopya sayısı girilmelidir.',
                    'errors' => ['copies' => ['En az bir renkli veya siyah-beyaz kopya sayısı girilmelidir.']]
                ], 422);
            }

            $printRequest->update($validated);
            $printRequest->load(['requester', 'approver']);

            return response()->json([
                'status' => 'success',
                'data' => $printRequest,
                'message' => 'Fotokopi isteği başarıyla güncellendi.'
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
     *     path="/print-requests/{id}",
     *     summary="Fotokopi isteği silme (Soft Delete)",
     *     description="Belirtilen fotokopi isteğini sistemden soft delete yapar. Kayıt fiziksel olarak silinmez, sadece deleted_at alanı güncellenir.",
     *     tags={"Print Requests"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fotokopi isteği ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Fotokopi isteği başarıyla silindi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\PrintRequest].")
     *         )
     *     )
     * )
     * 
     * Fotokopi isteği silme (Soft Delete)
     */
    public function destroy(PrintRequest $printRequest): JsonResponse
    {
        $printRequest->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Fotokopi isteği başarıyla silindi.'
        ]);
    }
}
