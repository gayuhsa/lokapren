 # AGENTS.md

 ## Project Overview

 This repository contains an Indonesian city-focused UMKM marketplace built with **CodeIgniter 4 (CI4)**.

 The application connects UMKM (Usaha Mikro, Kecil, dan Menengah) sellers with customers in an Indonesian city. Typical marketplace functionality includes:

 - UMKM/store profiles
- Product listings
- Product categories
- Product search and filtering
- Product images
- Shopping/cart functionality
- Orders and order status
- Customer and seller profiles
- Seller/store management
- Reviews and ratings
- City/area-based discovery
- Administrative moderation

 ### Core Stack

 - **Framework:** CodeIgniter 4
- **Language:** PHP
- **Database:** Supabase PostgreSQL
- **Authentication:** CodeIgniter Shield
- **Authorization:** CodeIgniter Shield
- **Frontend:** Use the existing project frontend conventions; do not introduce a new frontend framework without a clear requirement.
- **Database access:** CodeIgniter's database layer / Query Builder / Models
- **Deployment:** Follow the repository's existing deployment configuration.

---

 # Critical Architecture Rules

 ## 1\. CodeIgniter Shield Owns Authentication

 **Authentication is 100% handled by CodeIgniter Shield.**

 Do not implement a second authentication system.

 Shield is responsible for:

 - Login
- Logout
- Registration
- Password hashing
- Password verification
- Password reset
- Session-based authentication
- Remember-me functionality, where enabled
- User identities
- User status
- Roles
- Permissions
- Authorization checks

 Do **not** implement authentication through Supabase.

 Do not use:

 - Supabase Auth
- Supabase JWT authentication
- Supabase Auth users
- Custom password hashing
- Custom login sessions
- Custom authentication middleware when Shield already provides the functionality
- A second user/session system

 If a feature requires the current user, retrieve the authenticated user through Shield/CodeIgniter's authentication facilities.

 Example conceptually:

```
$auth = service('authentication');

if (! $auth->check()) {
    // User is not authenticated.
}
```

 Use the project's existing Shield configuration and APIs rather than creating parallel abstractions.

---

 # 2\. Supabase Is Only the Database

 Supabase is used as the application's **PostgreSQL database provider**.

 Treat Supabase as database infrastructure, not as an authentication provider.

 The application should communicate with Supabase primarily through CodeIgniter's database layer.

 Use:

 - CodeIgniter Models
- Query Builder
- Database connections
- Migrations
- Seeders
- Transactions

 Avoid coupling application logic directly to Supabase-specific APIs when normal PostgreSQL/CodeIgniter functionality is sufficient.

 ### Do not use Supabase for:

 - Authentication
- User login
- User sessions
- Password management
- Authorization
- Roles/permissions
- JWT authentication

 ### Database connection

 Keep database credentials in environment configuration.

 Never commit database credentials to the repository.

 Use `.env` for local development and the deployment platform's secret/environment-variable mechanism for production.

 Never hard-code:

```
DB_HOST
DB_DATABASE
DB_USERNAME
DB_PASSWORD
DB_PORT
```

 or equivalent credentials into PHP source code.

---

 # Authentication and User Model

 ## Shield Is the Source of Truth for Identity

 When a database record needs to reference a user, use the user identity managed by Shield.

 Do not create a second application-level authentication user table unless there is a concrete domain requirement for additional profile data.

 Shield's user records and identities remain the source of truth for authentication.

 Marketplace-specific information should be modeled separately.

 For example:

```
users / Shield tables
    |
    +-- seller_profile
    |
    +-- customer_profile
    |
    +-- admin-related data
```

 A seller profile may contain:

 - Store name
- Store description
- Store logo
- Address
- City
- District
- Phone number
- Business information
- Operating hours
- Delivery information

 But these are **domain/profile attributes**, not authentication credentials.

 Do not duplicate passwords, authentication state, or Shield identities in marketplace tables.

---

 # Roles and Permissions

 Use **CodeIgniter Shield roles and permissions** for access control.

 Typical roles may include:

```
admin
seller
customer
```

 Additional roles may be introduced when the domain requires them.

 Permissions should represent actions rather than merely pages.

 Examples:

```
products.create
products.update
products.delete
products.manage
orders.view
orders.manage
stores.manage
users.manage
reviews.moderate
```

 Prefer permission checks where appropriate:

```
if (! auth()->user()->can('products.manage')) {
    // deny access
}
```

 Do not create custom role/permission systems in application tables when Shield can represent the requirement.

 Do not rely solely on hidden UI elements for authorization.

 **Every protected operation must be authorized server-side.**

---

 # Marketplace Domain Rules

 ## Sellers and Stores

 A seller is an authenticated Shield user who has a marketplace seller profile/store.

 Do not assume that every authenticated user is a seller.

 A user may be:

 - A customer
- A seller
- An administrator
- More than one of these depending on the application's role configuration

 Store ownership must be enforced server-side.

 For example, a seller updating a product must only be able to update products belonging to their store.

 Never trust a submitted:

```
seller_id
store_id
user_id
```

 without verifying ownership or authorization.

---

 # Product Ownership

 Every product-management operation must verify that the authenticated seller has permission to manage the relevant store/product.

 Do not implement authorization like:

```
$product = $productModel->find($id);
$productModel->update($id, $data);
```

 without checking ownership/permissions when the endpoint is seller-restricted.

 The server must establish the relationship:

```
authenticated Shield user
        ↓
seller/store
        ↓
product
```

 before allowing mutations.

---

 # Orders

 Orders contain customer and seller/store relationships.

 Do not trust client-submitted ownership information.

 For example, never assume:

```
customer_id = POST customer_id
```

 is valid merely because it was supplied by the browser.

 The authenticated customer should normally be derived from Shield.

 Likewise, sellers should only be allowed to access orders associated with their stores.

 Order state transitions should be validated server-side.

 Avoid allowing arbitrary client-submitted status values.

 Prefer explicit domain transitions such as:

```
pending
confirmed
processing
ready
shipped
completed
cancelled
```

 The exact statuses should follow the existing application's schema and business requirements.

---

 # Database Guidelines

 ## PostgreSQL

 Supabase provides PostgreSQL.

 Use PostgreSQL-compatible SQL and CodeIgniter's database abstractions.

 Do not write database code assuming MySQL-specific behavior unless the project explicitly requires it.

 Be especially careful with:

 - UUIDs
- PostgreSQL enums
- JSON/JSONB
- timestamps
- indexes
- foreign keys
- unique constraints
- transactions
- case sensitivity
- PostgreSQL-specific SQL

 Prefer migrations for schema changes.

---

 # Migrations

 Database schema changes must be represented as CodeIgniter migrations.

 Do not manually modify production tables without also adding the corresponding migration to the repository.

 Migrations should:

 - Be deterministic
- Have clear names
- Define indexes
- Define foreign keys where appropriate
- Define sensible nullability
- Define timestamps consistently
- Be reversible when practical

 Example structure:

```
public function up()
{
    $this->forge->addField([
        'id' => [
            'type'           => 'UUID',
            'null'           => false,
        ],
        // ...
    ]);

    // indexes / keys / table creation
}
```

 Follow the actual project's database conventions rather than blindly copying this example.

---

 # Models

 Use CodeIgniter Models for domain data access.

 Models should define:

 - `$table`
- `$primaryKey`
- `$allowedFields`
- `$returnType`
- Validation rules where appropriate

 Do not use:

```
$model->save($request->getPost());
```

 for arbitrary request payloads.

 Explicitly control which fields can be written.

 Sensitive or server-controlled fields should never be mass-assigned from user input.

 Examples include:

 - `user_id`
- `seller_id`
- `store_id`
- `created_at`
- `updated_at`
- order totals
- payment state
- authorization-related fields

---

 # Controllers

 Controllers should coordinate HTTP requests and domain operations.

 Avoid putting large amounts of business logic directly inside controllers.

 Prefer:

```
Controller
    ↓
Service / domain logic
    ↓
Model / database
```

 when the operation has meaningful business complexity.

 Controllers should handle:

 - Request validation
- Authentication checks
- Authorization checks
- Calling application/domain logic
- Choosing an appropriate HTTP response

 Avoid:

 - Large SQL queries scattered through controllers
- Reimplementing Shield authentication
- Business rules duplicated across multiple controllers
- Trusting browser-supplied ownership fields

---

 # Validation

 Validate all user-controlled input server-side.

 This includes:

 - Product names
- Prices
- Stock quantities
- Store information
- Addresses
- Search/filter parameters
- Reviews
- Order-related input
- Uploaded files

 Client-side validation is useful for UX but is never a security boundary.

 Use CodeIgniter validation facilities and existing project conventions.

---

 # Prices and Money

 Never use floating-point arithmetic for financial calculations.

 Prefer integer minor units where appropriate, for example:

```
15000 = Rp15.000
```

 Use the application's established money representation consistently.

 Do not calculate order totals from values submitted by the browser.

 The server should calculate:

```
subtotal
discount
shipping
tax/fees, if applicable
total
```

 from trusted database/application data.

---

 # Indonesian Localization

 The marketplace targets Indonesian users.

 Use Indonesian conventions where appropriate:

 - Currency: Indonesian Rupiah (`IDR` / `Rp`)
- Local address structures
- Indonesian phone-number formats
- Indonesian date/time conventions
- Indonesian UI terminology
- Indonesian city/district/area terminology

 Do not hard-code locale-specific assumptions into reusable infrastructure unless required.

 When formatting money for display, use a consistent Indonesian format such as:

```
Rp15.000
Rp125.000
Rp1.250.000
```

 Database values should remain machine-friendly and should not store presentation-formatted currency strings.

---

 # Time and Dates

 Store timestamps consistently according to the application's configured timezone/database strategy.

 Do not mix arbitrary timezone assumptions across features.

 When displaying timestamps to Indonesian users, use the application's configured Indonesian timezone where appropriate.

 Do not store strings such as:

```
"Senin, 20 September 2026"
```

 as the canonical database timestamp.

 Store actual date/time values and format them at the presentation layer.

---

 # File Uploads and Images

 Product/store images are untrusted user input.

 Validate:

 - File type
- MIME type
- File size
- Image dimensions where relevant
- File extension
- Upload errors

 Never trust the filename supplied by the browser.

 Do not execute uploaded files.

 Store uploaded files outside executable PHP paths when possible.

 If an external object-storage/image service is introduced later, keep it separate from authentication.

---

 # Security

 Security is a first-class requirement.

 ## Never trust the client

 Treat all browser-submitted values as untrusted.

 This includes:

 - IDs
- Prices
- User IDs
- Seller IDs
- Store IDs
- Roles
- Permissions
- Order totals
- Statuses
- Hidden form fields
- Query parameters

 A hidden input is not a security mechanism.

---

 ## CSRF

 Use CodeIgniter's CSRF protection according to the application's configuration.

 Do not disable CSRF protection simply to make a request work.

 If an API requires a different authentication/CSRF strategy, document the reason and implement it deliberately.

---

 ## XSS

 Escape user-generated content when rendering HTML.

 Marketplace users can submit:

 - Store descriptions
- Product descriptions
- Reviews
- Names
- Addresses

 Do not render arbitrary user input as raw HTML unless it has been deliberately sanitized.

---

 ## SQL Injection

 Use CodeIgniter Query Builder, parameter binding, or other safe database APIs.

 Never construct SQL by concatenating raw user input.

 Bad:

```
$db->query("SELECT * FROM products WHERE name = '" . $name . "'");
```

 Prefer Query Builder or bound parameters.

---

 # API / AJAX Endpoints

 If the application exposes JSON endpoints:

 - Authenticate requests where required
- Authorize every protected operation
- Validate request payloads
- Return appropriate HTTP status codes
- Do not expose sensitive database fields
- Do not trust IDs supplied by the client
- Do not expose internal exception details in production

 Use consistent response structures.

---

 # Error Handling

 Do not expose:

 - Database credentials
- Stack traces
- SQL queries
- Internal filesystem paths
- Environment variables
- Shield internals
- Server configuration

 in production responses.

 Use CodeIgniter's production environment and error-handling mechanisms.

---

 # Environment Configuration

 Environment-specific configuration belongs in environment variables/configuration.

 Never commit:

 - Database passwords
- API secrets
- Private keys
- Production credentials
- Session encryption secrets
- Third-party service credentials

 `.env` should not be committed if it contains secrets.

 Provide or maintain an appropriate `.env.example` containing placeholder values.

---

 # Dependencies

 Before adding a dependency, consider whether CodeIgniter or an existing project dependency already provides the required functionality.

 Avoid adding libraries for functionality already covered by:

 - CodeIgniter 4
- CodeIgniter Shield
- Existing project utilities

 When adding a dependency:

 1. Explain why it is necessary.
2. Prefer maintained packages.
3. Check compatibility with the current PHP and CodeIgniter versions.
4. Keep the dependency narrowly scoped.

 Do not replace Shield with another authentication package.

---

 # Code Style

 Follow the existing repository style.

 Prefer:

 - PSR-compatible PHP
- Clear naming
- Small focused methods
- Explicit dependencies
- Type declarations where compatible with the project
- Meaningful variable names
- Early returns for simple guard conditions

 Avoid:

 - Unnecessary abstractions
- Clever one-liners that reduce readability
- Duplicate business logic
- Global state
- Hard-coded credentials
- Magic numbers without explanation

---

 # Routes

 Routes must enforce the correct access boundaries.

 Use Shield's authentication/authorization mechanisms for protected routes.

 Public marketplace pages may include:

```
/
/products
/products/{id}
/stores/{id}
/categories/{slug}
```

 Protected areas may include:

```
/account
/orders
/seller
/seller/products
/seller/orders
/admin
```

 The exact route structure should follow the existing project.

 Do not rely on frontend navigation visibility to protect routes.

---

 # Admin Area

 Administrative functionality must be protected by Shield roles/permissions.

 Examples:

 - User management
- Seller moderation
- Product moderation
- Category management
- Review moderation
- Order oversight
- Marketplace configuration

 Never authorize an admin operation solely because a request contains:

```
role=admin
```

 or a similar client-controlled value.

 The server must obtain the authenticated identity from Shield and verify the appropriate Shield permission/role.

---

 # Testing

 Tests should cover security-sensitive behavior in addition to normal functionality.

 At minimum, when relevant, test:

 - Unauthenticated access
- Authenticated customer access
- Seller access
- Cross-seller resource access
- Admin access
- Unauthorized mutations
- Validation failures
- Ownership checks
- Order status transitions
- Product ownership
- Database constraints
- Important marketplace business rules

 A particularly important test case is:

 > Seller A must not be able to read or modify Seller B's products, orders, or store data.

 Also test that authentication behavior continues to go through Shield rather than a custom authentication implementation.

---

 # Development Workflow

 Before changing code:

 1. Inspect the existing project structure.
2. Check existing Shield configuration.
3. Check existing database configuration.
4. Inspect relevant migrations/models/controllers.
5. Follow established project conventions.
6. Reuse existing abstractions where appropriate.

 When implementing a feature:

 1. Define or confirm the domain/data model.
2. Create/update migrations if required.
3. Update models.
4. Implement business logic.
5. Add authentication/authorization checks.
6. Add validation.
7. Implement controller/routes.
8. Update the UI/API.
9. Add tests.
10. Run the relevant test suite and static checks.

 Do not make unrelated refactors while implementing a focused feature.

---

 # Git and Changes

 Keep commits and changes focused.

 Avoid mixing:

 - Authentication changes
- Database migrations
- UI redesigns
- Dependency upgrades
- Unrelated refactors

 in one change unless they are genuinely required together.

 Never commit:

 - `.env`
- credentials
- production secrets
- generated sensitive files
- database dumps containing private data

---

 # Important Architectural Invariants

 These rules must remain true unless the architecture is intentionally changed and documented:

 1. **CodeIgniter Shield is the only authentication system.**
2. **Supabase is only the database provider.**
3. **Supabase Auth must not be introduced.**
4. **Passwords must never be stored in marketplace domain tables.**
5. **Authorization must be enforced server-side.**
6. **Authenticated user identity must come from Shield.**
7. **Seller ownership must be verified server-side.**
8. **Client-submitted prices and order totals must not be trusted.**
9. **Database credentials must remain outside source control.** to require Supabase Auth, first determine whether the requirement can be fulfilled using CodeIgniter Shield. The default, do not silently introduce a second authentication mechanism. Explain the architectural conflict and preserve the existing authentication
10. **Schema changes must be represented by migrations.**
11. **User-generated content must be safely escaped/sanitized.**
12. **Security checks must not depend on the frontend.**

---

 # Agent Guidance

 When working on this repository, prioritize correctness and consistency over introducing new architecture.

 Before implementing authentication-related functionality, inspect CodeIgniter Shield's existing configuration and use it.

 Before implementing database functionality, inspect the existing CodeIgniter database connection, models, and migrations.

 If a requested feature appears to require Supabase Auth, first determine whether the requirement can be fulfilled using CodeIgniter Shield. The default answer should be to keep authentication inside Shield.

 If a requirement conflicts with the architecture described in this document, do not silently introduce a second authentication mechanism. Explain the architectural conflict and preserve the existing authentication boundary unless the project explicitly chooses to change it.

 For marketplace operations involving users, stores, products, and orders, always establish:

```
Who is the authenticated user?
        ↓
What Shield role/permission do they have?
        ↓
What marketplace resource do they own/control?
        ↓
Is this specific operation authorized?
```

 Only then perform the mutation or return protected data.
