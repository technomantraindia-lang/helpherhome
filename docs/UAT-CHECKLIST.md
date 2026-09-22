# Helper Home UAT Checklist

Use a staging database and staging storage. Do not use production data for exploratory testing.

## Authentication and authorization

- [ ] Valid admin login succeeds.
- [ ] Invalid password and unknown email fail without revealing account details.
- [ ] Inactive users cannot log in or access admin routes.
- [ ] Logout and session expiry work.
- [ ] Super Admin, Admin, and Staff permissions are verified by direct URL and form request.

## Operations

- [ ] Create and verify a worker, including services, duty type, five references, documents, interview, availability, resume, and registration form.
- [ ] Create a customer with multiple requirements and complete household, accommodation, working, and preference sections.
- [ ] Match, shortlist, assign, start, complete, and replace a worker.
- [ ] Create, sign, generate, preview, print, download, and share an agreement.
- [ ] Create invoices with multiple items, discounts, CGST/SGST, IGST, and round-off.
- [ ] Record full, partial, pending, failed, cancelled, and overpayment cases.
- [ ] Verify receipts, document versions, reports, exports, and global search.

## Website

- [ ] Homepage, contact, service-page, and modal enquiries submit to the Laravel API.
- [ ] Services and duty types load dynamically.
- [ ] Service preselection is correct on Maid and Cook pages.
- [ ] Success, validation, rate-limit, network, and unavailable-API states are clear.
- [ ] Call, WhatsApp, phone, email, and configured social links work.
- [ ] Test at 320, 375, 390, 430, 768, 1024, 1366, 1440, and 1920 px.

## Release checks

- [ ] `php artisan test` passes.
- [ ] `npm run verify` passes.
- [ ] `npm run build` passes.
- [ ] `APP_DEBUG=false` is set in production.
- [ ] HTTPS, backups, storage permissions, queue, and scheduler decisions are documented.
