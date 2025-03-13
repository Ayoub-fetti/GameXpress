<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

   public function test_admin_can_create_and_list_category(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );
        
        // Authenticate as admin
        $this->actingAs($admin);
    
        // Create a category
        $category = Category::create([
            'name' => 'Testa Category',
            'slug' => 't025',
        ]);
    
    
        // Count the number of products in the database
        $categoryCount = Category::count();
    
          // Make request to fetch products
          $response = $this->getJson('/api/v1/admin/categories');
    
          // Check the response status
          $response->assertStatus(200);
      
          // Assert that the response contains the correct number of products
          $response->assertJsonCount($categoryCount);
    }
   public function test_admin_can_update_category(): void
    {
    $admin = User::firstOrCreate(
        ['email' => 'admin@gmail.com'],
        ['name' => 'Admin', 'password' => bcrypt('password')]
    );
    $this->actingAs($admin);

    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);

    $updatedData = [
        'name' => 'Updated category',
        'slug' => 't026',
    ];

    $response = $this->putJson("/api/v1/admin/categories/{$category->id}", $updatedData);

    $response->assertStatus(200);
    $this->assertDatabaseHas('categories', array_merge(['id' => $category->id], $updatedData));
    }
   public function test_admin_can_delete_category(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );
        $this->actingAs($admin);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);


        $response = $this->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

}
