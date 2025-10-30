# Quick Start Guide - New Features

Welcome to the updated Caseer Academy Support System! Here's how to use the new features.

---

## 🎯 Dashboard Overview

**Location:** Login → Dashboard (default homepage)

### What You'll See:

1. **4 Stat Cards** at the top:
   - Total Tickets (with breakdown)
   - Tickets Today
   - Total Customers
   - Support Team Size

2. **Tickets by Status Chart**:
   - Visual pie chart showing ticket distribution
   - Color-coded by status

3. **Recent Tickets Table**:
   - Latest 5 tickets
   - Click eye icon 👁️ to view details

**Refreshes:** Real-time data on every page load

---

## 👥 Managing Support Team Members

**Location:** System → Team Members

### Adding a New User:

1. Click **+ New User** (top right)
2. Fill in:
   - Full Name
   - Email Address
   - Password (min 8 characters)
   - Confirm Password
3. Click **Create**
4. User can now login!

### Editing a User:

1. Find user in list
2. Click **Edit** (pencil icon)
3. Update name or email
4. Leave password blank to keep current
5. Or enter new password to change it
6. Click **Save**

### Resetting a Password:

1. Find user in list
2. Click **Reset Password** (key icon 🔑)
3. Enter new password twice
4. Click **Reset Password**
5. Done! User should be notified of new password

### Deleting a User:

1. Find user in list
2. Click **Delete** (trash icon 🗑️)
3. Confirm deletion
4. **Note:** You cannot delete your own account!

### Copying Email:

- Click any email address to copy to clipboard
- Great for sending login credentials

---

## 🚀 Deploying to Production

### Quick Deploy (On Server):

```bash
cd /var/www/caseer-support
./deploy.sh
```

That's it! The script handles everything:
- Puts site in maintenance mode
- Updates code
- Installs dependencies
- Runs migrations
- Clears caches
- Restarts services
- Brings site back online

### First Time Setup:

1. Read `DEPLOYMENT.md` (comprehensive guide)
2. Copy `.env.production.example` to `.env`
3. Configure:
   - Database credentials
   - APP_URL
   - CASEER_API_URL and SECRET
   - Mail settings
4. Run deployment script
5. Create admin user: `php artisan make:filament-user`

---

## 📊 Dashboard Tips

### Understanding Stats:

- **Total Tickets**: Shows all-time total + breakdown
- **Tickets Today**: Only tickets created in last 24h
- **Customers**: Total registered customers
- **Support Team**: Number of admin users

### Using Recent Tickets:

- Click ID or subject to view full details
- Status badges show current state
- Priority badges show urgency
- "Since" column shows age (e.g., "2 hours ago")

### Chart Insights:

- **Red** = Open (needs attention)
- **Yellow** = Pending (in progress)
- **Green** = Resolved (completed)
- **Gray** = Closed (archived)

---

## 🔐 Security Features

### User Management Security:

- ✅ All passwords automatically hashed
- ✅ Cannot delete your own account
- ✅ Cannot bulk delete your own account
- ✅ Email addresses must be unique
- ✅ Passwords require 8+ characters
- ✅ Password confirmation required

### Production Security:

- ✅ SSL/HTTPS configured
- ✅ Debug mode disabled
- ✅ Security headers set
- ✅ Database credentials secured
- ✅ API secrets encrypted

---

## 🎓 Common Tasks

### Creating Multiple Users:

```bash
# Quick way to create users from command line
php artisan make:filament-user
# Follow prompts for each user
```

### Checking Who's Online:

Currently not available, but users are listed in:
**System → Team Members**

### Assigning Tickets to Team:

1. Go to **Support → Tickets**
2. Click ticket to edit
3. Select user from "Assigned To" dropdown
4. Save

---

## 🐛 Troubleshooting

### Dashboard Not Showing Data:

```bash
php artisan cache:clear
php artisan config:clear
php artisan filament:optimize
```

### User Creation Fails:

- Check email is unique
- Ensure password is 8+ characters
- Verify passwords match

### Deployment Script Fails:

- Check you're in project root
- Verify PHP version (8.2+)
- Check .env file exists
- Review script output for specific error

### Can't Login After Creating User:

- Verify email is correct
- Check password was saved (check storage/logs/laravel.log)
- Try password reset from login screen

---

## 📞 Quick Reference

### File Locations:

- **Deployment Script:** `deploy.sh`
- **Environment Template:** `.env.production.example`
- **Deployment Guide:** `DEPLOYMENT.md`
- **Feature Details:** `FEATURES_UPDATE.md`
- **Application Logs:** `storage/logs/laravel.log`

### Important URLs:

- **Admin Panel:** `https://your-domain.com/admin`
- **Login:** `https://your-domain.com/admin/login`
- **Dashboard:** `https://your-domain.com/admin` (after login)
- **Team Members:** `https://your-domain.com/admin/users`

### Commands:

```bash
# Start development server
php artisan serve

# Create admin user
php artisan make:filament-user

# Deploy to production
./deploy.sh

# Clear all caches
php artisan optimize:clear

# Run migrations
php artisan migrate

# Run tests
php artisan test
```

---

## 💡 Best Practices

### User Management:

1. Use strong passwords (12+ characters recommended)
2. Use real email addresses for password resets
3. Create separate accounts for each team member
4. Delete users who leave the team
5. Regularly review team member list

### Dashboard:

1. Check dashboard daily for ticket overview
2. Monitor "Tickets Today" for workload
3. Use recent tickets for quick triage
4. Watch for status distribution patterns

### Deployment:

1. Always test in staging first
2. Backup database before deploy
3. Run deploy script during low traffic
4. Monitor logs after deployment
5. Test critical features post-deploy

---

## 🎉 What's Next?

Now that you have these features, consider:

1. **Add More Team Members**
   - Create accounts for all support staff
   - Assign tickets to specific team members

2. **Monitor Dashboard Daily**
   - Check ticket trends
   - Identify busy periods
   - Plan resources accordingly

3. **Deploy to Production**
   - Follow DEPLOYMENT.md guide
   - Use deploy.sh script
   - Set up monitoring

4. **Customize Further**
   - Add more widgets
   - Create custom reports
   - Implement notifications

---

**Need Help?**

- 📖 Read `DEPLOYMENT.md` for deployment
- 📖 Read `FEATURES_UPDATE.md` for technical details
- 📝 Check `storage/logs/laravel.log` for errors
- 🔧 Run `php artisan about` for system info

---

**Version:** 2.1  
**Last Updated:** October 12, 2025  
**Status:** Production Ready ✅

