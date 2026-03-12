# Usage Guide

## Accounts and profiles

Each account has its own:

- invoices
- clients
- reusable catalog items
- issuer profile
- PB allowance tracking

Create a user account from the registration screen, then update the issuer data in `My profile`.

## Creating invoices

When creating or editing an invoice, the form supports:

- client autofill from saved clients
- reusable catalog item selection
- PB allowance availability hints
- persistent invoice attachments

Issuer profile data is injected automatically from the logged-in user profile.

## Attachments

Attachments uploaded on the invoice form are stored with the invoice and can be:

- downloaded later
- removed from the edit screen
- included automatically when sending the invoice by email

## Sending invoices

Sending an invoice attaches:

- the generated invoice PDF
- every persisted invoice attachment
- a blind copy to the configured `MAIL_INVOICE_COPY_TO`

## PB allowances

`PB Allowances` summarizes annual usage per user for:

- Home Office Allowance
- Wellness Allowance
- Tech Allowance
- Book Allowance

The dashboard uses invoice line totals from the selected year.
