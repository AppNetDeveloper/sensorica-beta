<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductList;

class ProductListController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/product-lists",
     *     summary="Get all product list items",
     *     tags={"Product Lists"},
     *     @OA\Response(
     *         response=200,
     *         description="Returns all product list items",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", description="Client ID"),
     *                 @OA\Property(property="name", type="string", description="Product name"),
     *                 @OA\Property(property="optimal_production_time", type="integer", description="Optimal production time"),
     *                 @OA\Property(property="box_kg", type="number", format="float", description="Box weight in kilograms")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Error message")
     *         )
     *     )
     * )
     */
    public function listAll()
    {
        $products = ProductList::all(['client_id as id', 'name', 'optimal_production_time', 'box_kg']);
        return response()->json($products, 200);
    }
    
    /**
     * @OA\Delete(
     *     path="/api/product-lists/{id}",
     *     summary="Delete a product list item",
     *     tags={"Product Lists"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the product to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        // Buscar el producto por client_id
        $product = ProductList::where('client_id', $id)->first();
    
        // Verificar si existe
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
    
        // Eliminar el producto
        $product->delete();
    
        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
    
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
     *             @OA\Property(property="name", type="string", description="Product name"),
     *             @OA\Property(property="optimal_production_time", type="integer", description="Optimal production time"),
     *             @OA\Property(property="box_kg", type="number", format="float", description="Box weight in kilograms")
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
            'optimal_production_time' => 'nullable|integer',
            'box_kg' => 'nullable|numeric',
        ]);

        $productList = ProductList::where('client_id', $validated['id'])->first();

        if ($productList) {
            // Update if exists
            $productList->update([
                'name' => $validated['name'],
                'optimal_production_time' => $validated['optimal_production_time'],
                'box_kg' => $validated['box_kg'],
            ]);
        } else {
            // Insert if not exists
            ProductList::create([
                'client_id' => $validated['id'],
                'name' => $validated['name'],
                'optimal_production_time' => $validated['optimal_production_time'],
                'box_kg' => $validated['box_kg'],
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
     *                 @OA\Property(property="name", type="string", description="Product name"),
     *                 @OA\Property(property="optimal_production_time", type="integer", description="Optimal production time"),
     *                 @OA\Property(property="box_kg", type="number", format="float", description="Box weight in kilograms")
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
            '*.optimal_production_time' => 'nullable|integer',
            '*.box_kg' => 'nullable|numeric',
        ]);

        // Delete all current records
        ProductList::truncate();

        // Insert the new list
        foreach ($validated as $item) {
            ProductList::create([
                'client_id' => $item['id'],
                'name' => $item['name'],
                'optimal_production_time' => $item['optimal_production_time'],
                'box_kg' => $item['box_kg'],
            ]);
        }

        return response()->json(['message' => 'All product lists replaced successfully'], 200);
    }
}
