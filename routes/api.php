// routes/api.php
use App\Http\Controllers\PayPalWebhookController;

Route::post('/webhooks/paypal', PayPalWebhookController::class);

