# Order Status Webhook Setup Guide

This guide explains how to configure your main Caseer Academy website to send order completion webhooks to this WhatsApp Customer Care System.

---

## 🎯 Overview

When a WooCommerce order is marked as "Completed" on your main website, it will automatically:
1. Send a webhook notification to this system
2. Create a Customer record (if doesn't exist)
3. Create a Conversation record
4. Create a Ticket with the course name and order details

---

## 📍 Webhook Endpoint

### Local Development (ngrok)
```
POST https://YOUR-NGROK-URL.ngrok-free.app/webhook/order-status
```

### Production
```
POST https://your-whatsapp-domain.com/webhook/order-status
```

---

## 📤 Webhook Payload Structure

Your main website should send a POST request with this JSON structure:

```json
{
    "event": "order_status_changed",
    "order_id": 123,
    "new_status": "completed",
    "customer": {
        "id": 45,
        "email": "student@example.com",
        "phone": "252638888872",
        "name": "Ahmed Mohamed"
    },
    "items": [
        { "name": "Laravel Masterclass" },
        { "name": "React Course" }
    ]
}
```

### Required Fields:
- `event` (string) - Must be "order_status_changed"
- `order_id` (integer) - WooCommerce order ID
- `new_status` (string) - Order status ("completed", "pending", etc.)
- `customer.id` (integer) - WordPress user ID
- `customer.email` (string) - Customer email
- `customer.phone` (string, optional) - Customer phone number with country code
- `customer.name` (string, optional) - Customer full name
- `items` (array) - Array of order items with course names

---

## ⚙️ Configuration Steps

### Step 1: Start ngrok (for local development)

```bash
ngrok http 8000
```

Copy the ngrok URL (e.g., `https://abc123.ngrok-free.app`)

### Step 2: Configure Your Main Website

In your WooCommerce plugin settings or custom webhook configuration:

**Webhook URL:** `https://YOUR-NGROK-URL.ngrok-free.app/webhook/order-status`

**Trigger:** Order status changed to "Completed"

**Payload Format:** JSON (see structure above)

### Step 3: Test the Webhook

Use cURL to test locally:

```bash
curl -X POST http://localhost:8000/webhook/order-status \
  -H "Content-Type: application/json" \
  -d '{
    "event": "order_status_changed",
    "order_id": 12345,
    "new_status": "completed",
    "customer": {
      "id": 99,
      "email": "test@example.com",
      "phone": "252638888872",
      "name": "Test Customer"
    },
    "items": [
      { "name": "Laravel Masterclass" }
    ]
  }'
```

Expected Response:
```json
{
    "success": true,
    "message": "Order processed and ticket created",
    "ticket_id": 3,
    "customer_id": 4
}
```

---

## 🔍 How It Works

1. **Order Completed** → Your main website detects order completion
2. **Webhook Sent** → POST request to `/webhook/order-status`
3. **Customer Created/Found** → System creates or finds customer by phone/email
4. **Conversation Created** → Creates a conversation record
5. **Ticket Created** → Automatic ticket with course name and order details
6. **Support Agent Notified** → Ticket appears in Filament admin panel

---

## 🛡️ Security Notes

- The webhook route is **exempt from CSRF protection** (already configured)
- For production, consider adding **webhook signature validation**
- Or use **IP whitelisting** for your main website's server

---

## 📊 Monitoring

### Check Recent Webhook Activity

```bash
# View webhook logs
tail -50 storage/logs/laravel.log | grep "Order webhook"

# Check latest tickets
php artisan tinker --execute="
\$tickets = \App\Models\Ticket::latest()->take(5)->get();
foreach (\$tickets as \$t) {
    echo 'Ticket #' . \$t->id . ' - ' . \$t->subject . ' (' . \$t->customer->name . ')' . PHP_EOL;
}
"
```

---

## 🚀 Next Steps

1. **Start ngrok:** `ngrok http 8000`
2. **Copy the ngrok URL** (e.g., `https://abc123.ngrok-free.app`)
3. **Configure your main website** with: `https://abc123.ngrok-free.app/webhook/order-status`
4. **Test** by completing a WooCommerce order
5. **Check** the Tickets page in Filament to see the auto-created ticket!

---

## ⚠️ Important Notes

- **Only "completed" orders** trigger ticket creation (other statuses are logged but ignored)
- **First item name** in the order is used as the course name
- **Phone number is optional** - system will fall back to email if phone is not provided
- **Customers are deduplicated** by phone number (or email if no phone)
- **Tickets are linked** to conversations for full WhatsApp integration

---

## 📞 Support

If you encounter any issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify ngrok is running: `ngrok http 8000`
3. Test webhook manually with cURL (see Step 3 above)
4. Ensure your main website can reach the ngrok URL




