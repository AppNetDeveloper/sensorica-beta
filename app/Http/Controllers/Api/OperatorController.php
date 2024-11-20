<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operator;

class OperatorController extends Controller
{
       /**
     * @OA\Post(
     *     path="/api/workers/update-or-insert",
     *     summary="Update or insert a single worker",
     *     tags={"Workers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", description="Client ID"),
     *             @OA\Property(property="name", type="string", description="Worker name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Worker updated or inserted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateOrInsertSingle(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string',
        ]);

        $operator = Operator::where('client_id', $validated['id'])->first();

        if ($operator) {
            // Update if exists
            $operator->update(['name' => $validated['name']]);
        } else {
            // Insert if not exists
            Operator::create([
                'client_id' => $validated['id'],
                'name' => $validated['name'],
            ]);
        }

        return response()->json(['message' => 'Operator updated or inserted successfully'], 200);
    }
        /**
     * @OA\Post(
     *     path="/api/workers/replace-all",
     *     summary="Replace all Workers",
     *     tags={"Workers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", description="Client ID"),
     *                 @OA\Property(property="name", type="string", description="Workers name")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All workers replaced successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function replaceAll(Request $request)
    {
        $validated = $request->validate([
            '*.id' => 'required|integer',
            '*.name' => 'required|string',
        ]);

        // Delete all current records
        Operator::truncate();

        // Insert the new list
        foreach ($validated as $item) {
            Operator::create([
                'client_id' => $item['id'],
                'name' => $item['name'],
            ]);
        }

        return response()->json(['message' => 'All operators replaced successfully'], 200);
    }
}
