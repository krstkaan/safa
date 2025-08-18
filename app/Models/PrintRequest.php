<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="PrintRequest",
 *     type="object",
 *     title="Fotokopi İsteği",
 *     description="Fotokopi isteği modeli",
 *     @OA\Property(property="id", type="integer", example=1, description="Benzersiz ID"),
 *     @OA\Property(property="requester_id", type="integer", example=1, description="Talep eden kişi ID"),
 *     @OA\Property(property="approver_id", type="integer", example=1, description="Onaylayan kişi ID"),
 *     @OA\Property(property="color_copies", type="integer", example=5, description="Renkli kopya sayısı"),
 *     @OA\Property(property="bw_copies", type="integer", example=10, description="Siyah-beyaz kopya sayısı"),
 *     @OA\Property(property="requested_at", type="string", format="date-time", example="2025-08-18T14:30:00.000000Z", description="İstek tarihi"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-08-18T14:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-08-18T14:30:00.000000Z"),
 *     @OA\Property(property="requester", ref="#/components/schemas/Requester", description="Talep eden kişi bilgileri"),
 *     @OA\Property(property="approver", ref="#/components/schemas/Approver", description="Onaylayan kişi bilgileri")
 * )
 */
class PrintRequest extends Model
{
    protected $fillable = [
        'requested_at',
        'color_copies',
        'bw_copies',
        'requester_id',
        'approver_id',
    ];

    protected $dates = ['requested_at'];

    public function requester()
    {
        return $this->belongsTo(Requester::class);
    }

    public function approver()
    {
        return $this->belongsTo(Approver::class);
    }
}
