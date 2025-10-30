# WhatsApp Customer Care System — Filament & Laravel Project Plan

**Version:** 2.0
**Updated:** October 2025

---

## Table of contents
1. [Purpose](#purpose)
2. [System Overview](#system-overview)
3. [Conversation Logic & 24-Hour Service Window](#conversation-logic--24-hour-service-window)
4. [Architecture](#architecture)
5. [Core Modules (Filament)](#core-modules-filament)
6. [Message Flow](#message-flow)
7. [Realtime Setup](#realtime-setup)
8. [Security](#security)
9. [WhatsApp Cloud API Integration](#whatsapp-cloud-api-integration)
10. [Suggested Sprints](#suggested-sprints)
11. [Deliverables](#deliverables)
12. [Future Enhancements](#future-enhancements)

---

## Purpose
A Laravel-based system to manage WhatsApp Cloud API interactions for student and customer care. The system, built with a **Filament admin panel**, will support viewing sent messages, creating tickets, and replying in real-time.

---

## System Overview

**Main Features**
- Filament-based Real-time WhatsApp Chat Panel
- Customer and Ticket Management Resources
- Sent Message Logs & Templates Viewer
- API Keys & Settings Page

**Tech Stack**
- **Backend:** Laravel 11+
- **Database:** MySQL / PostgreSQL
- **Admin Panel:** Filament PHP (TALL Stack)
- **Realtime:** Laravel Echo + Pusher / Soketi / Ably
- **External API:** Meta WhatsApp Cloud API

---

## Conversation Logic & 24-Hour Service Window

The WhatsApp Cloud API operates on a 24-hour "service window" that begins whenever a student sends a message.

| Scenario | Type | Window Status |
|---|---|---|
| You send a payment notification | Business-initiated | N/A (Starts a new conversation) |
| Student replies | User-initiated | **Opens** a 24-hour service window. |
| You reply within 24h | Service message / free-form | Allowed inside the open service window. |
| 24h passes without a student reply | Window expires | The service window is now **closed**. |

**Application Logic**
To manage conversations effectively, the system must track the `last_user_message_at` timestamp for each conversation.
- If the 24-hour window is **open**, agents can send free-form messages.
- If the window is **expired**, the free-form composer should be locked, and agents must use a pre-approved **Message Template** to re-initiate the conversation.

---

## Architecture

**Components**
1. **Laravel Backend** (API + Business Logic)
   - Authentication & Authorization
   - Webhook endpoint to receive WhatsApp events
   - Queued jobs for processing outbound messages
   - APIs for core functionalities
2. **Database** (MySQL/Postgres)
3. **Filament Admin Panel** (UI)
   - Built on the TALL stack (Tailwind CSS, Alpine.js, Livewire, Laravel).
   - Provides all user interfaces for chat, tickets, and settings.
4. **Realtime Broadcasting**
   - Laravel broadcasting -> Pusher / Soketi / Ably -> Client (Laravel Echo)
5. **External Services**
   - WhatsApp Cloud API (Meta)

---

## Core Modules (Filament)

### Filament Dashboard
- An overview page with key stats: active chats, unread messages, open tickets, and recent errors.

### Customer Resource
- A searchable and filterable list of all customers (students).
- A customer profile page showing their details, conversation history, and associated tickets.

### Ticket Resource
- A standard CRUD interface for managing support tickets.
- Features include changing status (open/pending/resolved), assigning agents, and setting priority.

### WhatsApp Chat Page (Custom Filament Page)
- A dedicated, full-screen Livewire component for real-time chat.
- **Inbox View:** Lists all conversations, sorted by the most recent activity.
- **Conversation View:** Displays message history, delivery/read receipts, and customer details.
- **Composer:** A rich text editor with options for sending templates, attachments, and quick replies.

### Settings Page (Custom Filament Page)
- A secure page for managing WhatsApp Cloud API credentials (API Token, Phone Number ID, WABA ID).
- Includes a **Test Connection** button to verify API credentials.
- Includes a **Refresh Templates** button to fetch and cache the latest message templates from the API.

### Template Viewer
- A read-only resource or page listing all available and active message templates.
- Shows template name, category, language, and body with variable placeholders.

---

## Message Flow

### Inbound (Customer -> App)
1. WhatsApp Cloud API sends a message event to the `/webhook/whatsapp` endpoint.
2. The `WebhookController` validates the request signature.
3. The system identifies or creates the `Customer` and `Conversation` records.
4. A new `Message` is stored with `direction = inbound`.
5. An `IncomingMessage` event is broadcast via Laravel Echo to subscribed agents.

### Outbound (Agent -> Customer)
1. An agent sends a message from the Filament chat composer.
2. An API request is sent to the backend, which stores the `Message` with `status = queued`.
3. A `SendWhatsAppMessageJob` is dispatched to a queue worker.
4. The job makes a `POST` request to the WhatsApp Cloud API.
5. The message status is updated to `sent`. Subsequent webhook events will update it to `delivered` or `read`.

---

## Realtime Setup

**Recommended:** Laravel Echo + Pusher for a quick and managed setup.
**Alternatives:** Soketi (self-hosted) or Ably.

**Example Broadcast Event (Laravel):**
```php
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class IncomingMessage implements ShouldBroadcast
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('conversations.' . $this->message->conversation_id);
    }
}

Rules:
1. Always track your job in the progress.md file to know what we did 
2. always make sure to run tests, everytime you code run tests on the laravel either pest or unit testing and also use the browser mcp to know more info. 
3. you will always need to be clean, do not create mess not too much files if you need to test something remove after you use that.
4. always know your goals and use the initial plan.
5. create modules.md file to know all the features the app has and add subtasks and track what we did and what is upcoming and what is failed and what is postponed and what is not needed