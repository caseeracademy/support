# Integration Guide: Main Website → WhatsApp Customer Care

## Overview

This document explains how to integrate your main website with the WhatsApp Customer Care System to automatically create support tickets when orders are completed.

## When to Call This API

Call this API endpoint **immediately after**:
1. An order is completed
2. You send the "ordercompleted" WhatsApp template to the customer

## API Endpoint

**URL**: `POST http://whatsapp.test/api/tickets/from-order`

**Headers**:
```
Content-Type: application/json
```

**Request Body**:
```json
{
  "phone_number": "252638888872",
  "order_number": "12345",
  "course_name": "Laravel Masterclass",
  "customer_name": "John Doe",
  "whatsapp_message_id": "wamid.xxx" // Optional - if you have it from WhatsApp send
}
```

**Response** (Success):
```json
{
  "success": true,
  "ticket_id": 5,
  "customer_id": 3,
  "message": "Ticket created successfully"
}
```

**Response** (Error):
```json
{
  "success": false,
  "error": "Error message here"
}
```

## Integration Example (Laravel/PHP)

### Example: After Sending WhatsApp Template

```php
use Illuminate\Support\Facades\Http;

// After you send the ordercompleted template to the customer
$response = Http::post('http://whatsapp.test/api/tickets/from-order', [
    'phone_number' => $order->customer->phone,
    'order_number' => $order->id,
    'course_name' => $order->course->name,
    'customer_name' => $order->customer->name,
    'whatsapp_message_id' => $whatsappResponse['message_id'] ?? null,
]);

if ($response->successful()) {
    $ticketId = $response->json('ticket_id');
    // Ticket created successfully!
}
```

## What This Does

1. **Creates/Updates Customer**: Stores customer info in the customer care system
2. **Creates Conversation**: Links customer to a conversation thread
3. **Creates Message Record**: Saves the sent template message (if message_id provided)
4. **Creates Ticket**: Creates a support ticket with:
   - Course name: For tracking which course the customer bought
   - Order number: Reference in the subject line
   - Status: `open` (ready for support team)
   - Priority: `medium` (can be customized)

## What Happens Next

1. Customer receives the "ordercompleted" WhatsApp template
2. Ticket appears in the WhatsApp Customer Care System
3. If customer replies → Message shows up automatically via webhook
4. Support team can:
   - Click "Send Message" → Opens WhatsApp Web to chat with customer
   - View all messages in the conversation
   - Update ticket status as they resolve issues

## Benefits

- ✅ **Automatic Ticket Creation**: No manual work needed
- ✅ **Course Tracking**: Know exactly which course the customer bought
- ✅ **Conversation History**: All WhatsApp messages linked to the ticket
- ✅ **Quick Response**: Support team can chat directly via WhatsApp Web
- ✅ **Centralized Management**: All customer support in one place

## Testing

You can test this API using curl:

```bash
curl -X POST http://whatsapp.test/api/tickets/from-order \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "252638888872",
    "order_number": "12345",
    "course_name": "Laravel Masterclass",
    "customer_name": "John Doe"
  }'
```

## Production Setup

For production, replace `http://whatsapp.test` with your actual domain and consider:

1. **API Authentication**: Add token-based auth (e.g., Laravel Sanctum)
2. **Rate Limiting**: Prevent abuse
3. **HTTPS**: Use secure connection
4. **Webhooks**: Ensure Meta webhooks are configured to send to this app

## Questions?

If you have questions about integration, check the main README.md or contact the development team.

