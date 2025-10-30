# Financial ERP - Quick Start Guide

**Last Updated:** October 13, 2025  
**Status:** Production Ready ✅

---

## 🚀 Getting Started

### Access Financial Features
Login to admin panel: `http://localhost:8000/admin`

### Main Navigation
- **Finance → Transactions** - Manage all financial transactions
- **Finance → Budgets** - Create and track budgets
- **Finance → Invoices** - Manage customer invoices
- **Finance → Financial Reports** - Generate comprehensive reports
- **Finance → Tax Reports** - Tax compliance and reporting
- **Finance → Tax Categories** - Manage tax classifications

---

## 📊 Dashboard Widgets

Your dashboard now includes 9 financial widgets:

1. **Financial Overview** - Monthly income, expenses, profit
2. **Recent Transactions** - Latest 10 transactions
3. **Payment Approval** - Tickets awaiting payment approval
4. **Income vs Expenses** - Monthly comparison chart
5. **Category Breakdown** - Expense distribution
6. **Profit Trend** - Daily profit tracking
7. **Cash Flow** - Multi-period cash flow analysis
8. **Unpaid Invoices** - Outstanding invoices table
9. **Revenue Forecast** - Predictive revenue projection

---

## ⚡ Quick Actions

### Import Transactions from CSV
1. Go to **Finance → Transactions**
2. Click **"Import CSV"** in header
3. Download template if needed
4. Upload your CSV file
5. Choose background processing for large files

### Generate Invoice from Ticket
1. Open a ticket with payment amount
2. Click **"Generate Invoice"** in header
3. Review/edit invoice details
4. Submit to create invoice
5. Invoice automatically links to ticket and customer

### Create Monthly Budget
1. Navigate to **Finance → Budgets**
2. Click **"Create"**
3. Set period type (monthly/quarterly/yearly)
4. Allocate amounts to expense categories
5. Click **"Activate"** to start tracking

### Run Tax Report
1. Go to **Finance → Tax Reports**
2. Select tax year and quarter
3. Review deductible expenses by category
4. Export in desired format:
   - Standard PDF
   - IRS Schedule C
   - TurboTax format
   - QuickBooks format

---

## 🎯 Common Workflows

### End of Month Closing
1. Review **Payment Approval** widget for pending payments
2. Run **Financial Reports** for the month
3. Export reports to PDF/Excel
4. Update budget spent amounts (or wait for automatic daily update)
5. Review budget alerts

### Quarterly Tax Preparation
1. Open **Tax Reports** page
2. Select quarter (Q1, Q2, Q3, or Q4)
3. Review deductible expenses by category
4. Check for deduction opportunities
5. Export in IRS Schedule C format
6. Submit to accountant

### Customer Payment Follow-up
1. Check **Unpaid Invoices** widget
2. Filter by overdue status
3. Click **"Send Reminder"** for individual invoices
4. Or use bulk action to send multiple reminders
5. Track reminder history in invoice metadata

### Budget Monitoring
1. Navigate to **Finance → Budgets**
2. Click **"Update Spent"** to refresh (or automatic daily)
3. Click **"Check Alerts"** to see overspend warnings
4. Review category-level spending
5. Adjust allocations if needed

---

## 📤 Export Options

### Financial Reports
- **Profit & Loss** - PDF download
- **Cash Flow Statement** - PDF download
- **Transaction Summary** - PDF download
- **Customer Payments** - PDF download
- **Excel Export** - All transactions with formatting

### Tax Reports
- **Standard Format** - PDF
- **IRS Schedule C** - Tax form format
- **TurboTax** - Import-ready format
- **QuickBooks** - Accounting software format

---

## 🤖 Automation Commands

### Run Invoice Automation
```bash
# Process all invoice automation tasks
php artisan invoices:process-automation

# Preview what would be processed (no changes)
php artisan invoices:process-automation --dry-run
```

### Enable Scheduled Tasks
Add to your crontab:
```bash
* * * * * cd /Users/caseer/Sites/whatsapp && php artisan schedule:run >> /dev/null 2>&1
```

This enables:
- Daily invoice automation (2:00 AM)
- Daily budget updates (3:00 AM)
- Daily payment matching (4:00 AM)
- Monthly financial summaries (1st of month, 5:00 AM)

### Start Queue Worker
For background jobs (imports, invoice generation, reminders):
```bash
php artisan queue:work --queue=imports,invoices,notifications,reports
```

---

## 📋 CSV Import Format

### Required Columns
- `type` - "income" or "expense"
- `amount` - Numeric value (e.g., 150.50)
- `title` - Description
- `date` - YYYY-MM-DD format

### Optional Columns
- `currency` - USD, EUR, GBP (default: USD)
- `description` - Detailed notes
- `category` - Category name (auto-created if new)
- `payment_method` - Payment method name
- `status` - pending/completed/cancelled/refunded
- `reference` - External reference number

### Download Template
Click **"Download Template"** in Transactions → Import CSV dialog

---

## 🎨 Financial Dashboard Filters

### Widgets with Filters
- **Income vs Expenses:** 3/6/12 months
- **Category Breakdown:** This month/quarter/year
- **Profit Trend:** 7/30/90 days
- **Cash Flow:** 3/6/12 months
- **Revenue Forecast:** Linear/Moving Avg/Seasonal

### Report Filters
- Date ranges
- Transaction types (income/expense)
- Categories
- Payment methods
- Customers
- Tax categories

---

## 💰 Budget Alerts

Automatic alerts when:
- **80% of budget used** - Warning notification
- **100% of budget used** - Critical notification
- **Budget exceeded** - Overspend alert

Configure per category:
- Enable/disable 80% alert
- Enable/disable 100% alert
- Alerts sent max once per 24 hours

---

## 📧 Notifications

You'll receive notifications for:
- Budget alerts (approaching/exceeded limits)
- Invoice reminders sent
- Transaction import completed
- Invoice generation completed
- Monthly financial summaries
- Payment approvals

---

## 🔍 Quick Tips

1. **Use Quick Presets** - Most pages have preset buttons for common date ranges
2. **Background Processing** - Enable for large CSV imports
3. **Auto-Categorization** - System suggests tax categories based on keywords
4. **Bulk Actions** - Select multiple records for batch operations
5. **Export Selected** - Export only specific transactions
6. **Real-Time Tracking** - Budget spent amounts update automatically
7. **Smart Matching** - System auto-matches payments to invoices

---

## ⚠️ Important Notes

1. **Queue Worker Required** - For background jobs to process
2. **Scheduler Required** - For daily/monthly automation
3. **Tax Categories** - 10 defaults created, customize as needed
4. **Company Info** - Update in InvoicePdfService for PDF invoices
5. **Backup Regularly** - Financial data is critical

---

## 📞 Need Help?

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Clear Caches
```bash
php artisan optimize:clear
php artisan filament:optimize
```

### Test Scheduler
```bash
php artisan schedule:list
php artisan schedule:test
```

### Verify Installation
```bash
php artisan about
php artisan route:list --name=financial
```

---

## 🎓 Best Practices

1. **Run Automation Daily** - Either via cron or manually
2. **Review Budgets Weekly** - Keep budgets updated
3. **Export Reports Monthly** - For record keeping
4. **Categorize Transactions** - For accurate tax reporting
5. **Send Invoice Reminders** - Before due dates
6. **Monitor Dashboard** - Check widgets daily
7. **Use Bulk Actions** - For efficiency

---

**All features are now active and ready to use!**

Navigate to your dashboard to see the new financial widgets in action.





