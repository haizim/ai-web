# Export Feature Workflow Guidelines

**TDD approach:** Red → Green → Refactor

**Workflow:** Read docs → Write failing tests → Implement → Refactor

## Phase 1: Documentation (User)

**File:** `docs/exports/{feature}.md`

```markdown
    # Export {Feature Name}

    ## File Name
    {feature_name}-{Dynamic Value}-{Ymd-His}.xlsx

    ## Params
    - {param_name} (type) - description

    ## Sheets

    ### {Sheet Name}

    #### Query
    ```sql
    select column1 alias1, column2 alias2
    from main_table t1 
        join related_table t2 on t1.id = t2.foreign_key 
    where t.condition = {$paramName}
    order by t.sort_column asc
    ```

    #### Columns
    - Column Header (database_column) : Type
    - Column Header (database_column) : Type
```

---

## Phase 2: Write Failing Tests (Red)

> **Goal:** Define the expected export behavior through tests BEFORE writing any implementation. All tests should fail at this stage.

### 2.1 Feature Tests

**File:** `tests/Feature/{Feature}/{Feature}ControllerTest.php`

```php
<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\{RelatedModel};
use App\Models\{Model};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravolt\Auth\Models\Permission as LaravoltPermission;
use Laravolt\Auth\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->exportPermission = LaravoltPermission::firstOrCreate(['name' => Permission::{FEATURE}_VIEW]);

    $this->exporterRole = Role::firstOrCreate(['name' => '{Feature}Exporter']);
    $this->exporterRole->addPermission($this->exportPermission);

    Gate::define(Permission::{FEATURE}_VIEW, function ($user) {
        return $user->hasPermission(Permission::{FEATURE}_VIEW);
    });

    $this->userWithPermission = User::factory()->create();
    $this->userWithPermission->addRole($this->exporterRole);

    $this->userWithoutPermission = User::factory()->create();

    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('it_can_export_{feature}_with_view_permission', function (): void {
    $entity = {RelatedModel}::factory()->create();
    {Model}::factory()->count(3)->create(['foreign_key' => $entity->id]);

    $response = $this->actingAs($this->userWithPermission)
        ->get(route('{feature}.export', ['param_id' => $entity->id]));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('it_cannot_export_{feature}_without_view_permission', function (): void {
    $entity = {RelatedModel}::factory()->create();

    $response = $this->actingAs($this->userWithoutPermission)
        ->get(route('{feature}.export', ['param_id' => $entity->id]));

    $response->assertStatus(401);
});
```

### 2.2 Unit Tests

**File:** `tests/Unit/Services/{Feature}ServiceTest.php`

```php
<?php

declare(strict_types=1);

use App\Models\{RelatedModel};
use App\Models\{Model};
use App\Services\{Feature}Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

uses(RefreshDatabase::class);

test('it_can_export_{feature}_by_param_id', function (): void {
    $entity = {RelatedModel}::factory()->create(['name' => 'Test']);
    {Model}::factory()->count(3)->create(['foreign_key' => $entity->id]);

    $response = {Feature}Service::export($entity->id);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    expect($response->getFile()->getExtension())->toBe('xlsx');
});

test('it_throws_exception_when_exporting_non_existent_entity', function (): void {
    {Feature}Service::export(999);
})->throws(ModelNotFoundException::class);
```

### 2.3 Verify Tests Are Failing

Run tests to confirm all newly written tests fail (expected at this stage):

```bash
php artisan test --compact
```

> All new tests should show as **FAILED**. If a test passes without implementation, the test is not testing the right thing.

---

## Phase 3: Write Minimum Implementation (Green)

> **Goal:** Write the minimum code necessary to make all failing tests pass. Do not add features beyond what the tests require.

### 3.1 Permission

**File:** `app/Enums/Permission.php`

```php
// {FEATURE}
const {FEATURE}_VIEW = '{feature}:view';
```

### 3.2 Export Class

**File:** `app/Exports/{Feature}Export.php`

```php
<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\{MainModel};
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final class {Feature}Export implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly int $paramId) {}

    public function query()
    {
        return {MainModel}::query()
            ->where('foreign_key_column', $this->paramId)
            ->with('relationship')
            ->orderBy('sort_column', 'asc');
    }

    public function map($data): array
    {
        return [
            $data->relationship->field,
            $data->field1,
            $data->date_field->format('d/m/Y H:i'),
        ];
    }

    public function headings(): array
    {
        return ['Column Header 1', 'Column Header 2', 'Column Header 3'];
    }

    public function columnFormats(): array
    {
        return ['C' => 'dd/mm/yyyy h:mm'];
    }

    public function title(): string
    {
        return '{Sheet Name}';
    }
}
```

### 3.3 Service Layer

**File:** `app/Services/{Feature}Service.php`

```php
use App\Exports\{Feature}Export;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

public static function export(int $paramId): BinaryFileResponse
{
    $now = date('Ymd-His');
    $entity = RelatedModel::findOrFail($paramId);
    $fileName = "{feature}-{$entity->name}-{$now}.xlsx";

    return Excel::download(new {Feature}Export($paramId), $fileName);
}
```

### 3.4 Controller

**File:** `app/Http/Controllers/{Feature}Controller.php`

```php
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

public function export(Request $request): BinaryFileResponse
{
    Gate::authorize(Permission::{FEATURE}_VIEW);
    return {Feature}Service::export((int) $request->param_id);
}
```

### 3.5 UI & Routes

**UI:** `resources/views/{feature}/index.blade.php`

```blade
<div class="ui styled fluid accordion" id="accordion">
    <div class="title"><i class="dropdown icon"></i>Export</div>
    <div class="content">
        {!! form()->get()->action(route('{feature}.export')) !!}
        @php $queryRelated = 'SELECT id, name from related_table'; @endphp
        {!! form()->dropdownDB('param_id', $queryRelated, 'id', 'name')
            ->label('Parameter')->placeholder('-- Select --') !!}
        <x-volt-button>Export</x-volt-button>
        {!! form()->close() !!}
    </div>
</div>
<script>$('.ui.accordion').accordion();</script>
```

**Routes:** `routes/web.php`

```php
Route::middleware({Feature}View::class)->group(function (): void {
    Route::get('{feature}/export', [{Feature}Controller::class, 'export'])->name('{feature}.export');
    Route::resource('{feature}', {Feature}Controller::class);
});
```

### 3.6 Verify All Tests Pass

```bash
php artisan test --compact
```

> All tests must pass before proceeding. If any test fails, fix the implementation — **do not modify the test** unless the test itself was written incorrectly.

---

## Phase 4: Refactor

> **Goal:** Improve code quality without changing behavior. Tests must remain green throughout.

- Extract duplicated logic
- Improve naming clarity
- Run code formatting: `vendor/bin/pint --dirty --format agent`
- Re-run tests after every refactor step: `php artisan test --compact`

---

## Checklist

### Phase 1: Documentation
- [ ] Read `docs/exports/{feature}.md` with SQL query and column mapping

### Phase 2: Write Failing Tests (Red)
- [ ] Write feature tests (success + permission denied)
- [ ] Write unit tests (export + exception)
- [ ] Confirm all new tests **FAIL** with `php artisan test --compact`

### Phase 3: Make Tests Pass (Green)
- [ ] Add permission constant to `app/Enums/Permission.php`
- [ ] Create `app/Exports/{Feature}Export.php`
- [ ] Add export method to service
- [ ] Add export method to controller
- [ ] Add export form to index view
- [ ] Add export route
- [ ] Confirm all tests **PASS** with `php artisan test --compact`

### Phase 4: Refactor
- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Confirm all tests still pass

---

## Common Issues

**Route conflicts:** Place export route before resource route

**Required imports:**
```php
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
```

**Dependency:** `composer require maatwebsite/excel`
