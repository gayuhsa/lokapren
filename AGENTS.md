# AGENTS.md

## Project

Indonesian city-focused UMKM marketplace built with:

* **CodeIgniter 4 / PHP**
* **MariaDB**
* **CodeIgniter Shield**
* **OpenStreetMap + Leaflet.js**

## Features

* Storefront
* Seller blogs
* Catalog + chat with seller
* Store locator (OpenStreetMap + Leaflet.js)
* Simple seller dashboard
* Shopping/cart functionality
* Orders and order status
* Reviews and ratings
* Product search and filtering
* Product images
* Customer and seller profiles

---

## Architecture

* **CodeIgniter Shield is the only authentication and authorization system.**
* **MariaDB is the application's database.**
* Only two roles exist: `customer` and `seller`.
* There is **no admin role or admin system**.
* There is **no separate store entity**.
* A storefront is the public presentation of a seller.
* Products and seller content belong directly to the seller/user.
* Customer ↔ seller chat is handled by the application's own chat system.
* Seller location discovery uses OpenStreetMap + Leaflet.js.
* Follow existing project conventions and frontend patterns.

Do not introduce another authentication system, database service, store abstraction, or admin system without an explicit architecture change.

---

## Authentication & Authorization

* Always obtain the authenticated user through CodeIgniter Shield.
* Enforce authorization server-side.
* Verify resource ownership against the authenticated user.
* Never trust client-supplied `user_id`, `seller_id`, roles, permissions, or ownership fields.
* Sellers may only manage their own products, blogs, profile/location data, and seller resources.
* Chat conversations may only be accessed by their participants.
* Customers may only access their own private orders and account data.

---

## Database

* Use CodeIgniter Models, Query Builder, migrations, seeders, and transactions.
* All schema changes require migrations.
* Use MariaDB-compatible database behavior.
* Keep database credentials in environment configuration.
* Never commit secrets or production credentials.
* Do not blindly mass-assign request data.
* Server-controlled fields must be set by the application.

---

## Security

Treat all client input as untrusted.

* Validate input server-side.
* Use CSRF protection.
* Use Query Builder or bound parameters.
* Escape/sanitize user-generated content.
* Validate uploaded files.
* Never trust client-submitted prices, totals, order status, coordinates, or ownership.
* Calculate financial values server-side.
* Do not use floating-point arithmetic for money.
* Never expose credentials, environment variables, SQL, stack traces, or sensitive internals in production.
* Never execute uploaded files.

---

## Marketplace Rules

* Sellers own their products and seller content directly; there is no store ownership layer.
* Sellers cannot access or modify another seller's resources.
* Customers cannot access another customer's private orders or chats.
* Order ownership and status transitions must be validated server-side.
* Product prices and order totals must come from trusted application/database data.
* Seller location belongs to the seller/profile domain.
* Use Indonesian conventions for currency, addresses, phone numbers, dates, and UI where appropriate.
* Store machine-readable values and format them at the presentation layer.

---

## Development

Before changing code:

1. Inspect the existing project structure.
2. Check Shield configuration.
3. Check MariaDB configuration.
4. Inspect relevant models, migrations, controllers, routes, and frontend code.
5. Reuse existing functionality before adding dependencies.

For new features:

1. Update the data model/migrations if needed.
2. Update models/data access.
3. Implement business logic.
4. Add authentication/authorization and validation.
5. Implement the UI/API.
6. Add relevant tests.
7. Run relevant tests and static checks.

Keep changes focused. Avoid unrelated refactoring.

---

## Testing

Test security-sensitive behavior, especially:

* Unauthenticated access
* Customer/seller authorization
* Cross-seller resource access
* Resource ownership
* Chat participant access
* Order ownership and transitions
* Validation failures
* Shield authentication/authorization

At minimum:

* Seller A must not access or modify Seller B's products or seller content.
* Customer A must not access Customer B's private orders or chats.

---

## Non-Negotiable Invariants

1. Shield is the only authentication system.
2. MariaDB is the application database.
3. Only `customer` and `seller` roles exist.
4. There is no admin system.
5. There is no separate store entity.
6. Storefronts represent sellers directly.
7. Products and seller content belong directly to sellers.
8. Chat is handled by the application's own system.
9. Seller location uses OpenStreetMap + Leaflet.js.
10. Authorization and ownership checks happen server-side.
11. Client-submitted financial and ownership data is never trusted.
12. Schema changes use migrations.
13. Secrets stay out of source control.
14. Do not introduce a conflicting architecture without explicitly changing these rules.

