# Implementation Notes

## Why DDD?

I went with Domain-Driven Design because the project has a well-defined business domain (invoices) and the requirements were clear. DDD allows for clean separation of business logic from infrastructure concerns.

## Module Structure

### Layer Division

Each module has standard layers:

-   **Domain** - pure business logic, no framework dependencies
-   **Application** - use cases, application services
-   **Infrastructure** - technical details (repositories, providers)
-   **Presentation** - controllers, routes

This way you can easily swap EloquentRepository for something else without touching the domain.

## Why Entity vs Model?

I have two Invoice classes:

-   Domain/Entities/Invoice.php - domain entity with business logic
-   app/Models/Invoice.php - Eloquent model for database

It's a bit of duplication, but:

-   Domain entity is independent of Laravel
-   Eloquent model only handles database mapping
-   Repository translates between them

In a smaller project you could simplify this, but here I wanted to show proper DDD.

## Repository Pattern

EloquentInvoiceRepository implements InvoiceRepositoryInterface - this allows:

-   Testing without database (mock the interface)
-   Changing ORM without touching logic
-   Dependency Inversion Principle from SOLID

## Event-Listener

Instead of directly updating status in controller after webhook, I used:

-   ResourceDeliveredEvent - event from Notifications module
-   InvoiceDeliveredListener - listener in Invoices module

This keeps modules loosely coupled. Invoice module does not know where the event comes from, and Notifications does not know who is listening.

## Validation in Service vs Controller

Basic request validation is in controller (FormRequest), but business validation in service:

-   Controller checks if fields are filled correctly
-   Service checks if operation is allowed (e.g. if invoice has product lines before sending)

## Unit Tests

Tests are for domain entities (InvoiceTest, InvoiceProductLineTest) because that's where the business logic lives:

-   Price calculations
-   Status validation
-   State transitions

Controllers could be tested with integration/feature tests, but requirements only mentioned unit tests.

## NotificationFacade

I use the facade instead of service directly because:

-   Easier to mock in tests
-   Simpler API (single notify method)
-   Implementation details are hidden (driver, queue)

## Job for webhook simulation

Added SimulateWebhookCallbackJob so we do not have to wait for a real webhook. In production an external service would fire the real callback, here we simulate that after 5 seconds the delivery confirmation arrives.

## What could be improved?

If I had more time:

-   Value Objects for Email, Money instead of strings/ints
-   Aggregates instead of simple entities
-   CQRS - separate read and write models
-   Domain Events instead of Laravel Events
-   Specification Pattern for validation

But for a recruitment task, current implementation shows understanding of DDD without overengineering.

## Running the project

```bash
./start.sh
docker compose exec app bash
php artisan migrate
php artisan test
```

API endpoints:

-   GET /api/invoices/{id}
-   POST /api/invoices
-   POST /api/invoices/{id}/send
