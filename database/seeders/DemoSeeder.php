<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityRate;
use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Repayment;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUsers();
        $this->createSettings();
        $this->createCatalog();
        $this->createFarmers();
        $this->createCommodities();
        $this->createTransactionsAndDebts();
        $this->createRepayments();
    }

    private function createUsers(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@market.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        $supervisor = User::factory()->create([
            'name' => 'Supervisor User',
            'email' => 'supervisor@market.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Supervisor,
        ]);

        $operator = User::factory()->create([
            'name' => 'Operator User',
            'email' => 'operator@market.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);

        $this->command->info("Created users: admin, supervisor, operator");
    }

    private function createSettings(): void
    {
        Setting::set('interest_rate', 0.05);
        Setting::set('default_credit_limit', 50000);

        $this->command->info("Created settings: interest_rate=5%, default_credit_limit=50000");
    }

    private function createCatalog(): void
    {
        $seeds = Category::factory()->create(['name' => 'Seeds', 'parent_id' => null]);
        $fertilizers = Category::factory()->create(['name' => 'Fertilizers', 'parent_id' => null]);
        $tools = Category::factory()->create(['name' => 'Tools', 'parent_id' => null]);

        $maize = Category::factory()->create(['name' => 'Maize', 'parent_id' => $seeds->id]);
        $rice = Category::factory()->create(['name' => 'Rice', 'parent_id' => $seeds->id]);

        Product::factory()->create(['name' => 'Hybrid Maize Seed (1kg)', 'price_fcfa' => 2500, 'category_id' => $maize->id]);
        Product::factory()->create(['name' => 'Local Maize Seed (1kg)', 'price_fcfa' => 1500, 'category_id' => $maize->id]);
        Product::factory()->create(['name' => 'Rice Seed NERICA (1kg)', 'price_fcfa' => 3000, 'category_id' => $rice->id]);
        Product::factory()->create(['name' => 'NPK Fertilizer (50kg)', 'price_fcfa' => 15000, 'category_id' => $fertilizers->id]);
        Product::factory()->create(['name' => 'Urea Fertilizer (50kg)', 'price_fcfa' => 12000, 'category_id' => $fertilizers->id]);
        Product::factory()->create(['name' => 'Hoe', 'price_fcfa' => 3500, 'category_id' => $tools->id]);
        Product::factory()->create(['name' => 'Machete', 'price_fcfa' => 2000, 'category_id' => $tools->id]);

        $this->command->info("Created catalog: 3 categories, 7 products");
    }

    private function createFarmers(): void
    {
        Farmer::factory()->create([
            'name' => 'Moussa Traore',
            'card_id' => 'FARM-001',
            'phone' => '+22370000001',
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 0,
        ]);

        Farmer::factory()->create([
            'name' => 'Aminata Diallo',
            'card_id' => 'FARM-002',
            'phone' => '+22370000002',
            'credit_limit' => 75000,
            'credit_balance_fcfa' => 25000,
        ]);

        Farmer::factory()->create([
            'name' => 'Kofi Mensah',
            'card_id' => 'FARM-003',
            'phone' => '+22370000003',
            'credit_limit' => 30000,
            'credit_balance_fcfa' => 30000,
        ]);

        $this->command->info("Created 3 farmers");
    }

    private function createCommodities(): void
    {
        $maize = Commodity::factory()->create(['name' => 'Maize', 'unit' => 'kg']);
        $rice = Commodity::factory()->create(['name' => 'Rice', 'unit' => 'kg']);

        CommodityRate::factory()->create([
            'commodity_id' => $maize->id,
            'rate_fcfa_per_unit' => 500,
            'is_active' => true,
        ]);

        CommodityRate::factory()->create([
            'commodity_id' => $rice->id,
            'rate_fcfa_per_unit' => 800,
            'is_active' => true,
        ]);

        $this->command->info("Created commodities: Maize (500 FCFA/kg), Rice (800 FCFA/kg)");
    }

    private function createTransactionsAndDebts(): void
    {
        $farmer1 = Farmer::where('card_id', 'FARM-001')->first();
        $farmer2 = Farmer::where('card_id', 'FARM-002')->first();
        $operator = User::where('email', 'operator@market.local')->first();
        $products = Product::all();

        // Cash transaction
        $tx1 = Transaction::factory()->create([
            'farmer_id' => $farmer1->id,
            'operator_id' => $operator->id,
            'payment_method' => 'cash',
            'total_amount' => 5000,
        ]);
        TransactionItem::factory()->create([
            'transaction_id' => $tx1->id,
            'product_id' => $products[0]->id,
            'quantity' => 2,
            'unit_price' => 2500,
            'total_price' => 5000,
        ]);

        // Credit transaction with debt
        $tx2 = Transaction::factory()->create([
            'farmer_id' => $farmer2->id,
            'operator_id' => $operator->id,
            'payment_method' => 'credit',
            'total_amount' => 15000,
        ]);
        TransactionItem::factory()->create([
            'transaction_id' => $tx2->id,
            'product_id' => $products[3]->id,
            'quantity' => 1,
            'unit_price' => 15000,
            'total_price' => 15000,
        ]);

        $totalDue = round(15000 * 1.05, 2);
        Debt::factory()->create([
            'transaction_id' => $tx2->id,
            'farmer_id' => $farmer2->id,
            'principal' => 15000,
            'interest_rate' => 0.05,
            'total_due' => $totalDue,
            'amount_repaid' => 0,
            'balance' => $totalDue,
            'status' => 'open',
        ]);

        $farmer2->credit_balance_fcfa = $totalDue;
        $farmer2->save();

        $this->command->info("Created transactions: 1 cash, 1 credit with debt");
    }

    private function createRepayments(): void
    {
        $farmer2 = Farmer::where('card_id', 'FARM-002')->first();
        $operator = User::where('email', 'operator@market.local')->first();

        Repayment::factory()->create([
            'farmer_id' => $farmer2->id,
            'operator_id' => $operator->id,
            'amount' => 5000,
            'payment_method' => 'cash',
            'reference' => 'RPY-20260502-0001',
        ]);

        // Update debt
        $debt = Debt::where('farmer_id', $farmer2->id)->first();
        $debt->balance = round($debt->balance - 5000, 2);
        $debt->amount_repaid = 5000;
        $debt->status = 'partially_paid';
        $debt->save();

        $this->command->info("Created repayment: 5000 FCFA cash repayment");
    }
}
