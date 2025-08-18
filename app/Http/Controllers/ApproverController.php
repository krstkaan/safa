<?php

namespace App\Http\Controllers;

use App\Models\Approver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApproverController extends Controller
{
    /**
     * @OA\Get(
     *     path="/approvers",
     *     summary="Tüm onaylayanları listeleme",
     *     description="Sistemdeki tüm onaylayanları getirir",
     *     tags={"Approvers"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Approver")),
     *             @OA\Property(property="message", type="string", example="Onaylayanlar başarıyla listelendi.")
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
     * Tüm onaylayanları listeleme
     */
    public function index(): JsonResponse
    {
        $approvers = Approver::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $approvers,
            'message' => 'Onaylayanlar başarıyla listelendi.'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/approvers/{id}",
     *     summary="Tekil onaylayan gösterme",
     *     description="Belirtilen ID'ye sahip onaylayanı getirir",
     *     tags={"Approvers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Onaylayan ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Approver"),
     *             @OA\Property(property="message", type="string", example="Onaylayan başarıyla getirildi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Approver].")
     *         )
     *     )
     * )
     * 
     * Tekil onaylayan gösterme
     */
    public function show(Approver $approver): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $approver,
            'message' => 'Onaylayan başarıyla getirildi.'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/approvers",
     *     summary="Yeni onaylayan oluşturma",
     *     description="Yeni bir onaylayan kaydı oluşturur",
     *     tags={"Approvers"},
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
     *             @OA\Property(property="data", ref="#/components/schemas/Approver"),
     *             @OA\Property(property="message", type="string", example="Onaylayan başarıyla oluşturuldu.")
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
     * Yeni onaylayan oluşturma
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $approver = Approver::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $approver,
                'message' => 'Onaylayan başarıyla oluşturuldu.'
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
     *     path="/approvers/{id}",
     *     summary="Onaylayan güncelleme",
     *     description="Mevcut onaylayanın bilgilerini günceller",
     *     tags={"Approvers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Onaylayan ID",
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
     *             @OA\Property(property="data", ref="#/components/schemas/Approver"),
     *             @OA\Property(property="message", type="string", example="Onaylayan başarıyla güncellendi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Approver].")
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
     * Onaylayan güncelleme
     */
    public function update(Request $request, Approver $approver): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $approver->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $approver,
                'message' => 'Onaylayan başarıyla güncellendi.'
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
     *     path="/approvers/{id}",
     *     summary="Onaylayan silme (Soft Delete)",
     *     description="Belirtilen onaylayanı sistemden soft delete yapar. Kayıt fiziksel olarak silinmez, sadece deleted_at alanı güncellenir.",
     *     tags={"Approvers"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Onaylayan ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarıyla silindi",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Onaylayan başarıyla silindi.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bulunamadı",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Approver].")
     *         )
     *     )
     * )
     * 
     * Onaylayan silme (Soft Delete)
     */
    public function destroy(Approver $approver): JsonResponse
    {
        $approver->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Onaylayan başarıyla silindi.'
        ]);
    }
}
