# DukanAI Backend Architecture

## Core modules

- Auth: phone OTP login, custom bearer tokens, shop-aware access.
- Inventory: products plus stock movement ledger.
- Udhaar: customers plus debt ledger.
- Billing: invoices, invoice items, stock deduction, outstanding balance.
- AI assistant: Urdu query endpoint with real shop context and Claude fallback.

## Main API endpoints

- `POST /api/v1/auth/otp/request`
- `POST /api/v1/auth/otp/verify`
- `GET /api/v1/auth/me`
- `GET /api/v1/dashboard`
- `GET|POST /api/v1/products`
- `GET|POST /api/v1/stock-movements`
- `GET|POST /api/v1/customers`
- `GET /api/v1/customers/{customer}/ledger`
- `GET|POST /api/v1/debts`
- `GET|POST /api/v1/invoices`
- `POST /api/v1/ai/ask`

## Suggested next backend steps

- Replace debug OTP with SMS provider integration.
- Add roles and permissions for cashier/manager.
- Add purchase orders and supplier module.
- Add queued alert jobs for low-stock notifications.
- Add printable receipt/PDF generation.
