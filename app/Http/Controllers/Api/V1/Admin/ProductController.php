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
    $products = Product::with('images')->get();
    
    $formattedProducts = $products->map(function ($product) {
        // Get all images for the product
        $images = $product->images;
        
        // Find primary image
        $primaryImage = $images->where('is_primary', true)->first();
        
        // Format other (non-primary) images
        $otherImages = $images->where('is_primary', false)->values();
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'stock' => $product->stock,
            'status' => $product->status,
            'category_id' => $product->category_id,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            'primary_image' => $primaryImage ? url($primaryImage->image_url) : null,
            'other_images' => $otherImages->map(function ($image) {
                return url($image->image_url);
            })->toArray(),
        ];
    });
    
    return response()->json($formattedProducts, 200);
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
        $product = Product::find($id);
        if (!$product) {
            return response()->json($product,200);
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
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:available,out_of_stock',
            'category_id' => 'sometimes|exists:categories,id',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        $product->update($request->only(['name', 'slug', 'price', 'stock', 'status', 'category_id']));
    
        if ($request->hasFile('image')) {
            // Find existing primary image
            $existingPrimaryImage = ProductImage::where('product_id', $product->id)
                ->where('is_primary', true)
                ->first();
                
            // If exists, change to non-primary
            if ($existingPrimaryImage) {
                $existingPrimaryImage->is_primary = false;
                $existingPrimaryImage->save();
            }
            
            // Store new image
            $imagePath = $request->file('image')->store('public/product_images');
            $productImage = new ProductImage([
                'product_id' => $product->id,
                'image_url' => Storage::url($imagePath),
                'is_primary' => true,
            ]);
            $productImage->save();
        }
        
        // Reload product with images to include in response
        $product = Product::with('images')->find($id);
        
        // Format response to match your index method
        $images = $product->images;
        $primaryImage = $images->where('is_primary', true)->first();
        $otherImages = $images->where('is_primary', false)->values();
        
        $formattedProduct = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'stock' => $product->stock,
            'status' => $product->status,
            'category_id' => $product->category_id,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            'primary_image' => $primaryImage ? url($primaryImage->image_url) : null,
            'other_images' => $otherImages->map(function ($image) {
                return url($image->image_url);
            })->toArray(),
        ];
        
        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $formattedProduct
        ], 200);
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
