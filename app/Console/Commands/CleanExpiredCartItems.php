<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanExpiredCartItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-expired-cart-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove cart items that have been inactive for 48 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning expired cart items...');
        
        $cutoffTime = Carbon::now()->subHours(48);
        
        // D'abord, trouvons les articles du panier inactifs depuis 48 heures
        $expiredItems = CartItem::where('updated_at', '<', $cutoffTime)->get();
        $count = $expiredItems->count();
        
        $this->info("Found {$count} expired cart items");
        
        // Pour chaque article, on le supprime
        foreach ($expiredItems as $item) {
            $this->line("Removing item ID: {$item->id} for product ID: {$item->product_id}");
            $cartId = $item->cart_id;
            $item->delete();
            
            // Loguer l'action pour référence
            Log::info("Removed expired cart item {$item->id} for product {$item->product_id}");
        }
        
        // Maintenant, nettoyons les paniers vides
        $emptyCarts = Cart::whereDoesntHave('items')->get();
        $emptyCartsCount = $emptyCarts->count();
        
        $this->info("Found {$emptyCartsCount} empty carts");
        
        foreach ($emptyCarts as $cart) {
            $this->line("Removing empty cart ID: {$cart->id}");
            $cart->delete();
            
            Log::info("Removed empty cart {$cart->id}");
        }
        
        $this->info("Cart cleanup completed. Removed {$count} items and {$emptyCartsCount} empty carts.");
        
        return Command::SUCCESS;
    }
}