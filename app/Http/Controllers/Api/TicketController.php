<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * Create a ticket from an order completion (called by main website)
     *
     * POST /api/tickets/from-order
     * {
     *   "phone_number": "252638888872",
     *   "order_number": "12345",
     *   "course_name": "Laravel Masterclass",
     *   "customer_name": "John Doe",
     *   "whatsapp_message_id": "wamid.xxx" (optional - if you have it from your send)
     * }
     */
    public function createFromOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'order_number' => 'required|string',
            'course_name' => 'required|string',
            'customer_name' => 'nullable|string',
            'whatsapp_message_id' => 'nullable|string',
        ]);

        try {
            // Create or update customer
            $customer = Customer::updateOrCreate(
                ['phone_number' => $validated['phone_number']],
                [
                    'name' => $validated['customer_name'] ?? 'Customer '.substr($validated['phone_number'], -4),
                    'last_message_at' => now(),
                ]
            );

            // Create or find conversation
            $conversation = Conversation::firstOrCreate(
                ['customer_id' => $customer->id],
                [
                    'last_message_at' => now(),
                    'status' => 'active',
                ]
            );

            // Create the outbound template message record (if we have the message ID)
            if (! empty($validated['whatsapp_message_id'])) {
                Message::firstOrCreate(
                    ['whatsapp_message_id' => $validated['whatsapp_message_id']],
                    [
                        'conversation_id' => $conversation->id,
                        'direction' => 'outbound',
                        'type' => 'template',
                        'content' => "Your order #{$validated['order_number']} for {$validated['course_name']} has been completed!",
                        'status' => 'sent',
                        'metadata' => [
                            'template_name' => 'ordercompleted',
                            'variables' => [$validated['order_number']],
                            'order_number' => $validated['order_number'],
                            'course_name' => $validated['course_name'],
                        ],
                    ]
                );
            }

            // Create ticket
            $ticket = Ticket::firstOrCreate(
                ['conversation_id' => $conversation->id],
                [
                    'customer_id' => $customer->id,
                    'course_name' => $validated['course_name'],
                    'subject' => "Support for {$validated['course_name']} (Order #{$validated['order_number']})",
                    'description' => "Order #{$validated['order_number']} completed. Customer may need support for {$validated['course_name']}.",
                    'status' => 'open',
                    'priority' => 'medium',
                ]
            );

            Log::info('Ticket created from order completion', [
                'ticket_id' => $ticket->id,
                'customer_id' => $customer->id,
                'course_name' => $validated['course_name'],
                'order_number' => $validated['order_number'],
            ]);

            return response()->json([
                'success' => true,
                'ticket_id' => $ticket->id,
                'customer_id' => $customer->id,
                'message' => 'Ticket created successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create ticket from order', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
