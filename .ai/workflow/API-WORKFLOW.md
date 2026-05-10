# API Implementation Workflow Guidelines

Based on commit `a7b77a6` - Bank API implementation, this workflow defines the standard approach for implementing RESTful APIs in the Laravolt ecosystem.

## Workflow Overview

The API implementation follows **Test Driven Development (TDD)** with a **Red → Green → Refactor** cycle:

1. **Phase 1: Documentation (USER)** - User creates API specification document
2. **Phase 2: Write Failing Tests — Red (AI AGENT)** - AI agent reads documentation and writes failing tests that define expected behavior
3. **Phase 3: Write Minimum Implementation — Green (AI AGENT)** - AI agent builds the API to make all tests pass
4. **Phase 4: Refactor (AI AGENT)** - AI agent cleans up code while keeping tests green

### Role Responsibilities

**User Responsibilities:**
- Create `docs/api/{resource}.md` with complete API specification
- Define route name, model, and searchable fields
- Specify validation requirements and permissions

**AI Agent Responsibilities:**
- Read and understand the API documentation
- Write failing tests FIRST that define the API contract (Red)
- Implement the service layer, controller, and routes to pass tests (Green)
- Refactor while keeping tests green

**AI Agent Workflow:**
```
User Request: "Create API for {resource}"
    ↓
AI Agent: Check if docs/api/{resource}.md exists
    ↓ (if NO)
    → Ask user to create documentation first
    ↓ (if YES)
AI Agent: Read docs/api/{resource}.md
    ↓
AI Agent: Extract requirements (route, model, searchable fields, permissions, validation)
    ↓
AI Agent: Write failing tests (Red)
    ↓
AI Agent: Verify all new tests FAIL
    ↓
AI Agent: Implement based on documentation blueprint (Green)
    ↓
AI Agent: Verify all tests PASS
    ↓
AI Agent: Refactor
```

---

## Phase 1: Documentation (User Responsibility)

### 1.1 Create API Documentation

**Action:** User creates `docs/api/{resource}.md`

Define the API specification in a markdown file that the AI agent will use as a reference:

```markdown
# API {Resource}

## Route

{resource-route}

## Model

{ModelName}

## Searchable

- {field1}
- {field2}
```

**Example:**
```markdown
# API Bank

## Route

banks

## Model

Bank

## Searchable

- name
- code
```

### 1.2 Define Requirements

**Action:** User identifies and documents requirements

The documentation should include:
- **HTTP Methods**: GET (index, show), POST (store), PUT/PATCH (update), DELETE (destroy)
- **Searchable Fields**: Which fields can be queried via URL parameters
- **Validation Rules**: Required fields, unique constraints, max lengths
- **Authorization**: Which permissions are required for each operation
- **Response Format**: Standard JSON structure

**Once the documentation is complete, the user requests the AI agent to implement the API based on the docs.**

---

## Phase 2: Write Failing Tests — Red (AI Agent)

> **Goal:** Define the expected API behavior through tests BEFORE writing any implementation. All tests should fail at this stage.

### 2.1 Feature Tests — API Endpoints

**File:** `tests/Feature/Api/{Resource}ControllerTest.php`

```php
<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\{Resource};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravolt\Auth\Models\Permission as LaravoltPermission;
use Laravolt\Auth\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->viewPermission = LaravoltPermission::firstOrCreate(['name' => Permission::{RESOURCE}_VIEW]);
    $this->managePermission = LaravoltPermission::firstOrCreate(['name' => Permission::{RESOURCE}_MANAGE]);

    $this->adminRole = Role::firstOrCreate(['name' => '{Resource}Admin']);
    $this->adminRole->addPermission($this->viewPermission);
    $this->adminRole->addPermission($this->managePermission);

    $this->viewerRole = Role::firstOrCreate(['name' => '{Resource}Viewer']);
    $this->viewerRole->addPermission($this->viewPermission);

    Gate::define(Permission::{RESOURCE}_VIEW, fn ($user) => $user->hasPermission(Permission::{RESOURCE}_VIEW));
    Gate::define(Permission::{RESOURCE}_MANAGE, fn ($user) => $user->hasPermission(Permission::{RESOURCE}_MANAGE));

    $this->adminUser = User::factory()->create();
    $this->adminUser->addRole($this->adminRole);

    $this->viewerUser = User::factory()->create();
    $this->viewerUser->addRole($this->viewerRole);

    $this->adminToken = $this->adminUser->createToken('test-token')->plainTextToken;
    $this->viewerToken = $this->viewerUser->createToken('test-token')->plainTextToken;
});

// --- Index & Search ---

test('it_can_get_list_of_{resources}', function (): void {
    {Resource}::factory()->count(3)->create();

    $response = $this->withToken($this->adminToken)
        ->getJson('/api/{resource-plural}');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'field1', 'field2'],
            ],
        ])
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

test('it_can_get_empty_list_of_{resources}', function (): void {
    $response = $this->withToken($this->adminToken)
        ->getJson('/api/{resource-plural}');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

test('it_can_search_{resources}_by_field', function (): void {
    {Resource}::factory()->create(['field1' => 'Value A', 'field2' => 'CODEA']);
    {Resource}::factory()->create(['field1' => 'Value B', 'field2' => 'CODEB']);

    $response = $this->withToken($this->adminToken)
        ->getJson('/api/{resource-plural}?field1=Value A');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.field1', 'Value A')
        ->assertJsonPath('data.0.field2', 'CODEA');
});

test('it_returns_empty_results_when_search_has_no_match', function (): void {
    {Resource}::factory()->create(['field1' => 'Existing Value']);

    $response = $this->withToken($this->adminToken)
        ->getJson('/api/{resource-plural}?field1=Non Existent');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

// --- Show ---

test('it_can_show_single_{resource}', function (): void {
    $resource = {Resource}::factory()->create(['field1' => 'Test Value']);

    $response = $this->withToken($this->adminToken)
        ->getJson("/api/{resource-plural}/{$resource->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'field1', 'field2'],
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $resource->id)
        ->assertJsonPath('data.field1', 'Test Value');
});

test('it_returns_not_found_when_showing_non_existent_{resource}', function (): void {
    $response = $this->withToken($this->adminToken)
        ->getJson('/api/{resource-plural}/999');

    $response->assertNotFound();
});

// --- Store ---

test('it_can_create_a_{resource}', function (): void {
    $data = ['field1' => 'New Value', 'field2' => 'NEW'];

    $response = $this->withToken($this->adminToken)
        ->postJson('/api/{resource-plural}', $data);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'field1', 'field2'],
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.field1', 'New Value')
        ->assertJsonPath('data.field2', 'NEW');

    $this->assertDatabaseHas('{table-name}', $data);
});

test('it_fails_validation_when_creating_{resource}_without_required_field', function (): void {
    $response = $this->withToken($this->adminToken)
        ->postJson('/api/{resource-plural}', ['field2' => 'VALUE']);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['field1']);
});

test('it_fails_validation_when_creating_{resource}_with_duplicate_unique_field', function (): void {
    {Resource}::factory()->create(['field2' => 'UNIQUE']);

    $response = $this->withToken($this->adminToken)
        ->postJson('/api/{resource-plural}', [
            'field1' => 'Another Name',
            'field2' => 'UNIQUE',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['field2']);
});

test('it_cannot_create_{resource}_without_manage_permission', function (): void {
    $response = $this->withToken($this->viewerToken)
        ->postJson('/api/{resource-plural}', [
            'field1' => 'New Value',
            'field2' => 'NEW',
        ]);

    $response->assertForbidden();
});

// --- Update ---

test('it_can_update_a_{resource}', function (): void {
    $resource = {Resource}::factory()->create(['field1' => 'Old Value']);
    $updateData = ['field1' => 'Updated Value', 'field2' => 'UPD'];

    $response = $this->withToken($this->adminToken)
        ->putJson("/api/{resource-plural}/{$resource->id}", $updateData);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.field1', 'Updated Value')
        ->assertJsonPath('data.field2', 'UPD');

    $this->assertDatabaseHas('{table-name}', array_merge(['id' => $resource->id], $updateData));
});

test('it_can_update_{resource}_with_same_unique_field', function (): void {
    $resource = {Resource}::factory()->create(['field2' => 'SAME']);

    $response = $this->withToken($this->adminToken)
        ->putJson("/api/{resource-plural}/{$resource->id}", [
            'field1' => 'Updated Name',
            'field2' => 'SAME',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.field1', 'Updated Name')
        ->assertJsonPath('data.field2', 'SAME');
});

test('it_returns_not_found_when_updating_non_existent_{resource}', function (): void {
    $response = $this->withToken($this->adminToken)
        ->putJson('/api/{resource-plural}/999', ['field1' => 'Updated']);

    $response->assertNotFound();
});

test('it_cannot_update_{resource}_without_manage_permission', function (): void {
    $resource = {Resource}::factory()->create();

    $response = $this->withToken($this->viewerToken)
        ->putJson("/api/{resource-plural}/{$resource->id}", ['field1' => 'Updated']);

    $response->assertForbidden();
});

// --- Destroy ---

test('it_can_delete_a_{resource}', function (): void {
    $resource = {Resource}::factory()->create();

    $response = $this->withToken($this->adminToken)
        ->deleteJson("/api/{resource-plural}/{$resource->id}");

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('{table-name}', ['id' => $resource->id]);
});

test('it_returns_not_found_when_deleting_non_existent_{resource}', function (): void {
    $response = $this->withToken($this->adminToken)
        ->deleteJson('/api/{resource-plural}/999');

    $response->assertNotFound();
});

test('it_cannot_delete_{resource}_without_manage_permission', function (): void {
    $resource = {Resource}::factory()->create();

    $response = $this->withToken($this->viewerToken)
        ->deleteJson("/api/{resource-plural}/{$resource->id}");

    $response->assertForbidden();
});

// --- Authentication ---

test('it_cannot_access_api_without_authentication', function (): void {
    $response = $this->getJson('/api/{resource-plural}');

    $response->assertUnauthorized();
});
```

### 2.2 Unit Tests — Service Search

**File:** `tests/Unit/Services/{Resource}ServiceTest.php`

Add tests for the `search()` method to the existing test file (or create new file).

```php
<?php

declare(strict_types=1);

use App\Models\{Resource};
use App\Models\User;
use App\Services\{Resource}Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

test('it_can_search_{resources}_by_field1', function (): void {
    {Resource}::factory()->create(['field1' => 'Value A', 'field2' => 'CODEA']);
    {Resource}::factory()->create(['field1' => 'Value B', 'field2' => 'CODEB']);

    $request = Request::create('/{resource-plural}', 'GET', ['field1' => 'Value A']);
    $result = {Resource}Service::search($request);

    expect($result)->toHaveCount(1);
    expect($result->first()->field1)->toBe('Value A');
    expect($result->first()->field2)->toBe('CODEA');
});

test('it_can_search_{resources}_by_field2', function (): void {
    {Resource}::factory()->create(['field1' => 'Value A', 'field2' => 'CODEA']);
    {Resource}::factory()->create(['field1' => 'Value B', 'field2' => 'CODEB']);

    $request = Request::create('/{resource-plural}', 'GET', ['field2' => 'CODEB']);
    $result = {Resource}Service::search($request);

    expect($result)->toHaveCount(1);
    expect($result->first()->field1)->toBe('Value B');
    expect($result->first()->field2)->toBe('CODEB');
});

test('it_can_search_{resources}_by_multiple_fields', function (): void {
    {Resource}::factory()->create(['field1' => 'Value A', 'field2' => 'CODEA']);
    {Resource}::factory()->create(['field1' => 'Value B', 'field2' => 'CODEB']);

    $request = Request::create('/{resource-plural}', 'GET', [
        'field1' => 'Value A',
        'field2' => 'CODEA',
    ]);
    $result = {Resource}Service::search($request);

    expect($result)->toHaveCount(1);
    expect($result->first()->field1)->toBe('Value A');
});

test('it_returns_all_{resources}_when_no_search_criteria', function (): void {
    {Resource}::factory()->count(3)->create();

    $request = Request::create('/{resource-plural}', 'GET', []);
    $result = {Resource}Service::search($request);

    expect($result)->toHaveCount(3);
});

test('it_returns_empty_collection_when_no_match_found', function (): void {
    {Resource}::factory()->create(['field1' => 'Existing Value']);

    $request = Request::create('/{resource-plural}', 'GET', ['field1' => 'Non Existent']);
    $result = {Resource}Service::search($request);

    expect($result)->toHaveCount(0);
});

test('it_ignores_non_searchable_fields', function (): void {
    {Resource}::factory()->create(['field1' => 'Value A']);

    $request = Request::create('/{resource-plural}', 'GET', [
        'field1' => 'Value A',
        'invalid_field' => 'some_value',
    ]);
    $result = {Resource}Service::search($request);

    expect($result)->toHaveCount(1);
    expect($result->first()->field1)->toBe('Value A');
});
```

### 2.3 Verify Tests Are Failing

```bash
php artisan test --compact
```

> All new tests should show as **FAILED**. If a test passes without implementation, the test is not testing the right thing.

---

## Phase 3: Write Minimum Implementation — Green (AI Agent)

> **Goal:** Write the minimum code necessary to make all failing tests pass. Do not add features beyond what the tests require.

### 3.1 Service Layer

**File:** `app/Services/{Resource}Service.php`

Add a `search()` method for listing and filtering resources:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Resource};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

final class {Resource}Service
{
    // Existing methods...

    public static function search(Request $request): Collection
    {
        $searchable = ['field1', 'field2', 'field3'];

        $queries = array_intersect_key($request->all(), array_flip($searchable));

        $query = {Resource}::query();

        foreach ($queries as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }
}
```

**Pattern:**
- Define searchable fields as an array
- Use `array_intersect_key()` to filter only allowed search parameters — prevents querying arbitrary columns
- Apply exact match queries
- Return Eloquent Collection

### 3.2 Form Request Validation

**File:** `app/Http/Requests/{Resource}Request.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class {Resource}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Permission::{RESOURCE}_MANAGE);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $uniqueField = 'unique:{table},{field}';

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $uniqueField .= ','.$this->route('{resource}');
        }

        return [
            'field1' => ['required', 'string', 'max:255'],
            'field2' => ['required', 'string', 'max:10', $uniqueField],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'field1' => 'Field One Label',
            'field2' => 'Field Two Label',
        ];
    }
}
```

**CRITICAL Requirements:**
- Must be marked as `final class` with `declare(strict_types=1);`
- Authorization checks `{RESOURCE}_MANAGE` permission for write operations
- Unique validation must exclude the current record on updates

### 3.3 API Controller

**File:** `app/Http/Controllers/Api/{Resource}Controller.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\{Resource}Request;
use App\Http\Responses\ApiResponse;
use App\Services\{Resource}Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class {Resource}Controller
{
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success({Resource}Service::search($request));
    }

    public function store({Resource}Request $request): JsonResponse
    {
        return ApiResponse::success({Resource}Service::save($request));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success({Resource}Service::get($id));
    }

    public function update({Resource}Request $request, int $id): JsonResponse
    {
        return ApiResponse::success({Resource}Service::update($request, $id));
    }

    public function destroy(int $id): JsonResponse
    {
        {Resource}Service::delete($id);

        return ApiResponse::success();
    }
}
```

**CRITICAL Requirements:**
- Must be marked as `final class` with `declare(strict_types=1);`
- **DO NOT extend any base Controller class** — standalone class
- All methods return `JsonResponse`
- Use `ApiResponse::success()` for consistent responses
- Controller delegates all logic to service layer

**ApiResponse Methods:**
- `ApiResponse::success($data, $message, $status)` — 200 OK
- `ApiResponse::created($data, $message)` — 201 Created
- `ApiResponse::error($message, $errors, $status)` — 400 Bad Request
- `ApiResponse::unauthorized($message)` — 401 Unauthorized
- `ApiResponse::forbidden($message)` — 403 Forbidden
- `ApiResponse::notFound($message)` — 404 Not Found
- `ApiResponse::validationError($message, $errors)` — 422 Validation Error

### 3.4 API Routes

**File:** `routes/api.php`

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Api\{Resource}Controller;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function (): void {
    // Existing routes...

    Route::middleware('auth:sanctum')->group(function (): void {
        // Existing authenticated routes...

        Route::apiResource('{resource-plural}', {Resource}Controller::class);
    });
});
```

**Pattern:**
- All API routes use `auth:sanctum` middleware
- Routes are namespaced with `api.` prefix
- Use `Route::apiResource()` for RESTful endpoints

### 3.5 Verify All Tests Pass

```bash
php artisan test --compact
php artisan route:list --path=api
```

> All tests must pass before proceeding. If any test fails, fix the implementation — **do not modify the test** unless the test itself was written incorrectly.

---

## Phase 4: Refactor (AI Agent)

> **Goal:** Improve code quality without changing behavior. Tests must remain green throughout.

- Extract duplicated logic
- Improve naming clarity
- Optimize queries (eager loading, N+1 prevention)
- Run code formatting: `vendor/bin/pint --dirty --format agent`
- Re-run tests after every refactor step: `php artisan test --compact`

---

## Code Quality Standards

### Type Safety

- **CRITICAL:** Always use `declare(strict_types=1);` at the top of EVERY PHP file
- Use explicit return type declarations for all methods
- Use proper type hints for method parameters
- **All classes must be marked as `final`** unless designed for extension

### API Response Standards

**Standard Response Structure:**
```json
{
  "success": true,
  "message": "Human readable message",
  "data": { ... }
}
```

**Error Response Structure:**
```json
{
  "success": false,
  "message": "Human readable message",
  "errors": { ... }
}
```

**HTTP Status Codes:**
- `200` — OK (GET, PUT/PATCH, DELETE)
- `201` — Created (POST)
- `401` — Unauthorized (no token)
- `403` — Forbidden (has token but no permission)
- `404` — Not Found
- `422` — Validation Error

### Security Best Practices

- All endpoints require `auth:sanctum` middleware
- Form requests authorize using permissions
- Search functionality filters by allowed fields only (whitelist via `$searchable`)
- Unique validation excludes current record on updates
- Never expose internal error details in responses

---

## Workflow Checklist

### Phase 1: Documentation (User Responsibility)

- [ ] **USER**: Create `docs/api/{resource}.md` with route, model, and searchable fields
- [ ] **USER**: Identify required permissions
- [ ] **USER**: Define validation rules
- [ ] **USER**: Request AI agent to implement the API

### Phase 2: Write Failing Tests — Red (AI Agent)

- [ ] Read `docs/api/{resource}.md` and extract requirements
- [ ] Write feature tests for all endpoints (list, search, show, store, update, destroy)
- [ ] Write feature tests for authentication and authorization
- [ ] Write unit tests for service `search()` method
- [ ] Confirm all new tests **FAIL** with `php artisan test --compact`

### Phase 3: Make Tests Pass — Green (AI Agent)

- [ ] Add `search()` method to `app/Services/{Resource}Service.php`
- [ ] Create or update `app/Http/Requests/{Resource}Request.php`
- [ ] Create `app/Http/Controllers/Api/{Resource}Controller.php`
- [ ] Add API routes to `routes/api.php`
- [ ] Verify routes: `php artisan route:list --path=api`
- [ ] Confirm all tests **PASS** with `php artisan test --compact`

### Phase 4: Refactor (AI Agent)

- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Eliminate any duplication introduced during Green phase
- [ ] Confirm all tests still pass

---

## Common Patterns

### Search Functionality

```php
public static function search(Request $request): Collection
{
    $searchable = ['field1', 'field2'];

    $queries = array_intersect_key($request->all(), array_flip($searchable));

    $query = Model::query();

    foreach ($queries as $key => $value) {
        $query->where($key, $value);
    }

    return $query->get();
}
```

### Unique Validation with Update Support

```php
public function rules(): array
{
    $uniqueRule = 'unique:table,column';

    if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
        $uniqueRule .= ','.$this->route('{resource}');
    }

    return [
        'field' => ['required', $uniqueRule],
    ];
}
```

### Consistent API Responses

```php
return ApiResponse::success($data);
return ApiResponse::created($data, 'Resource created');
return ApiResponse::notFound('Resource not found');
```

---

## Quick Reference

### File Locations

- **API Docs:** `docs/api/{resource}.md`
- **Service:** `app/Services/{Resource}Service.php`
- **Form Request:** `app/Http/Requests/{Resource}Request.php`
- **API Controller:** `app/Http/Controllers/Api/{Resource}Controller.php`
- **API Routes:** `routes/api.php`
- **Feature Tests:** `tests/Feature/Api/{Resource}ControllerTest.php`
- **Unit Tests:** `tests/Unit/Services/{Resource}ServiceTest.php`

### Artisan Commands

```bash
# List API routes
php artisan route:list --path=api

# Run tests
php artisan test --compact

# Run specific test file
php artisan test --filter {Resource}ControllerTest

# Format code
vendor/bin/pint --dirty --format agent
```

### Common HTTP Methods

```
GET    /api/{resource}       → index  (list all, with optional search params)
POST   /api/{resource}       → store  (create new)
GET    /api/{resource}/{id}  → show   (get single)
PUT    /api/{resource}/{id}  → update (replace)
PATCH  /api/{resource}/{id}  → update (partial)
DELETE /api/{resource}/{id}  → destroy
```

### Search Query Parameters

```
GET /api/{resource}?field1=value1&field2=value2
```

Only fields defined in the service's `$searchable` array will be processed.

This workflow ensures consistent, secure, and well-tested API implementations following Laravel and Laravolt best practices.
