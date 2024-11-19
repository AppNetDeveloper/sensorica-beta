<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductList;

class ProductListController extends Controller
{
        /**
     * @OA\Post(
     *     path="/api/product-lists/update-or-insert",
     *     summary="Update or insert a single product list item",
     *     tags={"Product Lists"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", description="Client ID"),
     *             @OA\Property(property="name", type="string", description="Product name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product list item updated or inserted successfully",
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

        $productList = ProductList::where('client_id', $validated['id'])->first();

        if ($productList) {
            // Update if exists
            $productList->update(['name' => $validated['name']]);
        } else {
            // Insert if not exists
            ProductList::create([
                'client_id' => $validated['id'],
                'name' => $validated['name'],
            ]);
        }

        return response()->json(['message' => 'Product list updated or inserted successfully'], 200);
    }
        /**
     * @OA\Post(
     *     path="/api/product-lists/replace-all",
     *     summary="Replace all product lists",
     *     tags={"Product Lists"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", description="Client ID"),
     *                 @OA\Property(property="name", type="string", description="Product name")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All product lists replaced successfully",
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
        ProductList::truncate();

        // Insert the new list
        foreach ($validated as $item) {
            ProductList::create([
                'client_id' => $item['id'],
                'name' => $item['name'],
            ]);
        }

        return response()->json(['message' => 'All product lists replaced successfully'], 200);
    }
}
