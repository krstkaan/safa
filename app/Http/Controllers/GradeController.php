<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class GradeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/grades",
     *     summary="Tüm sınıfları listeleme",
     *     description="Sistemdeki tüm sınıfları getirir. Pagination ve arama destekler.",
     *     tags={"Grades"},
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
     *         description="İsim alanında arama yapmak için kullanılır",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Grade")),
     *             @OA\Property(property="message", type="string", example="Sınıflar başarıyla listelendi."),
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
     * Tüm sınıfları listeleme
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
        $query = Grade::query();

        // Arama varsa filtrele (integer değer olduğu için exact match)
        if (!empty($search)) {
            $query->where('name', $search);
        }

        // Toplam kayıt sayısını al (filtreleme sonrası)
        $total = $query->count();

        // Toplam sayfa sayısını hesapla
        $totalPages = ceil($total / $limit);

        // Offset hesapla
        $offset = ($page - 1) * $limit;

        // Sınıfları getir
        $grades = $query->orderBy($sortBy, $sortDirection)
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Sonraki sayfa var mı kontrolü
        $hasNextPage = $page < $totalPages;

        $message = !empty($search)
            ? "Sınıflar arama sonuçları başarıyla listelendi."
            : "Sınıflar başarıyla listelendi.";

        return response()->json([
            'status' => 'success',
            'data' => $grades,
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
     *     path="/grades/{id}",
     *     summary="Tekil sınıf gösterme",
     *     description="Belirtilen ID'ye sahip sınıfı getirir",
     *     tags={"Grades"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Sınıf ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Grade"),
     *             @OA\Property(property="message", type="string", example="Sınıf başarıyla getirildi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Grade].")
     *         )
     *     )
     * )
     * 
     * Tekil sınıf gösterme
     */
    public function show(Grade $grade): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $grade,
            'message' => 'Sınıf başarıyla getirildi.'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/grades",
     *     summary="Yeni sınıf oluşturma",
     *     description="Yeni bir sınıf kaydı oluşturur",
     *     tags={"Grades"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="integer", example=5, minimum=1, maximum=8)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Başarıyla oluşturuldu",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Grade"),
     *             @OA\Property(property="message", type="string", example="Sınıf başarıyla oluşturuldu.")
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
     * Yeni sınıf oluşturma
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|integer|min:1|max:8|unique:grades,name',
            ]);

            $grade = Grade::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $grade,
                'message' => 'Sınıf başarıyla oluşturuldu.'
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
     *     path="/grades/{id}",
     *     summary="Sınıf güncelleme",
     *     description="Mevcut sınıfın bilgilerini günceller",
     *     tags={"Grades"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Sınıf ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="integer", example=6, minimum=1, maximum=8)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla güncellendi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Grade"),
     *             @OA\Property(property="message", type="string", example="Sınıf başarıyla güncellendi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Grade].")
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
     * Sınıf güncelleme
     */
    public function update(Request $request, Grade $grade): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|integer|min:1|max:8|unique:grades,name,' . $grade->id,
            ]);

            $grade->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $grade,
                'message' => 'Sınıf başarıyla güncellendi.'
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
     *     path="/grades/{id}",
     *     summary="Sınıf silme (Soft Delete)",
     *     description="Belirtilen sınıfı sistemden soft delete yapar. Kayıt fiziksel olarak silinmez, sadece deleted_at alanı güncellenir.",
     *     tags={"Grades"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Sınıf ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Sınıf başarıyla silindi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Grade].")
     *         )
     *     )
     * )
     * 
     * Sınıf silme (Soft Delete)
     */
    public function destroy(Grade $grade): JsonResponse
    {
        $grade->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sınıf başarıyla silindi.'
        ]);
    }
}