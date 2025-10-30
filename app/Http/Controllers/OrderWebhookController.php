<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderWebhookController extends Controller
{
    /**
     * Handle incoming order status webhook from main website
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Order webhook received', [
            'payload' => $payload,
            'raw_body' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
        ]);

        // Handle empty payload
        if (empty($payload)) {
            Log::warning('Order webhook received empty payload');

            return response()->json([
                'success' => false,
                'message' => 'Empty payload received',
            ], 400);
        }

        // Validate the incoming data
        $validated = $request->validate([
            'event' => 'required|string',
            'order_id' => 'required|integer',
            'new_status' => 'required|string',
            'total' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'customer' => 'required|array',
            'customer.id' => 'nullable|integer',
            'customer.email' => 'nullable|email',
            'customer.phone' => 'nullable|string',
            'customer.billing_phone' => 'nullable|string',
            'customer.name' => 'nullable|string',
            'items' => 'required|array',
        ]);

        // Extract data
        $orderId = $validated['order_id'];
        $customerData = $validated['customer'];
        $items = $validated['items'];
        $courseName = $items[0]['name'] ?? 'Unknown Course';
        $orderTotal = $validated['total'] ?? null;
        $orderCurrency = $validated['currency'] ?? 'USD';
        $orderStatus = $validated['new_status'];

        // Check if this is a guest order (no customer ID or email)
        $isGuestOrder = empty($customerData['id']) || empty($customerData['email']);

        // Determine phone number and customer name
        // Priority: billing_phone > phone > "No number used"
        $phoneNumber = $customerData['billing_phone'] ?? $customerData['phone'] ?? null;
        $customerId = $customerData['id'] ?? 0;
        $customerEmail = ! empty($customerData['email']) ? $customerData['email'] : "guest-order-{$orderId}@caseer.academy";
        $customerName = trim($customerData['name'] ?? '') ?: ($isGuestOrder ? "Guest Order #{$orderId}" : "Customer {$customerId}");

        // Create or find customer
        $customer = null;
        if ($phoneNumber) {
            $customer = Customer::firstOrCreate(
                ['phone_number' => $phoneNumber],
                [
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'metadata' => [
                        'source' => 'order_webhook',
                        'wordpress_user_id' => $customerId,
                        'is_guest_order' => $isGuestOrder,
                    ],
                ]
            );
        } else {
            // Create customer by email if no phone
            // Use "No number used" as placeholder
            $uniquePhoneNumber = 'No number used';

            $customer = Customer::firstOrCreate(
                ['email' => $customerEmail],
                [
                    'name' => $customerName,
                    'phone_number' => $uniquePhoneNumber,
                    'metadata' => [
                        'source' => 'order_webhook',
                        'wordpress_user_id' => $customerId,
                        'no_phone_provided' => true,
                        'is_guest_order' => $isGuestOrder,
                    ],
                ]
            );
        }

        // Find existing ticket by order ID in description, or create new one
        $ticket = Ticket::where('description', 'LIKE', "%Order #{$orderId}%")->first();

        $orderStatusLabel = ucfirst($orderStatus);
        $guestLabel = $isGuestOrder ? ' [GUEST ORDER]' : '';
        $description = "Order #{$orderId}{$guestLabel} ({$orderStatusLabel}) for {$courseName}. Customer: {$customerName} ({$customerEmail})";

        // Use the exact WooCommerce status from the webhook
        $ticketStatus = $orderStatus; // Keep it as-is from WooCommerce

        // Determine payment status based on order status
        // "completed" orders are considered paid, others need review/approval
        $paymentStatus = $orderStatus === 'completed' ? 'paid' : 'pending';
        $paidAmount = ($orderStatus === 'completed' && $orderTotal) ? $orderTotal : 0;
        $paidAt = $orderStatus === 'completed' ? now() : null;

        if ($ticket) {
            // Update existing ticket
            $ticket->update([
                'subject' => "Order {$orderStatusLabel}: {$courseName}",
                'description' => $description,
                'status' => $ticketStatus,
                'total_amount' => $orderTotal,
                'paid_amount' => $paidAmount,
                'currency' => $orderCurrency,
                'payment_status' => $paymentStatus,
                'order_reference' => $orderId,
                'paid_at' => $paidAt,
            ]);
            $action = 'updated';
        } else {
            // Create new ticket with payment information
            $ticket = Ticket::create([
                'customer_id' => $customer->id,
                'course_name' => $courseName,
                'subject' => "Order {$orderStatusLabel}: {$courseName}",
                'description' => $description,
                'status' => $ticketStatus,
                'priority' => 'medium',
                // Payment fields from webhook
                'total_amount' => $orderTotal,
                'paid_amount' => $paidAmount,
                'currency' => $orderCurrency,
                'payment_status' => $paymentStatus,
                'order_reference' => $orderId,
                'paid_at' => $paidAt,
                'payment_due_date' => $orderStatus !== 'completed' ? now()->addDays(7) : null,
            ]);
            $action = 'created';
        }

        Log::info("Order webhook processed successfully - Ticket {$action}", [
            'order_id' => $orderId,
            'order_status' => $orderStatus,
            'customer_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'course_name' => $courseName,
            'total_amount' => $orderTotal,
            'currency' => $orderCurrency,
            'payment_status' => $paymentStatus,
            'action' => $action,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Order processed and ticket {$action}",
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'action' => $action,
        ]);
    }
}
