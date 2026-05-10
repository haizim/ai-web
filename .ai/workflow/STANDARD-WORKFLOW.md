# Standard CRUD Workflow Guidelines

Based on the established patterns in the contact management implementation, these guidelines define the standard workflow for creating complete CRUD features in the Laravolt ecosystem.

## Role Separation

**CRITICAL:** This workflow has clear role separation between USER and AI AGENT:

### USER Responsibilities:
- ✅ Create CRUD documentation at `docs/crud/{feature}.md`
- ✅ Define model name, table fields, validation rules, permissions, and table columns
- ✅ Provide complete specification before AI implementation

### AI AGENT Responsibilities:
- ✅ Read and understand the user-created documentation
- ✅ Write failing tests FIRST (Red)
- ✅ Implement CRUD feature to make tests pass (Green)
- ✅ Refactor while keeping tests green
- ❌ **NEVER create the CRUD documentation**

**Workflow Rule:** Documentation is the SINGLE SOURCE OF TRUTH. AI agent implements EXACTLY what is specified in the documentation.

## Workflow Overview

The standard workflow follows **Test Driven Development (TDD)** with a **Red → Green → Refactor** cycle:

1. **Documentation (USER)** - User creates CRUD specification document at `docs/crud/{feature}.md`
2. **Red (AI AGENT)** - AI agent reads documentation and writes failing tests that define expected behavior
3. **Green (AI AGENT)** - AI agent writes minimum implementation to make all tests pass
4. **Refactor (AI AGENT)** - AI agent cleans up code while keeping tests green

**AI Agent Workflow:**
```
User Request: "Create CRUD for {feature}"
    ↓
AI Agent: Check if docs/crud/{feature}.md exists
    ↓ (if NO)
    → Ask user to create documentation first
    ↓ (if YES)
AI Agent: Read docs/crud/{feature}.md
    ↓
AI Agent: Extract requirements (fields, validation, permissions, table columns, menu)
    ↓
AI Agent: Write failing tests (Red)
    ↓
AI Agent: Implement to pass tests (Green)
    ↓
AI Agent: Refactor & cleanup
```

Each phase maps to a commit:
1. **Test Commit** - Failing tests that define the feature contract
2. **Implementation Commit** - Minimum code to pass all tests
3. **Refactor Commit** - Cleanup and polish (optional)

---

## Phase 0: Documentation (USER)

> **Goal:** User creates a complete specification document that the AI agent will use as the single source of truth.

**File:** `docs/crud/{feature}.md`

```markdown
# {Feature Name}

## Model
- Table: {table_name}
- Belongs to: User (user_id)
- Soft Deletes: yes/no

## Fields
| Field       | Type         | Required | Rules            | Label       |
|-------------|--------------|----------|------------------|-------------|
| name        | string(255)  | yes      | max:255          | Nama        |
| email       | string(255)  | yes      | email, unique    | Email       |
| phone       | string(20)   | no       |                  | Telepon     |
| status      | enum         | yes      | in:active,block  | Status      |

## Permissions
- View: {feature}:view
- Manage: {feature}:manage

## Menu
- Label: {Feature Name}
- Icon: {icon-name}
- Order: (under which group)

## Table Columns (Index)
| Column      | Type           | Sortable |
|-------------|----------------|----------|
| name        | Text           | yes      |
| email       | Text           | yes      |
| status      | Label (mapped) | no       |
| created_at  | Date           | yes      |

## Searchable Fields
- name
- email
```

**CRITICAL:** AI agent must NOT proceed without this document. If the document does not exist, ask the user to create it first.

---

## Phase 1: Write Failing Tests (Red)

> **Goal:** Define the expected behavior through tests BEFORE writing any implementation. All tests should fail at this stage.

### 1.1 Test Environment Setup

**File: `.env.testing`**
- Create testing-specific environment configuration
- Use separate test database
- Configure appropriate cache and session drivers

**Icon Stubs:**

**Directory: `tests/fixtures/icons/duotone/`**

Create minimal SVG stubs for icons used in tests:
```svg
<svg></svg>
```

**Pattern:** Create stubs for all icons referenced in views and components.

### 1.2 Feature Tests

**File: `tests/Feature/{Feature}/{Feature}ControllerTest.php`**

Write comprehensive feature tests covering all expected behaviors:

**Permission Testing:**
- Access with proper permissions should succeed
- Access without permissions should fail (401)
- Role-based permission checking

**CRUD Operations:**
- `test_it_can_access_{feature}_index_with_view_permission`
- `test_it_cannot_access_{feature}_index_without_view_permission`
- `test_it_can_access_{feature}_create_with_manage_permission`
- `test_it_can_store_a_{feature}`
- `test_it_can_display_{feature}_detail`
- `test_it_can_display_{feature}_edit_page`
- `test_it_can_update_a_{feature}`
- `test_it_can_delete_a_{feature}`

**Test Setup Pattern:**
```php
beforeEach(function (): void {
    $this->user = User::factory()->create();
    
    // Create permissions in DB
    $this->viewPermission = LaravoltPermission::firstOrCreate(['name' => Permission::{FEATURE}_VIEW]);
    $this->managePermission = LaravoltPermission::firstOrCreate(['name' => Permission::{FEATURE}_MANAGE]);
    
    // Create roles and assign permissions
    $this->viewerRole = Role::firstOrCreate(['name' => '{Feature}Viewer']);
    $this->viewerRole->addPermission($this->viewPermission);

    $this->managerRole = Role::firstOrCreate(['name' => '{Feature}Manager']);
    $this->managerRole->addPermission($this->viewPermission);
    $this->managerRole->addPermission($this->managePermission);

    // Define Gates manually
    Gate::define(Permission::{FEATURE}_VIEW, function ($user) {
        return $user->hasPermission(Permission::{FEATURE}_VIEW);
    });
    Gate::define(Permission::{FEATURE}_MANAGE, function ($user) {
        return $user->hasPermission(Permission::{FEATURE}_MANAGE);
    });
    
    $this->withoutMiddleware(ValidateCsrfToken::class);
});
```

### 1.3 Unit Tests — Model

**File: `tests/Unit/Models/{Feature}Test.php`**

Test model characteristics before the model exists:
- Relationship definitions if needed
- Fillable attributes
- Soft delete functionality
- Custom methods or scopes

### 1.4 Unit Tests — Service

**File: `tests/Unit/Services/{Feature}ServiceTest.php`**

Test all service methods before the service exists:
- `test_it_can_save_a_{feature}`
- `test_it_can_get_a_{feature}_by_id`
- `test_it_cannot_get_{feature}_belonging_to_another_user` : only if required user_id in that table
- `test_it_can_update_a_{feature}`
- `test_it_can_delete_a_{feature}`

**Pattern:** Test business logic, user scoping, and edge cases.

### 1.5 Verify Tests Are Failing

Run tests to confirm all newly written tests fail (expected at this stage):

```bash
php artisan test --compact
```

> All new tests should show as **FAILED**. If a test passes without implementation, the test is not testing the right thing.

---

## Phase 2: Write Minimum Implementation (Green)

> **Goal:** Write the minimum code necessary to make all failing tests pass. Do not add features beyond what the tests require.

### 2.1 Permissions & Authorization

**File: `app/Enums/Permission.php`**

Add permission constants to the Permission enum:
```php
// {FEATURE}
const {FEATURE}_VIEW = '{feature}:view';
const {FEATURE}_MANAGE = '{feature}:manage';
```

**Pattern:** Use `{FEATURE}:view` for read access and `{FEATURE}:manage` for create/update/delete access.

### 2.2 Database Layer

**Migration:** Create table with proper schema
- Use `php artisan make:migration` command
- Include `id()`, `timestamps()`, and `softDeletes()`
- Add foreign key constraints with proper `onDelete` behavior
- Index frequently queried columns

**Model:** Create Eloquent model
- **CRITICAL:** Must be marked as `final class`
- Add `declare(strict_types=1);` at the top
- Add `AutoFilter`, `AutoSearch`, `AutoSort` traits from Laravolt Suitable
- Define `$fillable` properties for mass assignment
- Add relationships to other models only if needed (e.g., `belongsTo(User::class)`)

**Factory:** Create database factory
- **CRITICAL:** Must be marked as `final class`
- Add `declare(strict_types=1);` at the top
- Use `php artisan make:factory` command
- Define realistic dummy data using Faker
- Associate with parent models where appropriate

### 2.3 Service Layer

**File: `app/Services/{Feature}Service.php`**

Create static methods for all business logic:
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Feature};
use Illuminate\Http\Request;

final class {Feature}Service
{
    public static function save(Request $request)
    public static function get($id) 
    public static function update(Request $request, $id)
    public static function delete($id)
}
```

**Pattern:** Service layer handles all data operations and user-scoped queries. Controllers remain thin.

### 2.4 Request Validation

**File: `app/Http/Requests/{Feature}Request.php`**

Create Form Request class with:
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class {Feature}Request extends FormRequest
{
    // validation rules and authorization
}
```

**Requirements:**
- **CRITICAL:** Must be marked as `final class`
- Add `declare(strict_types=1);` at the top
- Proper authorization logic
- Comprehensive validation rules
- Custom error messages if needed

### 2.5 Controller Layer

**File: `app/Http/Controllers/{Feature}Controller.php`**

Create resource controller with standard methods:
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\{Feature}Request;
use App\Models\{Feature};
use App\Services\{Feature}Service;

final class {Feature}Controller
{
    public function index()
    public function create()
    public function store({Feature}Request $request)
    public function show(string $id)
    public function edit(string $id)
    public function update({Feature}Request $request, string $id)
    public function destroy({Feature} ${feature})
}
```

**CRITICAL Requirements:**
- **Must be marked as `final class`**
- **DO NOT extend any Controller class** - standalone class
- Add `declare(strict_types=1);` at the top
- Inject service layer and return proper responses with flash messages

### 2.6 Authorization Middleware

**File: `app/Http/Middleware/{Feature}View.php`**

Create middleware for permission-based access control:
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class {Feature}View
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()->can(Permission::{FEATURE}_VIEW)) {
            return $next($request);
        }

        return abort(401);
    }
}
```

**CRITICAL:** Must be marked as `final class` with `declare(strict_types=1);`

### 2.7 Livewire Data Table

**Location: `app/Livewire/Table/{Feature}Table.php`**

⚠️ **Important:** TableView files must be located in `app/Livewire/Table/`, NOT in `app/Http/Livewire/Table/`.

Create TableView class extending `Laravolt\Ui\TableView`:
```php
<?php

declare(strict_types=1);

namespace App\Livewire\Table;

use App\Enums\Permission;
use App\Models\{Feature};
use Laravolt\Suitable\Columns\{...};
use Laravolt\Ui\TableView;

final class {Feature}Table extends TableView
{
    public function data() // Define query with user scoping and search/sort
    public function columns(): array // Define table columns using Suitable columns
}
```

**CRITICAL:** Must be marked as `final class` with `declare(strict_types=1);`

**Column Types:** Use appropriate Suitable column types:
- **Avatar:** `Avatar::make('email', 'Avatar')`
- **Boolean:** `Boolean::make('is_active')`
- **Button:** `Button::make('permalink', 'Info')->label('Profile')->icon('external link')`
  * *Dynamic closure:* `Button::make(fn ($user) => route('users.show', $user['id']))`
- **Checkall:** `Checkall::make('id')`
- **Date & Time:** `Date::make('created_at')`, `DateTime::make('created_at')`
- **HTML & Raw:** * `Html::make('bio')` *(For executing WYSIWYG tags safely)*
  * `Raw::make(fn ($user) => $user->roles->implode('name', ', '), 'Roles')` *(Custom logic)*
- **ID & Row Number:** `Id::make()`, `RowNumber::make()`
- **Image:** `Image::make('profile_picture')->height(50)->width(50)->alt('Pic')`
- **Label:** `Label::make('status')->addClass('green')->map(['active' => 'green', 'banned' => 'red'])`
- **Number & Text:** `Number::make('salary')` *(Formats with thousands separator)*, `Text::make('name', 'Full Name')->sortable()`
- **Restful Buttons (CRUD):** `RestfulButton::make('users')->only('show', 'edit')->except('destroy')`
- **URL & View:** `Url::make('website')`, `View::make('profile')`

### 2.8 Views

**Directory:** `resources/views/{feature}/`

Create standard views:
- `index.blade.php` - Main listing with table
- `create.blade.php` - Creation form
- `edit.blade.php` - Edit form
- `show.blade.php` - Detail view
- `_form.blade.php` - Reusable form partial

**View Patterns:**
- Use `<x-volt-app>` wrapper with proper title
- Use `<x-volt-panel>` for content grouping
- Use `actions` slot for page-level buttons
- Use Laravolt form builder: `{!! form()->text('field')->label('Label') !!}`
- Use Laravolt button components for all form actions (see Form Buttons section below)
- Use Fomantic UI's "very basic" table class for detail views (see Show View pattern below)

**Index View Pattern:**
```blade
<x-volt-app title="{Feature}">
    @slot('actions')
        <x-volt-link-button url="{{ route('{feature}.create') }}" icon="plus" label="New" />
    @endslot

    @livewire(\App\Livewire\Table\{Feature}Table::class)
</x-volt-app>
```

**Show View Pattern with "very basic" Table:**

For the `show.blade.php` detail view, use Fomantic UI's "very basic" table class for a clean, simple data display:

```blade
<x-volt-app title="{Feature} Detail">
    <x-volt-panel title="Detail">
        <table class="ui very basic table">
            <tbody>
                <tr>
                    <td><strong>Field 1</strong></td>
                    <td>{{ $feature->field1 }}</td>
                </tr>
                <tr>
                    <td><strong>Field 2</strong></td>
                    <td>{{ $feature->field2 }}</td>
                </tr>
                <tr>
                    <td><strong>Created At</strong></td>
                    <td>{{ $feature->created_at->format('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Updated At</strong></td>
                    <td>{{ $feature->updated_at->format('d M Y H:i') }}</td>
                </tr>
            </tbody>
        </table>

        @if (auth()->user()->can(\App\Enums\Permission::{FEATURE}_MANAGE))
            <x-volt-link-button url="{{ route('{feature}.edit', $feature->id) }}" icon="edit" label="Edit" />
        @endif
        <x-volt-link-button url="{{ route('{feature}.index') }}" icon="arrow left" label="Back" />
    </x-volt-panel>
</x-volt-app>
```

**The "very basic" table class:**
- Removes borders and padding for a cleaner look
- Perfect for displaying single entity details in show view
- Maintains Fomantic UI table structure
- Best used with `<x-volt-panel>` for proper spacing

**Reusable Form Partial View Pattern (_form):**
```blade
{{-- Form fields here --}}
<x-volt-link-button url="{{ route('{feature}.index') }}" icon="arrow left" label="Cancel" class="secondary" />
<x-volt-button icon="save" label="Save" />
```


**Form View Pattern (create/edit):**
```blade
<x-volt-app title="Create {Feature}">
    <x-volt-panel title="Form">
        {!! form()->open()->post()->action(route('{feature}.store')) !!}
            @include('{feature}._form')
        {!! form()->close() !!}
    </x-volt-panel>
</x-volt-app>
```

### 2.9 Routes

**File: `routes/web.php`**

Add resource route with middleware:
```php
Route::resource('{feature}', {Feature}Controller::class)
    ->middleware({Feature}View::class);
```

### 2.10 Menu Configuration

**File: `config/laravolt/menu/menu.php`**

Add menu item with proper permissions:
```php
'{Feature}' => [
    'route' => '{feature}.index',
    'active' => '{feature}/*',
    'icon' => 'appropriate-icon',
    'permissions' => [Permission::{FEATURE}_VIEW],
],
```

### 2.11 Verify All Tests Pass

Run all tests and confirm everything passes (Green):

```bash
php artisan test --compact
```

> All tests must pass before proceeding. If any test fails, fix the implementation — **do not modify the test** unless the test itself was written incorrectly.

---

## Phase 3: Refactor (Refactor)

> **Goal:** Improve the code quality without changing behavior. Tests must remain green throughout.

- Extract duplicated logic
- Improve naming clarity
- Optimize queries (eager loading, N+1 prevention)
- Run code formatting: `vendor/bin/pint --dirty --format agent`
- Re-run tests after every refactor step: `php artisan test --compact`

---

## Testing Standards

### Test Execution
- Use Pest testing framework syntax
- Run tests with `php artisan test --compact`
- Use descriptive test names that explain what is being tested

### Test Structure
- Use `beforeEach()` for common setup
- Use factories for test data
- Clean up database between tests
- Test both success and failure scenarios

### Coverage Goals
- Test all controller methods
- Test all service methods
- Test permission checks
- Test user data scoping
- Test database operations

---

## Code Quality Standards

### Type Safety
- **CRITICAL:** Always use `declare(strict_types=1);` at the top of EVERY PHP file
- Use explicit return type declarations for all methods and functions
- Use proper type hints for method parameters
- **All classes must be marked as `final` unless they are explicitly designed for extension**
- Avoid inheritance - prefer composition over inheritance
- Do not extend the base `Controller` class - create standalone controller classes

### Architectural Requirements (ArchTest Compliance)
- **All classes must be `final`** - Models, Services, Controllers, Requests, Middleware, Factories, Seeders, Livewire components
- **All files must have `declare(strict_types=1);`** - No exceptions, including tests, factories, seeders
- **No inheritance of local `Controller` class** - Create standalone controller classes
- **No abstract classes in custom code** - Use interfaces or concrete implementations
- **All form requests must be final** - Mark request classes as `final class`
- **All service classes must be final** - Prevent extension of business logic classes
- **All models must be final** - Unless explicitly designed for inheritance
- **All factories must be final** - Prevent extension of factory classes
- **All seeders must be final** - Prevent extension of seeder classes
- **All Livewire components must be final** - Prevent extension of UI components

### Laravolt Components
- Use `<x-volt-app>` for page layouts
- Use Laravolt form builder for all forms
- Use Suitable columns for tables
- Use Laravolt buttons and links
- Follow Fomantic UI conventions

### Error Handling
- Use flash messages for user feedback
- Proper HTTP status codes
- Graceful error handling in services

### Database Best Practices
- Use Eloquent relationships
- Prevent N+1 queries with eager loading
- Use proper foreign key constraints
- Index frequently queried columns
- Use soft deletes for data retention

---

## Workflow Checklist

### Phase 1: Write Failing Tests (Red)
- [ ] Create `.env.testing` configuration
- [ ] Create icon fixture stubs
- [ ] Write feature test with permission tests (expect failures)
- [ ] Write feature test with CRUD operation tests (expect failures)
- [ ] Write unit test for model (expect failures)
- [ ] Write unit test for service (expect failures)
- [ ] Confirm all new tests **FAIL** with `php artisan test --compact`

### Phase 2: Make Tests Pass (Green)
- [ ] Add permissions to `app/Enums/Permission.php`
- [ ] Create database migration with proper schema
- [ ] Create Eloquent model with traits and relationships
- [ ] Create database factory with realistic data
- [ ] Create service class with static methods
- [ ] Create Form Request validation class
- [ ] Create resource controller
- [ ] Create authorization middleware
- [ ] Create Livewire TableView class in `app/Livewire/Table/` (NOT in `app/Http/Livewire/Table/`)
- [ ] Create all Blade views with Laravolt components
- [ ] Add routes with middleware
- [ ] Add menu configuration
- [ ] Run migration
- [ ] Confirm all tests **PASS** with `php artisan test --compact`

### Phase 3: Refactor
- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Eliminate any duplication introduced during Green phase
- [ ] Confirm all tests still pass after refactoring

---

## Common Patterns

### User-Scoped Queries
```php
$me = auth()->user();
$query = Model::query();
```

### Flash Messages
```php
return redirect()->route('{feature}.index')
    ->withSuccess('Success message');
return redirect()->back()->with('error', 'Error message');
```

### Form Integration
```php
{!! form()->open()->post()->action(route('{feature}.store')) !!}
{!! form()->bind($model)->put()->action(route('{feature}.update', $model->id)) !!}
```

### Form Buttons

**Use Laravolt button components for all form actions:**

```blade
<!-- Standard Button (for form submission) -->
<x-volt-button type="submit" icon="save">
    Save
</x-volt-button>

<x-volt-button icon="plus" label="Create {Feature}" />

<!-- Link Button (for navigation/external links) -->
<x-volt-link-button url="{{ route('{feature}.index') }}" icon="arrow left" label="Back" />
<x-volt-link-button url="{{ route('{feature}.create') }}" icon="plus" label="New" />
<x-volt-link-button url="{{ route('{feature}.edit', $feature->id) }}" icon="edit" label="Edit" />

<!-- Button with custom class -->
<x-volt-button class="secondary" icon="cancel">
    Cancel
</x-volt-button>

<!-- Link button to external URL -->
<x-volt-link-button url="https://example.com" icon="external" label="External" target="_blank" />
```

**When to use each button type:**

- **`<x-volt-button>`** - For form submissions (store, update), inline form actions, cancel buttons, or custom click handlers
- **`<x-volt-link-button>`** - For navigation, external links, or GET requests

**Complete Form Example:**

```blade
{{-- _form_.blade.php --}}
{!! form()->text('field1')->label('Field 1')->required() !!}
{!! form()->text('field2')->label('Field 2') !!}
{!! form()->textarea('description')->label('Description') !!}

<x-volt-link-button url="{{ route('{feature}.index') }}" icon="arrow left" label="Cancel" class="secondary" />
<x-volt-button icon="save" label="Save" />
```

**Button in Show View:**

```blade
{{-- show.blade.php --}}
<x-volt-app title="{Feature} Detail">
    <x-volt-panel title="Detail">
        {{-- Content --}}

        <x-volt-link-button url="{{ route('{feature}.index') }}" icon="arrow left" label="Back" />
        @if (auth()->user()->can(\App\Enums\Permission::{FEATURE}_MANAGE))
            <x-volt-link-button url="{{ route('{feature}.edit', $feature->id) }}" icon="edit" label="Edit" />
        @endif
    </x-volt-panel>
</x-volt-app>
```

### Permission-Based UI
```php
@if (auth()->user()->can(\App\Enums\Permission::{FEATURE}_MANAGE))
    <!-- Manage UI elements -->
@endif
```

This workflow ensures consistent, maintainable, and fully tested CRUD implementations that follow Laravel and Laravolt best practices.

---

## Common Issues & Fixes

### TableView Files in Wrong Location

**Problem:** TableView files located in `app/Http/Livewire/Table/` instead of `app/Livewire/Table/`.

**Fix:** Move files to the correct location and update namespaces:
```bash
# Move all TableView files to correct location
mv app/Http/Livewire/Table/*.php app/Livewire/Table/

# Or move single file
mv app/Http/Livewire/Table/FeatureTable.php app/Livewire/Table/FeatureTable.php
```

**Update namespace in each file:**
```php
// ❌ WRONG - Old namespace
namespace App\Http\Livewire\Table;

// ✅ CORRECT - New namespace
namespace App\Livewire\Table;
```

**Verify:** After moving, ensure all imports are updated:
```php
// In views and other files using the TableView
use App\Livewire\Table\FeatureTable;  // ✅ CORRECT
```

### ArchTest Failures - "Expecting X to be final"

**Problem:** The architectural testing preset requires all classes to be marked as `final` and all files to use strict types.

**Common Files That Need Fixing:**
```php
// Models (Contact.php, Product.php)
final class Contact extends Model  // ❌ class Contact extends Model

// Services (ContactService.php, ProductService.php)
final class ContactService  // ❌ class ContactService

// Controllers (ContactController.php, ProductController.php)
final class ContactController  // ❌ class ContactController extends Controller

// Requests (ContactRequest.php, ProductRequest.php)
final class ContactRequest extends FormRequest  // ❌ class ContactRequest

// Middleware (ContactView.php, ProductView.php)
final class ContactView  // ❌ class ContactView

// Livewire Components (ContactTable.php, ProductTable.php)
final class ContactTable extends TableView  // ❌ class ContactTable

// Factories (ContactFactory.php, ProductFactory.php, UserFactory.php)
final class ContactFactory extends Factory  // ❌ class ContactFactory

// Seeders (DatabaseSeeder.php)
final class DatabaseSeeder extends Seeder  // ❌ class DatabaseSeeder
```

### ArchTest Failures - "Expecting X to use strict types"

**Problem:** Missing `declare(strict_types=1);` at the top of files.

**Fix:** Add to the top of EVERY PHP file:
```php
<?php

declare(strict_types=1);  // ADD THIS LINE

namespace App\...;
```

### ArchTest Failures - "Expecting App\Http\Controllers\Controller not to be used"

**Problem:** Controllers extending the local `Controller` class.

**Fix:** Remove `extends Controller` from controllers:
```php
// ❌ WRONG
class ContactController extends Controller
{
}

// ✅ CORRECT
final class ContactController
{
}
```

**Note:** If the base `Controller.php` file exists and is unused, delete it:
```bash
rm app/Http/Controllers/Controller.php
```

### Quick Fix Checklist

When `php artisan test --compact` fails with ArchTest errors:

1. **Add strict types** to all files:
   ```bash
   # Find files missing strict types
   grep -L "declare(strict_types=1)" app/Models/*.php
   grep -L "declare(strict_types=1)" app/Services/*.php
   grep -L "declare(strict_types=1)" app/Http/Controllers/*.php
   grep -L "declare(strict_types=1)" app/Http/Requests/*.php
   grep -L "declare(strict_types=1)" app/Http/Middleware/*.php
   grep -L "declare(strict_types=1)" app/Livewire/Table/*.php
   grep -L "declare(strict_types=1)" database/factories/*.php
   grep -L "declare(strict_types=1)" database/seeders/*.php
   ```

2. **Make classes final:**
   - Models: `final class ModelName extends Model`
   - Services: `final class ServiceName`
   - Controllers: `final class ControllerName` (no extends)
   - Requests: `final class RequestName extends FormRequest`
   - Middleware: `final class MiddlewareName`
   - Livewire: `final class ComponentName extends TableView`
   - Factories: `final class FactoryName extends Factory`
   - Seeders: `final class SeederName extends Seeder`

3. **Run code formatting:**
   ```bash
   vendor/bin/pint --dirty --format agent
   ```

4. **Verify all tests pass:**
   ```bash
   php artisan test --compact
   # Should show: "Tests: 86+ passed"
   ```

### Prevention Tips

**When creating new classes, always use this template:**
```php
<?php

declare(strict_types=1);

namespace App\{Namespace};

use {RequiredImports};

final class {ClassName}
{
    // class implementation
}
```

**When extending framework classes:**
- Controllers: **DO NOT** extend local Controller, create standalone
- Models: `final class ModelName extends Model`
- Requests: `final class RequestName extends FormRequest`
- Factories: `final class FactoryName extends Factory`
- Livewire: `final class ComponentName extends TableView/Component`

This proactive approach prevents ArchTest failures and ensures clean, maintainable code architecture.
