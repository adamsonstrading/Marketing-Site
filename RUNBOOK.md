# Email Campaign System - Final Runbook

## Complete Setup Commands (Run in Order)

### 1. Project Setup (Already Done)
```bash
# Laravel project already created
# Dependencies already installed
# Migrations already run
```

### 2. Configure Environment
```bash
# Update .env file with SendGrid settings
# Replace YOUR_SENDGRID_API_KEY_HERE with actual API key
# Update MAIL_FROM_ADDRESS with verified email
```

### 3. Seed Sample Data
```bash
php artisan db:seed --class=SenderSeeder
```

### 4. Start Queue Worker (Terminal 1)
```bash
php artisan queue:work --queue=emails
```

### 5. Start Development Server (Terminal 2)
```bash
php artisan serve
```

### 6. Access Application
Open browser to: http://127.0.0.1:8000/campaign

## Testing Steps

### 1. Test with Sample Data
1. Go to http://127.0.0.1:8000/campaign
2. Fill out the form:
   - Campaign Name: "Test Campaign"
   - Subject: "Test Email"
   - Message: "<h1>Hello!</h1><p>This is a test email.</p>"
   - Recipients: Enter 2-3 test email addresses (one per line)
3. Click "Create Campaign & Start Sending"
4. Watch the status dashboard update in real-time

### 2. Verify Queue Processing
```bash
# Check if jobs are being processed
# Look for log messages in the queue worker terminal
# Check database for recipient status updates
```

### 3. Test API Endpoints
```bash
# Test sender listing
curl http://127.0.0.1:8000/api/senders

# Test campaign status (replace {id} with actual campaign ID)
curl http://127.0.0.1:8000/api/campaigns/{id}/status
```

## Production Deployment Commands

### 1. Environment Setup
```bash
# Update .env for production
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
# Add MySQL credentials
```

### 2. Install Production Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Cache Configuration
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Run Migrations
```bash
php artisan migrate --force
```

### 5. Set Up Supervisor (Linux)
```bash
# Create supervisor config
sudo nano /etc/supervisor/conf.d/email-worker.conf

# Add the configuration from SETUP_GUIDE.md
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start email-worker:*
```

### 6. Restart Services
```bash
php artisan queue:restart
# Restart web server (nginx/apache)
```

## Monitoring Commands

### Check Queue Status
```bash
php artisan queue:work --once
```

### Monitor Logs
```bash
tail -f storage/logs/laravel.log
```

### Database Queries
```sql
-- Check campaign status
SELECT status, COUNT(*) FROM recipients GROUP BY status;

-- Check recent activity
SELECT email, status, sent_at FROM recipients ORDER BY updated_at DESC LIMIT 10;
```

## Troubleshooting Commands

### Reset Queue
```bash
php artisan queue:flush
php artisan queue:restart
```

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Test Email Sending
```bash
php artisan tinker
# Then run:
dispatch(new App\Jobs\SendRecipientJob(1));
```

## Security Checklist

- [ ] Update SendGrid API key in .env
- [ ] Verify sender email addresses
- [ ] Set up HTTPS in production
- [ ] Configure proper firewall rules
- [ ] Monitor queue worker logs
- [ ] Set up log rotation
- [ ] Implement backup strategy
- [ ] Test rate limiting

## Performance Checklist

- [ ] Use Redis for queues (production)
- [ ] Set up database indexes
- [ ] Configure CDN for static assets
- [ ] Implement caching strategy
- [ ] Monitor memory usage
- [ ] Set up monitoring alerts

## Final Notes

1. **Always test with Mailtrap first** before using SendGrid
2. **Monitor the queue worker** - it must stay running
3. **Check logs regularly** for errors
4. **Verify email authentication** with SendGrid
5. **Test rate limiting** to avoid API blocks
6. **Backup database regularly** in production

The system is now ready for use! 🚀
