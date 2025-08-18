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
     *     description="Sistemdeki tüm talep edenleri getirir",
     *     tags={"Requesters"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Requester")),
     *             @OA\Property(property="message", type="string", example="Talep edenler başarıyla listelendi.")
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
    public function index(): JsonResponse
    {
        $requesters = Requester::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $requesters,
            'message' => 'Talep edenler başarıyla listelendi.'
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
     *     summary="Talep eden silme",
     *     description="Belirtilen talep edeni sistemden siler",
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
     * Talep eden silme
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
