<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    // =================== CATEGORY TREE ===================

    public function test_can_list_categories_as_nested_tree(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        Category::factory()->create(['parent_id' => $child->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $parent->id)
            ->assertJsonPath('data.0.children.0.id', $child->id)
            ->assertJsonPath('data.0.children.0.children.0.parent_id', $child->id);
    }

    public function test_can_show_category_with_children(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/categories/'.$parent->id);

        $response->assertOk()
            ->assertJsonPath('id', $parent->id)
            ->assertJsonPath('children.0.id', $child->id);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->postJson('/api/v1/categories', [
                'name' => 'Fruits',
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Fruits')
            ->assertJsonPath('parent_id', null);

        $this->assertDatabaseHas('categories', ['name' => 'Fruits']);
    }

    public function test_admin_can_create_nested_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $parent = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->postJson('/api/v1/categories', [
                'name' => 'Apples',
                'parent_id' => $parent->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Apples')
            ->assertJsonPath('parent_id', $parent->id);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/categories', [
                'name' => 'Fruits',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->putJson('/api/v1/categories/'.$category->id, [
                'name' => 'New Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'New Name');
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->deleteJson('/api/v1/categories/'.$category->id);

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    // =================== CATEGORY PRODUCTS ===================

    public function test_can_list_products_by_category(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);
        Product::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/categories/'.$category->id.'/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // =================== PRODUCTS ===================

    public function test_can_list_products(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        Product::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_products_by_category(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id]);
        Product::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/products?category_id='.$category->id);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_products_by_name(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        Product::factory()->create(['name' => 'Organic Apples']);
        Product::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/products?search=Organic');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Organic Apples');
    }

    public function test_can_show_product(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/products/'.$product->id);

        $response->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonPath('category.id', $product->category_id);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->postJson('/api/v1/products', [
                'name' => 'Organic Apples',
                'description' => 'Fresh organic apples',
                'price_fcfa' => 1500,
                'category_id' => $category->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Organic Apples')
            ->assertJsonPath('price_fcfa', '1500.00')
            ->assertJsonPath('category.id', $category->id);
    }

    public function test_non_admin_cannot_create_product(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/products', [
                'name' => 'Organic Apples',
                'price_fcfa' => 1500,
                'category_id' => $category->id,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->putJson('/api/v1/products/'.$product->id, [
                'name' => 'New Name',
                'price_fcfa' => 2500,
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('price_fcfa', '2500.00');
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->deleteJson('/api/v1/products/'.$product->id);

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
