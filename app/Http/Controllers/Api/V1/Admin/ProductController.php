<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Product::all(),200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:available,out_of_stock',
            'category_id' => 'required|exists:categories,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product= Product::create($request->all());

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('public/product_images');
            $productImage = new ProductImage([
                'product_id' => $product->id,
                'image_url' => Storage::url($imagePath),
                'is_primary' => true,
            ]);
            $productImage->save();
        }
        return response()->json([
            'message' => 'Produit cree avec succes',
            'product' => $product], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('images')->find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        return response()->json($product, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'],404);
        }
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|in:available,out_of_stock',
            'category_id' => 'sometimes|required|exists:categories,id',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // Validation for image
        ]);
        $product->update($request->all());

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('public/product_images');
            $productImage = new ProductImage([
                'product_id' => $product->id,
                'image_url' => Storage::url($imagePath),
                'is_primary' => true,
            ]);
            $productImage->save();
        }
        return response()->json(['message' => 'Product created succesfully',
           'product' => $product], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé', 401]);
        }
        // Supprimer images associees
        $productImages = ProductImage::where('product_id', $id)->get();
        foreach ($productImages as $productImage) {
        // Supprimer le fichier d'image du stockage
            Storage::delete(str_replace('/storage', 'public', $productImage->image_url));
        // Supprimer l'enregistrement de l'image
            $productImage->delete();
        }

        $product->delete();
        return response()->json(['message' => 'Produit supprimé'], 200);
    }
}
