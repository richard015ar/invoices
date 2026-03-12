# Architecture

## Application boundaries

The application is intentionally simple and keeps most workflows inside Laravel MVC, with a small service layer for invoice-specific operations.

## Main domains

- `Invoice`
- `InvoiceLine`
- `InvoiceAttachment`
- `Client`
- `CatalogItem`
- `IssuerProfile`
- `User`

## Data isolation

Business data is scoped by `user_id`. Controllers enforce ownership before reading or mutating records.

This applies to:

- invoices
- clients
- catalog items
- issuer profiles
- allowance reporting

## Service layer

`InvoiceAttachmentService`
- stores and removes persisted invoice attachments

`InvoiceDeliveryService`
- builds PDF binaries
- attaches persisted files
- sends invoice emails

`InvoiceViewDataFactory`
- resolves issuer and client display data for views and PDFs

## Rendering strategy

- Web views use Blade
- PDFs render the same invoice data through DomPDF
- Email delivery reuses the PDF generation path to avoid divergence

## Testing strategy

Feature tests cover end-to-end workflows:

- auth redirects
- invoice CRUD and delivery flows
- client/profile flows
- allowance reporting

Unit tests cover lower-level business behavior:

- invoice total recalculation
- attachment service behavior
- invoice display data resolution
