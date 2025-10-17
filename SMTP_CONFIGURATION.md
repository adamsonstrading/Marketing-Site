# SMTP Configuration Summary

## Your Business SMTP Accounts

### 1. Business Loans 4U
- **Sender ID:** 6
- **Name:** Business Loans 4U
- **Email:** info@businessloans4u.co.uk
- **SMTP Host:** smtp.ionos.co.uk
- **SMTP Port:** 587
- **Username:** info@businessloans4u.co.uk
- **Password:** Adamsons@514
- **Encryption:** TLS
- **From Name:** Business Loans 4U
- **From Address:** info@businessloans4u.co.uk

### 2. Finda Property
- **Sender ID:** 7
- **Name:** Finda Property
- **Email:** no-reply@findaproperty.io
- **SMTP Host:** smtp.ionos.co.uk
- **SMTP Port:** 587
- **Username:** no-reply@findaproperty.io
- **Password:** Adamsons@514
- **Encryption:** TLS
- **From Name:** Finda Property
- **From Address:** no-reply@findaproperty.io

## How to Use

1. **Access Campaign Page:** http://127.0.0.1:8000/campaign
2. **Select Sender:** Choose either "Business Loans 4U" or "Finda Property" from the dropdown
3. **Create Campaign:** Fill in your campaign details
4. **Add Recipients:** Use manual entry or CSV upload
5. **Send Emails:** Emails will be sent using the selected sender's SMTP configuration

## Features Available

- ✅ **Multiple SMTP Accounts:** Switch between different business email accounts
- ✅ **CSV Import:** Upload recipient lists from CSV files
- ✅ **Excel Support:** Convert Excel files to CSV for import
- ✅ **Real-time Status:** Monitor email sending progress
- ✅ **Queue Processing:** Reliable email delivery with retry logic
- ✅ **Password Security:** SMTP passwords are encrypted in the database

## API Endpoints

- **GET /api/senders** - List all available senders
- **POST /api/campaigns** - Create new email campaign
- **GET /api/campaigns/{id}/status** - Check campaign status
- **GET /sample-csv** - Download sample CSV template
