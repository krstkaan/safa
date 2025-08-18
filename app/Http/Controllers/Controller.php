<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Fotokopi İstek Yönetim API",
 *     version="1.0.0",
 *     description="Ofis fotokopi isteklerini yönetmek için API sistemi",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api",
 *     description="Local Development Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}
