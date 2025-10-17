# Email Campaign System - Setup Guide

## Overview
This Laravel application provides a complete email campaign system with:
- Queue-based email sending
- Multiple SMTP sender support
- Real-time status tracking
- Bootstrap frontend interface
- SendGrid integration

## Prerequisites
- PHP 8.1+
- Composer
- MySQL/SQLite
- SendGrid account (or Mailtrap for testing)

## Quick Setup

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
Copy the settings from `env-config.txt` to your `.env` file:

```bash
# Database (already configured for SQLite)
DB_CONNECTION=sqlite

# Queue (already configured)
QUEUE_CONNECTION=database

# SendGrid SMTP Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_API_KEY_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-verified-email@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Seed Sample Data
```bash
php artisan db:seed --class=SenderSeeder
```

### 5. Start Queue Worker
```bash
php artisan queue:work --queue=emails
```

### 6. Start Development Server
```bash
php artisan serve
```

### 7. Access the Application
Open http://127.0.0.1:8000/campaign in your browser.

## Configuration Details

### SendGrid Setup
1. Create a SendGrid account
2. Generate an API key
3. Verify your sender email address
4. Replace `YOUR_SENDGRID_API_KEY_HERE` in `.env` with your actual API key
5. Update `MAIL_FROM_ADDRESS` with your verified email

### Alternative: Mailtrap Testing
For testing without sending real emails:
```bash
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
```

## API Endpoints

### Create Campaign
```
POST /api/campaigns
Content-Type: application/x-www-form-urlencoded

name=Campaign Name
subject=Email Subject
body=<h1>HTML Content</h1>
sender_id=1
recipients=email1@example.com
email2@example.com
```

### Get Campaign Status
```
GET /api/campaigns/{id}/status
```

### List Senders
```
GET /api/senders
```

### Create Sender
```
POST /api/senders
Content-Type: application/x-www-form-urlencoded

name=Sender Name
email=sender@example.com
smtp_host=smtp.sendgrid.net
smtp_port=587
smtp_username=apikey
smtp_password=API_KEY
smtp_encryption=tls
from_name=From Name
from_address=from@example.com
```

## Production Deployment

### 1. Environment Setup
```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false

# Use MySQL for production
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```

### 2. Queue Worker with Supervisor
Create `/etc/supervisor/conf.d/email-worker.conf`:
```ini
[program:email-worker]
command=php /path/to/project/artisan queue:work --name=email-worker --queue=emails --sleep=3 --tries=3
process_name=%(program_name)s_%(process_num)02d
numprocs=1
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/email-worker.log
```

### 3. Deploy Commands
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan queue:restart
```

## Security Features

### Rate Limiting
- Campaign creation: 5 requests per minute
- Status checking: 30 requests per minute
- Sender creation: 5 requests per minute

### Data Protection
- SMTP passwords encrypted using Laravel's encryption
- Input validation and sanitization
- Maximum 500 recipients per campaign
- Email validation

### Security Recommendations
1. Use HTTPS in production
2. Implement authentication for admin functions
3. Monitor queue worker logs
4. Set up proper firewall rules
5. Regular security updates

## Monitoring & Debugging

### Log Files
- Application logs: `storage/logs/laravel.log`
- Queue worker logs: `/var/log/email-worker.log` (production)

### Useful Commands
```bash
# Check queue status
php artisan queue:work --once

# Clear failed jobs
php artisan queue:flush

# Monitor queue
php artisan queue:monitor emails

# Test email sending
php artisan tinker
>>> dispatch(new App\Jobs\SendRecipientJob(1));
```

### Database Queries
```sql
-- Check campaign status
SELECT status, COUNT(*) FROM recipients WHERE campaign_id = ? GROUP BY status;

-- Check sender usage
SELECT s.name, COUNT(c.id) as campaigns FROM senders s 
LEFT JOIN campaigns c ON s.id = c.sender_id 
GROUP BY s.id;

-- Recent failed emails
SELECT email, last_error, attempt_count FROM recipients 
WHERE status = 'failed' 
ORDER BY updated_at DESC LIMIT 10;
```

## Troubleshooting

### Common Issues

1. **Emails not sending**
   - Check SendGrid API key
   - Verify sender email is authenticated
   - Check queue worker is running
   - Review logs for errors

2. **Queue worker not processing**
   - Restart queue worker: `php artisan queue:restart`
   - Check database connection
   - Verify queue table exists

3. **Rate limiting errors**
   - Wait for rate limit reset
   - Implement proper retry logic
   - Consider using Redis for better performance

4. **Database errors**
   - Run migrations: `php artisan migrate`
   - Check database permissions
   - Verify .env database settings

### Performance Optimization

1. **Use Redis for queues** (production)
2. **Implement job batching** for large campaigns
3. **Add database indexes** for frequently queried fields
4. **Use CDN** for static assets
5. **Implement caching** for sender data

## Support

For issues or questions:
1. Check the logs first
2. Review this documentation
3. Test with Mailtrap before SendGrid
4. Verify all environment settings
