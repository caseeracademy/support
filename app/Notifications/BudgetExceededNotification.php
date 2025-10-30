<?php

namespace App\Notifications;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetExceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Budget $budget,
        protected array $alerts,
        protected string $alertType = 'multiple'
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail']; // Can be extended to include SMS, Slack, etc.
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting("Hello {$notifiable->name},")
            ->line($this->getMainMessage());

        // Add alert details
        foreach ($this->alerts as $alert) {
            $mailMessage->line($this->formatAlertForEmail($alert));
        }

        $mailMessage->action('View Budget Details', url("/admin/budgets/{$this->budget->id}/edit"))
            ->line('Please review and take appropriate action to stay within budget limits.')
            ->salutation('Best regards, Finance Team');

        // Set priority based on alert severity
        if ($this->hasExceededAlerts()) {
            $mailMessage->priority(1); // High priority
        }

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => $this->getTitle(),
            'message' => $this->getDatabaseMessage(),
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'alert_type' => $this->alertType,
            'alerts' => $this->alerts,
            'severity' => $this->getSeverity(),
            'action_url' => "/admin/budgets/{$this->budget->id}/edit",
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'alerts' => $this->alerts,
            'alert_type' => $this->alertType,
        ];
    }

    protected function getSubject(): string
    {
        $budgetName = $this->budget->name;

        if ($this->hasExceededAlerts()) {
            return "🚨 Budget Exceeded: {$budgetName}";
        } elseif ($this->hasApproachingAlerts()) {
            return "⚠️ Budget Alert: {$budgetName}";
        } else {
            return "📊 Budget Update: {$budgetName}";
        }
    }

    protected function getTitle(): string
    {
        if ($this->hasExceededAlerts()) {
            return 'Budget Exceeded';
        } elseif ($this->hasApproachingAlerts()) {
            return 'Budget Alert';
        } else {
            return 'Budget Update';
        }
    }

    protected function getMainMessage(): string
    {
        $alertCount = count($this->alerts);
        $budgetName = $this->budget->name;

        if ($alertCount === 1) {
            $alert = $this->alerts[0];

            return "Your budget '{$budgetName}' has a category that needs attention: {$alert['category']} is at {$alert['percentage']}% usage.";
        } else {
            return "Your budget '{$budgetName}' has {$alertCount} categories that need attention.";
        }
    }

    protected function getDatabaseMessage(): string
    {
        $alertSummary = [];

        foreach ($this->alerts as $alert) {
            $alertSummary[] = "{$alert['category']}: {$alert['percentage']}% used";
        }

        return implode(', ', $alertSummary);
    }

    protected function formatAlertForEmail(array $alert): string
    {
        $category = $alert['category'];
        $percentage = number_format($alert['percentage'], 1);

        if ($alert['type'] === 'exceeded_limit') {
            $overspent = '$'.number_format($alert['overspent'], 2);

            return "• {$category}: {$percentage}% used (overspent by {$overspent})";
        } else {
            $remaining = '$'.number_format($alert['remaining'], 2);

            return "• {$category}: {$percentage}% used ({$remaining} remaining)";
        }
    }

    protected function hasExceededAlerts(): bool
    {
        return collect($this->alerts)->contains('type', 'exceeded_limit');
    }

    protected function hasApproachingAlerts(): bool
    {
        return collect($this->alerts)->contains('type', 'approaching_limit');
    }

    protected function getSeverity(): string
    {
        if ($this->hasExceededAlerts()) {
            return 'critical';
        } elseif ($this->hasApproachingAlerts()) {
            return 'warning';
        } else {
            return 'info';
        }
    }

    /**
     * Determine if the notification should be sent.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        // Don't send if no alerts
        if (empty($this->alerts)) {
            return false;
        }

        // Don't send if budget is not active
        if (! $this->budget->is_active) {
            return false;
        }

        return true;
    }

    /**
     * Get the notification's channels.
     */
    public function broadcastType(): string
    {
        return 'budget.alert';
    }

    /**
     * Create notification instances for different alert types
     */
    public static function createForBudgetAlerts(Budget $budget, array $alerts): array
    {
        $notifications = [];

        // Group alerts by type
        $exceededAlerts = collect($alerts)->where('type', 'exceeded_limit')->all();
        $approachingAlerts = collect($alerts)->where('type', 'approaching_limit')->all();

        // Create separate notifications for different severity levels
        if (! empty($exceededAlerts)) {
            $notifications[] = new static($budget, $exceededAlerts, 'exceeded');
        }

        if (! empty($approachingAlerts)) {
            $notifications[] = new static($budget, $approachingAlerts, 'approaching');
        }

        return $notifications;
    }

    /**
     * Send alerts to all budget stakeholders
     */
    public static function sendBudgetAlerts(Budget $budget, array $alerts): void
    {
        if (empty($alerts)) {
            return;
        }

        $notifications = static::createForBudgetAlerts($budget, $alerts);

        // Send to budget creator
        if ($budget->createdBy) {
            foreach ($notifications as $notification) {
                $budget->createdBy->notify($notification);
            }
        }

        // TODO: Send to additional stakeholders (managers, finance team, etc.)
        // This could be configurable per budget or organization
    }
}
