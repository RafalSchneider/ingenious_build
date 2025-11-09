# Testy Jednostkowe - Invoice Module

## Opis

Projekt zawiera kompleksowy zestaw testów jednostkowych dla modułu faktur (Invoices), zgodnie z wymaganiami projektu dotyczącymi testowania podstawowej logiki biznesowej.

## Struktura Testów

### 1. InvoiceProductLineTest.php

Testy dla encji `InvoiceProductLine` sprawdzające:

-   ✅ Tworzenie linii produktu z poprawnymi danymi
-   ✅ Obliczanie całkowitej ceny jednostkowej (quantity × price)
-   ✅ Obsługę różnych wartości (1, 0, duże liczby)

**Kluczowe scenariusze:**

-   Obliczanie ceny dla pojedynczej jednostki
-   Obliczanie ceny dla dużej ilości jednostek
-   Obsługa zerowej ilości lub ceny

### 2. InvoiceTest.php

Testy dla encji `Invoice` sprawdzające reguły biznesowe:

-   ✅ Tworzenie faktury w statusie `draft`
-   ✅ Tworzenie faktury z pustymi liniami produktów
-   ✅ Obliczanie całkowitej ceny faktury
-   ✅ Walidację możliwości wysłania faktury (`canBeSent()`)
-   ✅ Zmiany statusów faktury (draft → sending → sent-to-client)

**Kluczowe reguły biznesowe testowane:**

-   Faktura może być wysłana tylko w statusie `draft`
-   Faktura musi zawierać linie produktów z quantity > 0 i price > 0
-   Faktura nie może być wysłana jeśli którykolwiek produkt ma nieprawidłowe wartości
-   Przejścia statusów są warunkowe (draft → sending tylko gdy `canBeSent()` = true)
-   Status `sent-to-client` można ustawić tylko ze statusu `sending`

### 3. InvoiceServiceTest.php

Testy dla serwisu aplikacyjnego `InvoiceService` z wykorzystaniem mocków:

-   ✅ Tworzenie faktury z klientem
-   ✅ Tworzenie faktury z liniami produktów
-   ✅ Pobieranie faktury po ID
-   ✅ Wysyłanie faktury (integracja z NotificationFacade)
-   ✅ Walidację przed wysłaniem

**Testowane interakcje:**

-   Zapis do repozytorium
-   Wywołanie NotificationFacade z poprawnymi danymi
-   Zmiana statusu faktury na `sending` przed wysłaniem notyfikacji
-   Odrzucenie wysyłki dla nieistniejącej faktury
-   Odrzucenie wysyłki gdy `canBeSent()` = false

### 4. InvoiceDeliveredListenerTest.php

Testy dla listenera `InvoiceDeliveredListener` obsługującego webhook:

-   ✅ Zmiana statusu na `sent-to-client` gdy status = `sending`
-   ✅ Brak zmiany statusu gdy status != `sending`
-   ✅ Obsługa nieistniejącej faktury (brak wyjątku)

**Testowany workflow:**

-   Event `ResourceDeliveredEvent` z UUID faktury
-   Pobranie faktury z repozytorium
-   Walidacja statusu przed zmianą
-   Wywołanie `markAsSentToClient()` tylko dla statusu `sending`

## Uruchomienie Testów

### W kontenerze Docker:

```bash
docker compose exec app bash
php artisan test --testsuite=Unit
```

### Lub bezpośrednio PHPUnit:

```bash
docker compose exec app bash
./vendor/bin/phpunit --testsuite=Unit
```

### Konkretny plik testowy:

```bash
docker compose exec app bash
./vendor/bin/phpunit tests/Unit/Invoices/InvoiceTest.php
```

### Z filtrem (konkretny test):

```bash
docker compose exec app bash
./vendor/bin/phpunit --filter test_invoice_can_be_sent_when_all_conditions_are_met
```

## Pokrycie Testami

Testy pokrywają wszystkie kluczowe aspekty logiki biznesowej:

1. **Encje domenowe** (100% logiki biznesowej):

    - `Invoice::getTotalPrice()`
    - `Invoice::canBeSent()`
    - `Invoice::markAsSending()`
    - `Invoice::markAsSentToClient()`
    - `InvoiceProductLine::getTotalUnitPrice()`

2. **Serwisy aplikacyjne** (kluczowe operacje):

    - `InvoiceService::createInvoice()`
    - `InvoiceService::getInvoice()`
    - `InvoiceService::sendInvoice()`

3. **Event Listeners** (workflow):
    - `InvoiceDeliveredListener::handle()`

## Podejście Testowe

### Unit Tests (Pure Domain Logic)

Testy encji domenowych (`Invoice`, `InvoiceProductLine`) są czystymi testami jednostkowymi bez zależności od frameworka Laravel - testują tylko logikę biznesową.

### Service Tests (With Mocks)

Testy serwisów używają Mockery do izolacji zależności:

-   `InvoiceRepositoryInterface` - mock repozytorium
-   `NotificationFacadeInterface` - mock fasady notyfikacji

## Technologie

-   **PHPUnit 11** - framework testowy
-   **Mockery 1.6** - biblioteka do tworzenia mocków
-   **Laravel 12** - framework aplikacji (tylko dla TestCase)

## Notatki Deweloperskie

Zgodnie z wymaganiami projektu:

-   ✅ Testowana jest podstawowa logika biznesowa faktur
-   ✅ Nie testujemy zwracanych wartości z endpointów (to byłyby testy integracyjne/feature)
-   ✅ Skupiamy się na regułach domenowych i przepływie statusów
-   ✅ Używamy DDD approach - testy odzwierciedlają strukturę domenową

## Możliwe Rozszerzenia

W przyszłości można dodać:

-   Testy integracyjne dla kontrolerów (Feature tests)
-   Testy dla repozytorium Eloquent
-   Testy dla fasady notyfikacji
-   Testy mutacyjne (Infection PHP)
-   Coverage reports
