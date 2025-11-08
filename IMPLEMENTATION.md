# Invoice Module - Implementation Summary

## Project Structure (DDD Architecture)

Projekt wykorzystuje **Domain-Driven Design** z organizacją w formie modułów:

```
src/Modules/Invoices/
├── Domain/                          # Logika biznesowa (niezależna od frameworka)
│   ├── Entities/
│   │   ├── Invoice.php             # Główna encja z regułami biznesowymi
│   │   └── InvoiceProductLine.php  # Value object dla linii produktu
│   ├── Enums/
│   │   └── StatusEnum.php          # Enum dla statusów faktury
│   └── Repositories/
│       └── InvoiceRepositoryInterface.php  # Kontrakt dla repozytorium
│
├── Application/                     # Use cases i logika aplikacyjna
│   ├── Services/
│   │   └── InvoiceService.php      # Serwis orkiestrujący logikę biznesową
│   └── Listeners/
│       └── InvoiceDeliveredListener.php  # Event listener
│
├── Infrastructure/                  # Implementacje techniczne
│   ├── Persistence/
│   │   └── EloquentInvoiceRepository.php  # Implementacja repozytorium
│   └── Providers/
│       └── InvoiceServiceProvider.php     # Service provider
│
└── Presentation/                    # Warstwa prezentacji (API)
    ├── Http/
    │   └── Controllers/
    │       └── InvoiceController.php
    └── routes.php                   # Definicje route'ów
```

## Completed Features

### 1. Domain Layer (DDD Architecture)

#### Entities

-   **Invoice** (`src/Modules/Invoices/Domain/Entities/Invoice.php`)

    -   Properties: ID (UUID), Status, Customer Name, Customer Email, Product Lines
    -   Business logic: `canBeSent()`, `markAsSending()`, `markAsSentToClient()`, `getTotalPrice()`
    -   Status transitions: draft → sending → sent-to-client

-   **InvoiceProductLine** (`src/Modules/Invoices/Domain/Entities/InvoiceProductLine.php`)
    -   Properties: Product Name, Quantity, Unit Price
    -   Calculation: `getTotalUnitPrice()` = Quantity × Unit Price

#### Status Enum

-   `StatusEnum.php` defines three states: draft, sending, sent-to-client

#### Repository Interface

-   **InvoiceRepositoryInterface** (`src/Modules/Invoices/Domain/Repositories/InvoiceRepositoryInterface.php`)
    -   Contract for data persistence abstraction

### 2. Application Layer

#### InvoiceService

-   **Location**: `src/Modules/Invoices/Application/Services/InvoiceService.php`
-   **createInvoice**: Creates invoice in draft status with optional product lines
-   **getInvoice**: Retrieves invoice by UUID
-   **sendInvoice**:
    -   Validates invoice can be sent (has product lines with positive quantities and prices)
    -   Changes status to "sending"
    -   Sends email notification via NotificationFacade
    -   Returns true/false based on success

#### Event Listener

-   **InvoiceDeliveredListener** (`src/Modules/Invoices/Application/Listeners/InvoiceDeliveredListener.php`)
    -   Listens to `ResourceDeliveredEvent` from Notification module
    -   Updates invoice status from "sending" to "sent-to-client"
    -   Only processes invoices currently in "sending" status

### 3. Infrastructure Layer

#### Repository Implementation

-   **EloquentInvoiceRepository** (`src/Modules/Invoices/Infrastructure/Persistence/EloquentInvoiceRepository.php`)
    -   Implements `InvoiceRepositoryInterface`
    -   Maps between domain entities and Eloquent models
    -   Handles UUID generation for new invoices
    -   Cascading save for product lines

#### Service Provider

-   **InvoiceServiceProvider** (`src/Modules/Invoices/Infrastructure/Providers/InvoiceServiceProvider.php`)
    -   Registers `InvoiceRepositoryInterface` → `EloquentInvoiceRepository` binding
    -   Registers event listener for `ResourceDeliveredEvent`
    -   Registered in `bootstrap/providers.php`

### 4. Presentation Layer

#### HTTP Controller

-   **InvoiceController** (`src/Modules/Invoices/Presentation/Http/Controllers/InvoiceController.php`)
    -   `GET /invoices/{id}`: View invoice details
    -   `POST /invoices`: Create new invoice
    -   `POST /invoices/{id}/send`: Send invoice to customer

#### Routes

-   **routes.php** (`src/Modules/Invoices/Presentation/routes.php`)
    -   Defines all Invoice module endpoints
    -   Loaded in `routes/api.php`

### 5. Database Layer

#### Migrations

-   **invoices table**: id (UUID), customer_name, customer_email, status, timestamps
-   **invoice_product_lines table**: id (UUID), invoice_id (FK), product_name, quantity, unit_price, total_unit_price, timestamps

### 6. Unit Tests

#### Invoice Entity Tests

-   Tests for all business logic methods
-   Validation scenarios (empty product lines, zero/negative values)
-   Status transition validations
-   Total price calculations

#### InvoiceService Tests

-   Create invoice in draft status
-   Get invoice by ID
-   Send invoice workflow with notification
-   Edge cases (not found, invalid status)

## Business Rules Implemented

✅ Invoice can only be created in `draft` status  
✅ Invoice can be created with empty product lines  
✅ Invoice can only be sent if in `draft` status  
✅ Invoice can only be marked as `sent-to-client` if status is `sending`  
✅ To be sent, invoice must have product lines with positive quantity and unit price  
✅ Email notification sent via NotificationFacade when sending  
✅ Status changes to `sending` after notification  
✅ Status changes to `sent-to-client` when ResourceDeliveredEvent received

## API Endpoints

### 1. Create Invoice

```
POST /invoices
Content-Type: application/json

{
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "product_lines": [
    {
      "product_name": "Product A",
      "quantity": 2,
      "unit_price": 100
    }
  ]
}
```

### 2. View Invoice

```
GET /invoices/{uuid}
```

Response:

```json
{
    "id": "uuid-here",
    "status": "draft",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "product_lines": [
        {
            "product_name": "Product A",
            "quantity": 2,
            "unit_price": 100,
            "total_unit_price": 200
        }
    ],
    "total_price": 200
}
```

### 3. Send Invoice

```
POST /invoices/{uuid}/send
```

## Workflow Diagram

```
1. Create Invoice (POST /invoices)
   └─> Invoice created with status = 'draft'

2. Send Invoice (POST /invoices/{id}/send)
   └─> Validate invoice.canBeSent()
   └─> Change status to 'sending'
   └─> Send email via NotificationFacade
   └─> DummyDriver triggers webhook

3. Webhook receives ResourceDeliveredEvent
   └─> InvoiceDeliveredListener handles event
   └─> Change invoice status to 'sent-to-client'
```

## Technical Decisions

1. **DDD Architecture**: Separated domain logic from infrastructure
2. **UUID for IDs**: Used UUIDs instead of auto-increment integers for better distributed system support
3. **Event-Driven**: Decoupled notification delivery confirmation via events
4. **Repository Pattern**: Abstract data access to allow different implementations
5. **Value Objects**: InvoiceProductLine as immutable value object with business calculations
6. **Status Enum**: Type-safe status values

## Testing

Run unit tests:

```bash
docker compose exec app bash
./vendor/bin/phpunit tests/Unit/Invoices
```

## Dependencies

-   Laravel Framework (Eloquent, Events, Service Container)
-   ramsey/uuid (UUID generation)
-   NotificationModule (for email sending)
