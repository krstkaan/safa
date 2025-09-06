<?php

namespace App\Http\Controllers;

use App\Exports\AllPrintRequestsExport;
use App\Exports\PrintRequestsComparisonExport;
use App\Models\Approver;
use App\Models\PrintRequest;
use App\Models\Requester;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exports\PrintRequestsByRequesterExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Carbon\Carbon;

class PrintRequestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/print-requests",
     *     summary="Tüm fotokopi isteklerini listeleme",
     *     description="Sistemdeki tüm fotokopi isteklerini talep eden ve onaylayan bilgileriyle birlikte getirir. Pagination ve filtreleme destekler.",
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
     *     @OA\Parameter(
     *         name="requester_names",
     *         in="query",
     *         required=false,
     *         description="Talep eden isimleri (virgülle ayrılmış, örnek: 'Ahmet Yılmaz,Mehmet Demir')",
     *         @OA\Schema(type="string", example="Ahmet Yılmaz,Mehmet Demir")
     *     ),
     *     @OA\Parameter(
     *         name="approver_names",
     *         in="query",
     *         required=false,
     *         description="Onaylayan isimleri (virgülle ayrılmış, örnek: 'Ali Veli,Ayşe Fatma')",
     *         @OA\Schema(type="string", example="Ali Veli,Ayşe Fatma")
     *     ),
     *     @OA\Parameter(
     *         name="color_copies_min",
     *         in="query",
     *         required=false,
     *         description="Minimum renkli kopya sayısı",
     *         @OA\Schema(type="integer", example=1, minimum=0)
     *     ),
     *     @OA\Parameter(
     *         name="color_copies_max",
     *         in="query",
     *         required=false,
     *         description="Maksimum renkli kopya sayısı",
     *         @OA\Schema(type="integer", example=100, minimum=0)
     *     ),
     *     @OA\Parameter(
     *         name="bw_copies_min",
     *         in="query",
     *         required=false,
     *         description="Minimum siyah-beyaz kopya sayısı",
     *         @OA\Schema(type="integer", example=1, minimum=0)
     *     ),
     *     @OA\Parameter(
     *         name="bw_copies_max",
     *         in="query",
     *         required=false,
     *         description="Maksimum siyah-beyaz kopya sayısı",
     *         @OA\Schema(type="integer", example=100, minimum=0)
     *     ),
     *     @OA\Parameter(
     *         name="requested_at_from",
     *         in="query",
     *         required=false,
     *         description="Talep tarihi başlangıç (Y-m-d H:i:s formatında)",
     *         @OA\Schema(type="string", format="date-time", example="2025-08-01 00:00:00")
     *     ),
     *     @OA\Parameter(
     *         name="requested_at_to",
     *         in="query",
     *         required=false,
     *         description="Talep tarihi bitiş (Y-m-d H:i:s formatında)",
     *         @OA\Schema(type="string", format="date-time", example="2025-08-31 23:59:59")
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
     *             ),
     *             @OA\Property(property="filters_applied", type="object", description="Uygulanan filtreler")
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

        // Query builder'ı başlat
        $query = PrintRequest::with(['requester', 'approver']);

        // Filtreleri uygula
        $appliedFilters = $this->applyFilters($query, $request);

        // Toplam kayıt sayısını al (filtreler uygulandıktan sonra)
        $total = $query->count();

        // Toplam sayfa sayısını hesapla
        $totalPages = ceil($total / $limit);

        // Offset hesapla
        $offset = ($page - 1) * $limit;

        // Fotokopi isteklerini getir
        $printRequests = $query
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
            ],
            'filters_applied' => $appliedFilters
        ]);
    }

    /**
     * Filtreleri query'ye uygula
     */
    private function applyFilters($query, Request $request): array
    {
        $appliedFilters = [];

        // Talep eden isim filtresi (VEYA mantığı ile çalışır)
        if ($request->has('requester_names') && !empty($request->get('requester_names'))) {
            $requesterNames = array_filter(array_map('trim', explode(',', $request->get('requester_names'))));
            if (!empty($requesterNames)) {
                $query->whereHas('requester', function ($q) use ($requesterNames) {
                    $q->whereIn('name', $requesterNames);
                });
                $appliedFilters['requester_names'] = $requesterNames;
            }
        }

        // Onaylayan isim filtresi (VEYA mantığı ile çalışır)
        if ($request->has('approver_names') && !empty($request->get('approver_names'))) {
            $approverNames = array_filter(array_map('trim', explode(',', $request->get('approver_names'))));
            if (!empty($approverNames)) {
                $query->whereHas('approver', function ($q) use ($approverNames) {
                    $q->whereIn('name', $approverNames);
                });
                $appliedFilters['approver_names'] = $approverNames;
            }
        }

        // Renkli kopya sayısı filtresi (aralık)
        $colorCopiesMin = $request->get('color_copies_min');
        $colorCopiesMax = $request->get('color_copies_max');

        if ($colorCopiesMin !== null && is_numeric($colorCopiesMin)) {
            $colorCopiesMin = max(0, (int) $colorCopiesMin);
            $query->where('color_copies', '>=', $colorCopiesMin);
            $appliedFilters['color_copies_min'] = $colorCopiesMin;
        }

        if ($colorCopiesMax !== null && is_numeric($colorCopiesMax)) {
            $colorCopiesMax = max(0, (int) $colorCopiesMax);
            $query->where('color_copies', '<=', $colorCopiesMax);
            $appliedFilters['color_copies_max'] = $colorCopiesMax;
        }

        // Siyah-beyaz kopya sayısı filtresi (aralık)
        $bwCopiesMin = $request->get('bw_copies_min');
        $bwCopiesMax = $request->get('bw_copies_max');

        if ($bwCopiesMin !== null && is_numeric($bwCopiesMin)) {
            $bwCopiesMin = max(0, (int) $bwCopiesMin);
            $query->where('bw_copies', '>=', $bwCopiesMin);
            $appliedFilters['bw_copies_min'] = $bwCopiesMin;
        }

        if ($bwCopiesMax !== null && is_numeric($bwCopiesMax)) {
            $bwCopiesMax = max(0, (int) $bwCopiesMax);
            $query->where('bw_copies', '<=', $bwCopiesMax);
            $appliedFilters['bw_copies_max'] = $bwCopiesMax;
        }

        // Talep tarihi filtresi (aralık)
        if ($request->has('requested_at_from') && !empty($request->get('requested_at_from'))) {
            try {
                $fromDate = Carbon::parse($request->get('requested_at_from'));
                $query->where('requested_at', '>=', $fromDate);
                $appliedFilters['requested_at_from'] = $fromDate->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Geçersiz tarih formatı, filtreyi yok say
            }
        }

        if ($request->has('requested_at_to') && !empty($request->get('requested_at_to'))) {
            try {
                $toDate = Carbon::parse($request->get('requested_at_to'));
                $query->where('requested_at', '<=', $toDate);
                $appliedFilters['requested_at_to'] = $toDate->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Geçersiz tarih formatı, filtreyi yok say
            }
        }

        return $appliedFilters;
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
     *             @OA\Property(property="description", type="string", example="Matematik dersi için fotokopi isteği", description="İstek açıklama metni"),
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
                'description' => 'nullable|string|max:500',
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
     *             @OA\Property(property="description", type="string", example="Matematik dersi için fotokopi isteği", description="İstek açıklama metni"),
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
                'description' => 'nullable|string|max:500',
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



    /**
     * @OA\Get(
     *     path="/print-requests/export/by-requester",
     *     summary="Talep Edenlere Göre Fotokopi İsteklerini Excel'e Aktarma",
     *     description="Belirtilen tarih aralığında, her bir talep edenin toplam fotokopi istek sayısını ve kopya adetlerini içeren bir Excel dosyası oluşturur.",
     *     tags={"Print Requests"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=true,
     *         description="Rapor başlangıç tarihi (Y-m-d formatında)",
     *         @OA\Schema(type="string", format="date", example="2025-08-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=true,
     *         description="Rapor bitiş tarihi (Y-m-d formatında)",
     *         @OA\Schema(type="string", format="date", example="2025-08-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel dosyası başarıyla indirildi.",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Doğrulama hatası (tarih formatları yanlış veya eksik)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Doğrulama hatası."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function exportByRequester(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            // Gelen tarihlerin günün başlangıcı ve bitişini kapsamasını sağlayalım.
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();

            $fileName = 'fotokopi_raporu_' . $startDate->format('Y-m-d') . '_-_' . $endDate->format('Y-m-d') . '.xlsx';

            return Excel::download(new PrintRequestsByRequesterExport($startDate->toDateTimeString(), $endDate->toDateTimeString()), $fileName);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/print-requests/export/comparison",
     *     summary="İki Tarih Aralığı Karşılaştırma Raporu",
     *     description="İki farklı tarih aralığındaki fotokopi isteklerini kişi bazında karşılaştıran Excel raporu oluşturur.",
     *     tags={"Print Requests"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="first_start_date",
     *         in="query",
     *         required=true,
     *         description="İlk dönem başlangıç tarihi (Y-m-d formatında)",
     *         @OA\Schema(type="string", format="date", example="2025-07-01")
     *     ),
     *     @OA\Parameter(
     *         name="first_end_date",
     *         in="query",
     *         required=true,
     *         description="İlk dönem bitiş tarihi (Y-m-d formatında)",
     *         @OA\Schema(type="string", format="date", example="2025-07-31")
     *     ),
     *     @OA\Parameter(
     *         name="second_start_date",
     *         in="query",
     *         required=true,
     *         description="İkinci dönem başlangıç tarihi (Y-m-d formatında)",
     *         @OA\Schema(type="string", format="date", example="2025-08-01")
     *     ),
     *     @OA\Parameter(
     *         name="second_end_date",
     *         in="query",
     *         required=true,
     *         description="İkinci dönem bitiş tarihi (Y-m-d formatında)",
     *         @OA\Schema(type="string", format="date", example="2025-08-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel dosyası başarıyla indirildi.",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string", format="binary")
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

    public function exportComparison(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_start_date' => 'required|date_format:Y-m-d',
                'first_end_date' => 'required|date_format:Y-m-d|after_or_equal:first_start_date',
                'second_start_date' => 'required|date_format:Y-m-d',
                'second_end_date' => 'required|date_format:Y-m-d|after_or_equal:second_start_date',
            ]);

            $firstStartDate = Carbon::parse($validated['first_start_date'])->startOfDay();
            $firstEndDate = Carbon::parse($validated['first_end_date'])->endOfDay();
            $secondStartDate = Carbon::parse($validated['second_start_date'])->startOfDay();
            $secondEndDate = Carbon::parse($validated['second_end_date'])->endOfDay();

            $fileName = 'fotokopi_karsilastirma_' .
                $firstStartDate->format('Y-m-d') . '_' . $firstEndDate->format('Y-m-d') . '_vs_' .
                $secondStartDate->format('Y-m-d') . '_' . $secondEndDate->format('Y-m-d') . '.xlsx';

            return Excel::download(
                new PrintRequestsComparisonExport(
                    $firstStartDate->toDateTimeString(),
                    $firstEndDate->toDateTimeString(),
                    $secondStartDate->toDateTimeString(),
                    $secondEndDate->toDateTimeString()
                ),
                $fileName
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/print-requests/export/all",
     *     summary="Tüm fotokopi taleplerini Excel formatında export et",
     *     tags={"Print Requests"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Başlangıç tarihi (YYYY-MM-DD formatında, isteğe bağlı)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Bitiş tarihi (YYYY-MM-DD formatında, isteğe bağlı)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel dosyası başarıyla oluşturuldu",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string", format="binary")
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
    public function exportAll(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'nullable|date|date_format:Y-m-d',
                'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $startDate = null;
            $endDate = null;
            $fileName = 'tum_fotokopi_talepleri';

            // Eğer tarih aralığı belirtilmişse kullan
            if (isset($validated['start_date']) && isset($validated['end_date'])) {
                $startDate = Carbon::parse($validated['start_date'])->startOfDay()->toDateTimeString();
                $endDate = Carbon::parse($validated['end_date'])->endOfDay()->toDateTimeString();
                $fileName .= '_' . $validated['start_date'] . '_' . $validated['end_date'];
            }

            $fileName .= '.xlsx';

            return Excel::download(
                new AllPrintRequestsExport($startDate, $endDate),
                $fileName
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors()
            ], 422);
        }
    }
}