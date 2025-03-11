<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Product;

class StockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function via(object $notifiable): array
    {
        return ['mail','database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Stock critique : ' . $this->product->name)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line("Le produit **{$this->product->name}** est en stock critique.")
            ->line("Stock actuel : **{$this->product->stock}** unités.")
            ->action('Voir le produit', url('/admin/products/' . $this->product->id))
            ->line('Veuillez réapprovisionner le stock dès que possible.');
    }
}
