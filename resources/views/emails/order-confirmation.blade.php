<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirmation de commande</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
        }
        h1 {
            color: #4a5568;
        }
        .order-details {
            margin-bottom: 30px;
        }
        .order-id {
            font-weight: bold;
            color: #2d3748;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9em;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>GameXpress</h1>
        </div>
        
        <h2>Merci pour votre commande!</h2>
        
        <p>Bonjour {{ $order->user->name ?? 'Cher client' }},</p>
        
        <p>Nous vous confirmons que votre commande a été traitée avec succès et que votre paiement a été approuvé.</p>
        
        <div class="order-details">
            <p><span class="order-id">Commande #{{ $order->id }}</span></p>
            <p>Date: {{ date('d/m/Y', strtotime($order->created_at)) }}</p>
            <p>Statut: <strong>{{ $order->status }}</strong></p>
        </div>
        
        <h3>Détails de la commande</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Prix</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td>{{ $product['product_name'] }}</td>
                    <td>{{ $product['quantity'] }}</td>
                    <td>{{ number_format($product['price'], 2) }} MAD</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <table>
            <tr>
                <td>Sous-total:</td>
                <td>{{ number_format($order->subtotal, 2) }} MAD</td>
            </tr>
            <tr>
                <td>TVA:</td>
                <td>{{ number_format($order->tax_amount, 2) }} MAD</td>
            </tr>
            @if($order->discount_amount > 0)
            <tr>
                <td>Remise:</td>
                <td>{{ number_format($order->discount_amount, 2) }} MAD</td>
            </tr>
            @endif
            <tr>
                <td><strong>Total:</strong></td>
                <td><strong>{{ number_format($order->total_price, 2) }} MAD</strong></td>
            </tr>
        </table>
        
        <p>Votre paiement a été traité avec succès via Stripe.</p>
        
        <p>Si vous avez des questions concernant votre commande, n'hésitez pas à nous contacter.</p>
        
        <p>Cordialement,<br>L'équipe GameXpress</p>
        
        <div class="footer">
            <p>© {{ date('Y') }} GameXpress. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>