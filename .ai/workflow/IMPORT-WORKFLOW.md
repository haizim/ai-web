# Import Feature Workflow Guidelines

Based on the established patterns in the Bank and Bank Account import implementations, these guidelines define the standard workflow for creating complete import features in the Laravolt ecosystem.

## Role Separation

**CRITICAL:** This workflow has clear role separation between USER and AI AGENT:

### USER Responsibilities:
- ✅ Create import documentation at `docs/imports/{resource}.md`
- ✅ Define model name, template structure, and validation rules
- ✅ Provide complete specification before AI implementation

### AI AGENT Responsibilities:
- ✅ Read and understand the user-created documentation
- ✅ Write failing tests FIRST (Red)
- ✅ Implement import feature to make tests pass (Green)
- ✅ Refactor while keeping tests green
- ❌ **NEVER create the import documentation**

**Workflow Rule:** Documentation is the SINGLE SOURCE OF TRUTH. AI agent implements EXACTLY what is specified in the documentation.

## Workflow Overview

The import feature workflow follows **Test Driven Development (TDD)** with a **Red → Green → Refactor** cycle:

1. **Documentation Phase (USER)** - User creates import specification document at `docs/imports/{resource}.md`
2. **Red Phase (AI AGENT)** - AI agent reads documentation and writes failing tests that define the expected behavior
3. **Green Phase (AI AGENT)** - AI agent writes minimum implementation to make all tests pass
4. **Refactor Phase (AI AGENT)** - AI agent cleans up code while keeping tests green

**Critical Workflow Rule:**
- **USER** creates the documentation → AI agent reads, writes tests, then implements
- **AI agent NEVER creates the import documentation**
- **AI agent writes tests BEFORE implementation**

**AI Agent Workflow:**
```
User Request: "Create import feature for {resource}"
    ↓
AI Agent: Check if docs/imports/{resource}.md exists
    ↓ (if NO)
    → Ask user to create documentation first
    ↓ (if YES)
AI Agent: Read docs/imports/{resource}.md
    ↓
AI Agent: Extract requirements (model, columns, validation, Data Map)
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

## Phase 1: Documentation Phase (User Step)

### 1.1 Create Import Documentation

**Step:** User creates `docs/imports/{resource}.md`

**IMPORTANT:** This documentation MUST be created by the USER before AI implementation begins. The AI agent will read and use this documentation as the blueprint for tests and implementation.

The user should create a comprehensive import specification document following the structure below.

**Documentation Structure:**

```markdown
# Import {Resource Name}

## Model

{ModelName}

## Form

- File : file (file xlsx)

## Template

### Sheet : Import

#### Columns

- {Column Name 1} : {data type/reference}
- {Column Name 2} : {data type/reference}
- ...

## Import

### Rules

- {field_1} : {validation rules}
- {field_2} : {validation rules}
- ...

### Data Map

- {excel_column_1} : {model_field_1}
- {excel_column_2} : {model_field_2}
- ...
```

**IMPORTANT:** The **Data Map** section is CRITICAL for AI agents to understand how to map Excel column names to model field names.

**Examples:**

**Simple Import (Single Sheet):**
```markdown
# Import Bank

## Model

Bank

## Form

- File : file (file xlsx)

## Template

### Sheet : Import

#### Columns

- Kode Bank : string
- Nama Bank : string

## Import

### Rules

- kode_bank : required|string|max:10
- nama_bank : required|string|max:255

### Data Map

- kode_bank : code
- nama_bank : name
```

**Complex Import (Multiple Sheets with References):**
```markdown
# Import Bank Accounts

## Model

BankAccount

## Form

- File : file (file xlsx)

## Template

### Sheet : Import

#### Columns

- Kode Bank : {bank->code}
- Nama Bank : =VLOOKUP(A2,Bank!A:B,2,FALSE)
- Nomor Rekening : random_int(100000000, 999999999)
- Atas Nama : fake()->name

### Sheet : Bank

#### Model

Bank

#### Columns

- Kode Bank : string
- Nama Bank : string

## Import

### Rules

- kode_bank : required|string|exists:banks,code
- nama_bank : nullable|string
- nomor_rekening : required|numeric
- atas_nama : required|string|max:255

### Data Map

- kode_bank : get bank's id by bank's code
- nomor_rekening : account_number
- atas_nama : by_name
```

**Documentation Conventions:**
- Use kebab-case for filename: `{resource}.md`
- Use singular PascalCase for model names
- Specify column data types: `string`, `numeric`, `date`, etc.
- For referenced columns: use `{model->field}` notation
- For Excel formulas: use actual Excel syntax
- For example values: use PHP function names as hints
- **Data Map section is REQUIRED** - maps Excel columns to model fields
  - Direct mapping: `excel_column : model_field`
  - Reference lookup: `excel_column : get related's id by related's field`
  - Skip non-model fields (display-only): omit from Data Map

---

## Phase 2: Write Failing Tests — Red (AI Agent)

> **Goal:** Define the expected import behavior through tests BEFORE writing any implementation. All tests should fail at this stage.

### AI Agent First Step: Read Documentation

**CRITICAL:** When user requests import feature implementation, the AI agent MUST:

1. **Verify documentation exists:**
   ```bash
   ls docs/imports/{resource}.md
   ```

2. **If documentation does NOT exist:**
   ```
   STOP and ask user to create the documentation first:
   "I need you to create the import documentation at docs/imports/{resource}.md
    before I can implement the import feature."
   ```

3. **If documentation exists:** read and extract:
   - Model name
   - Template columns and data types
   - Import validation rules
   - **Data Map for field mappings (CRITICAL)**

**Understanding Data Map:**
- **Direct mapping:** `excel_column : model_field`
  - Example: `kode_bank : code` → Excel column `kode_bank` maps to model field `code`
- **Reference lookup:** `excel_column : get related's id by related's field`
  - Example: `kode_bank : get bank's id by bank's code` → look up Bank by `code`, use its `id`
- **Display-only columns:** Omitted from Data Map (not stored in database)

### 2.1 Feature Tests

**File:** `tests/Feature/{Resource}/{Resource}ControllerTest.php`

Add import tests to existing test file (or create new file).

```php
<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Exports\{Resource}TemplateExport;
use App\Models\{Resource};
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravolt\Auth\Models\Permission as LaravoltPermission;
use Laravolt\Auth\Models\Role;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->viewPermission = LaravoltPermission::firstOrCreate(['name' => Permission::{RESOURCE}_VIEW]);
    $this->managePermission = LaravoltPermission::firstOrCreate(['name' => Permission::{RESOURCE}_MANAGE]);

    $this->viewerRole = Role::firstOrCreate(['name' => '{Resource}Viewer']);
    $this->viewerRole->addPermission($this->viewPermission);

    $this->managerRole = Role::firstOrCreate(['name' => '{Resource}Manager']);
    $this->managerRole->addPermission($this->viewPermission);
    $this->managerRole->addPermission($this->managePermission);

    Gate::define(Permission::{RESOURCE}_VIEW, fn ($user) => $user->hasPermission(Permission::{RESOURCE}_VIEW));
    Gate::define(Permission::{RESOURCE}_MANAGE, fn ($user) => $user->hasPermission(Permission::{RESOURCE}_MANAGE));

    $this->viewerUser = User::factory()->create();
    $this->viewerUser->addRole($this->viewerRole);

    $this->managerUser = User::factory()->create();
    $this->managerUser->addRole($this->managerRole);

    $this->userWithoutPermission = User::factory()->create();

    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('it_can_download_{resource}_template_with_view_permission', function (): void {
    $response = $this->actingAs($this->viewerUser)
        ->get(route('{resource}.template'));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('it_cannot_download_{resource}_template_without_view_permission', function (): void {
    $response = $this->actingAs($this->userWithoutPermission)
        ->get(route('{resource}.template'));

    $response->assertStatus(401);
});

test('it_can_see_import_section_with_manage_permission', function (): void {
    $response = $this->actingAs($this->managerUser)
        ->get(route('{resource}.index'));

    $response->assertStatus(200);
    $response->assertSee('Import');
});

test('it_cannot_see_import_section_without_manage_permission', function (): void {
    $response = $this->actingAs($this->viewerUser)
        ->get(route('{resource}.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Import');
});

test('it_can_import_{resource}_with_valid_template_file', function (): void {
    Storage::fake('local');

    $templateFile = 'test_import_controller.xlsx';
    Excel::store(new {Resource}TemplateExport, $templateFile, 'local');

    $file = new UploadedFile(
        Storage::path($templateFile),
        'test_import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $response = $this->actingAs($this->managerUser)
        ->post(route('{resource}.import'), ['file' => $file]);

    $response->assertRedirect(route('{resource}.index'));
    $response->assertSessionHasNoErrors();
});

test('it_cannot_import_{resource}_without_manage_permission', function (): void {
    Storage::fake('local');

    $templateFile = 'test_import_no_permission.xlsx';
    Excel::store(new {Resource}TemplateExport, $templateFile, 'local');

    $file = new UploadedFile(
        Storage::path($templateFile),
        'test_import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $response = $this->actingAs($this->viewerUser)
        ->post(route('{resource}.import'), ['file' => $file]);

    $response->assertStatus(403);
});

test('it_cannot_import_{resource}_with_invalid_file_format', function (): void {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->managerUser)
        ->post(route('{resource}.import'), ['file' => $file]);

    $response->assertSessionHasErrors(['file']);
});

test('it_cannot_import_{resource}_without_file', function (): void {
    $response = $this->actingAs($this->managerUser)
        ->post(route('{resource}.import'), []);

    $response->assertSessionHasErrors(['file']);
});
```

### 2.2 Unit Tests — Service

**File:** `tests/Unit/Services/{Resource}ServiceTest.php`

Add import unit tests to existing test file (or create new file).

```php
<?php

declare(strict_types=1);

use App\Exports\{Resource}TemplateExport;
use App\Models\{Resource};
use App\Services\{Resource}Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

uses(RefreshDatabase::class);

test('it_can_download_{resource}_template', function (): void {
    $response = {Resource}Service::template();

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    expect($response->getFile()->getExtension())->toBe('xlsx');
});

test('it_can_import_{resource}_from_excel_file', function (): void {
    Storage::fake('local');

    $initialCount = {Resource}::count();

    $templateFile = 'test_template_import.xlsx';
    Excel::store(new {Resource}TemplateExport, $templateFile, 'local');

    {Resource}Service::import(Storage::path($templateFile));

    expect({Resource}::count())->toBeGreaterThan($initialCount);
});

test('it_can_generate_template_and_import_{resource}', function (): void {
    {Resource}::factory()->create(['field' => 'TEST_VALUE']);

    $templateResponse = {Resource}Service::template();
    $templatePath = $templateResponse->getFile()->getRealPath();

    expect(file_exists($templatePath))->toBeTrue();

    {Resource}Service::import($templatePath);

    expect({Resource}::where('field', 'TEST_VALUE')->exists())->toBeTrue();
});
```

### 2.3 Verify Tests Are Failing

Run tests to confirm all newly written tests fail (expected at this stage):

```bash
php artisan test --compact
```

> All new tests should show as **FAILED**. If a test passes without implementation, the test is not testing the right thing.

---

## Phase 3: Write Minimum Implementation — Green (AI Agent)

> **Goal:** Write the minimum code necessary to make all failing tests pass. Do not add features beyond what the tests require.

### 3.1 Create Import Class

**File:** `app/Imports/{Resource}Import.php`

Use the **Data Map** section from documentation to map Excel columns to model fields.

**Simple Import Pattern (No External Dependencies):**

*Documentation Data Map:*
```markdown
### Data Map
- kode_bank : code
- nama_bank : name
```

*Implementation:*
```php
<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\{Resource};
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

final class {Resource}Import implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithValidation
{
    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {
            {Resource}::updateOrCreate(
                ['code' => $row['kode_bank']],
                [
                    'name' => $row['nama_bank'],
                    'code' => $row['kode_bank'],
                ]
            );
        }
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'kode_bank' => 'required|string|max:255',
            'nama_bank' => 'required|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'kode_bank.required' => 'Kode Bank wajib diisi.',
            'kode_bank.max' => 'Kode Bank maksimal 255 karakter.',
            'nama_bank.required' => 'Nama Bank wajib diisi.',
        ];
    }
}
```

**Complex Import Pattern (With External References & Error Handling):**

*Documentation Data Map:*
```markdown
### Data Map
- kode_bank : get bank's id by bank's code
- nomor_rekening : account_number
- atas_nama : by_name
```

*Implementation:*
```php
<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Bank;
use App\Models\{Resource};
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;

final class {Resource}Import implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithMultipleSheets, WithValidation
{
    /**
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * @return array<int, self>
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    /**
     * @param Collection<int, mixed> $rows
     */
    public function collection(Collection $rows): void
    {
        $banks = Bank::all();

        foreach ($rows as $row) {
            $bank = $banks->where('code', $row['kode_bank'])->first();

            if (! $bank) {
                $this->errors[] = "Bank dengan kode '{$row['kode_bank']}' tidak ditemukan.";
                continue;
            }

            {Resource}::create([
                'bank_id' => $bank->id,
                'account_number' => $row['nomor_rekening'],
                'by_name' => $row['atas_nama'],
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'kode_bank' => 'required|string|exists:banks,code',
            'nama_bank' => 'nullable|string',
            'nomor_rekening' => 'required|numeric',
            'atas_nama' => 'required|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'kode_bank.required' => 'Kode Bank wajib diisi.',
            'kode_bank.exists' => 'Kode Bank tidak ditemukan.',
            'nomor_rekening.required' => 'Nomor Rekening wajib diisi.',
            'atas_nama.required' => 'Atas Nama wajib diisi.',
        ];
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }
}
```

**Import Class Requirements:**
- **CRITICAL:** Must be marked as `final class`
- Add `declare(strict_types=1);` at the top
- Implement: `SkipsEmptyRows`, `ToCollection`, `WithHeadingRow`, `WithValidation`
- Add `WithMultipleSheets` for multi-sheet imports
- Use `updateOrCreate` for upsert logic or `create` for insert-only
- Validation rules must use Excel column names (not model field names)

### 3.2 Create Template Export Classes

**File:** `app/Exports/{Resource}TemplateExport.php` (Main Template)
**File:** `app/Exports/{Resource}ImportTemplateExport.php` (Import Sheet)

**Simple Template Pattern (Single Sheet):**

```php
<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

final class {Resource}TemplateExport implements FromCollection, ShouldAutoSize, WithTitle
{
    /**
     * @return Collection<int, array<int, string>>
     */
    public function collection(): Collection
    {
        return collect([
            ['Column Header 1', 'Column Header 2'],
            ['Example Value 1', 'Example Value 2'],
        ]);
    }

    public function title(): string
    {
        return 'Import';
    }
}
```

**Complex Template Pattern (Multiple Sheets):**

**Main Template File:**
```php
<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class {Resource}TemplateExport implements WithMultipleSheets
{
    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new {Resource}ImportTemplateExport,
            new {RelatedResource}Export,
        ];
    }
}
```

**Import Sheet Template:**
```php
<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\{RelatedModel};
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

final class {Resource}ImportTemplateExport implements FromCollection, ShouldAutoSize, WithTitle
{
    /**
     * @return Collection<int, array<int, string>>
     */
    public function collection(): Collection
    {
        $related = {RelatedModel}::first();

        $headers = ['Column 1', 'Column 2', 'Column 3'];

        $exampleRow = $related
            ? [$related->code, '=VLOOKUP(A2, RelatedSheet!A:B, 2, FALSE)', fake()->name()]
            : ['EXAMPLE_CODE', '=VLOOKUP(A2, RelatedSheet!A:B, 2, FALSE)', fake()->name()];

        return collect([$headers, $exampleRow]);
    }

    public function title(): string
    {
        return 'Import';
    }
}
```

### 3.3 Update Service Layer

**File:** `app/Services/{Resource}Service.php`

```php
use App\Exports\{Resource}TemplateExport;
use App\Imports\{Resource}Import;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

public static function template(): BinaryFileResponse
{
    return Excel::download(new {Resource}TemplateExport, '{resource}-template.xlsx');
}

public static function import(string $file): void
{
    Excel::import(new {Resource}Import, $file);
}
```

### 3.4 Create Import Request Validation

**File:** `app/Http/Requests/{Resource}ImportRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class {Resource}ImportRequest extends FormRequest
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
        return [
            'file' => 'required|mimes:xlsx',
        ];
    }
}
```

### 3.5 Update Controller

**File:** `app/Http/Controllers/{Resource}Controller.php`

```php
use App\Http\Requests\{Resource}ImportRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

public function template(): BinaryFileResponse
{
    return {Resource}Service::template();
}

public function import({Resource}ImportRequest $request): RedirectResponse
{
    {Resource}Service::import($request->file('file'));

    return redirect()->route('{resource}.index')
        ->withSuccess('{Resource} berhasil diimport');
}
```

### 3.6 Update Routes

**File:** `routes/web.php`

```php
Route::middleware({Resource}View::class)->group(function (): void {
    Route::get('{resource}/export', [{Resource}Controller::class, 'export'])->name('{resource}.export');
    Route::get('{resource}/template', [{Resource}Controller::class, 'template'])->name('{resource}.template');
    Route::post('{resource}/import', [{Resource}Controller::class, 'import'])->name('{resource}.import');
    Route::resource('{resource}', {Resource}Controller::class);
});
```

**Note:** Place template and import routes before the resource route to avoid conflicts.

### 3.7 Update Views

**File:** `resources/views/{resource}/index.blade.php`

```blade
@if (auth()->user()->can(\App\Enums\Permission::{RESOURCE}_MANAGE))
<div class="title">
    <i class="dropdown icon"></i>
    Import
</div>
<div class="content">
    <p>
        {!! form()->open()->post()->action(route('{resource}.import'))->id('import')->multipart() !!}
        {!! form()->file('file')->label('File')->required() !!}
        <x-volt-link-button class="secondary" url="{{ route('{resource}.template') }}" icon="file excel">
            Template
        </x-volt-link-button>
        <x-volt-button type="submit">
            Import
        </x-volt-button>
        {!! form()->close() !!}
    </p>
</div>
@endif
```

### 3.8 Verify All Tests Pass

```bash
php artisan test --compact
```

> All tests must pass before proceeding. If any test fails, fix the implementation — **do not modify the test** unless the test itself was written incorrectly.

---

## Phase 4: Refactor (AI Agent)

> **Goal:** Improve code quality without changing behavior. Tests must remain green throughout.

- Extract duplicated logic
- Improve naming clarity
- Optimize queries (load related models once, not per-row)
- Run code formatting: `vendor/bin/pint --dirty --format agent`
- Re-run tests after every refactor step: `php artisan test --compact`

---

## Code Quality Standards

### Type Safety

- **CRITICAL:** Always use `declare(strict_types=1);` at the top of EVERY PHP file
- Use explicit return type declarations for all methods
- Use proper type hints for method parameters
- **All classes must be marked as `final`**
- Use PHPDoc blocks for array shapes and complex types

### Architectural Requirements

- **All Import classes must be `final`**
- **All Export classes must be `final`**
- **All Request classes must be `final`**
- Use Laravel Excel interfaces for import/export functionality
- Follow Laravel and Laravolt coding conventions

### Error Handling

- Provide clear validation messages in user-friendly language
- Handle external reference failures gracefully
- Collect and report import errors for complex imports
- Use flash messages for user feedback

---

## Workflow Checklist

### Phase 1: Documentation (USER)

**CRITICAL:** This phase must be completed by the USER before AI agent begins work.

- [ ] USER creates `docs/imports/{resource}.md`
- [ ] USER documents model name
- [ ] USER documents form specification
- [ ] USER documents template structure (columns, data types)
- [ ] USER documents import validation rules
- [ ] **USER creates Data Map section (REQUIRED)**
  - [ ] Map Excel columns to model fields
  - [ ] Specify direct mappings: `excel_column : model_field`
  - [ ] Specify reference lookups: `excel_column : get related's id by related's field`
  - [ ] Omit display-only columns from Data Map
- [ ] USER includes related sheets if applicable

### Phase 2: Write Failing Tests — Red (AI AGENT)

- [ ] Read `docs/imports/{resource}.md` and extract Data Map
- [ ] Write feature tests (template download, import, permission checks, file validation)
- [ ] Write unit tests for service (template download, import, round-trip)
- [ ] Confirm all new tests **FAIL** with `php artisan test --compact`

### Phase 3: Make Tests Pass — Green (AI AGENT)

- [ ] Create `app/Imports/{Resource}Import.php` using Data Map
- [ ] Create `app/Exports/{Resource}TemplateExport.php`
- [ ] Create `app/Exports/{Resource}ImportTemplateExport.php` (if complex)
- [ ] Add `template()` and `import()` to `app/Services/{Resource}Service.php`
- [ ] Create `app/Http/Requests/{Resource}ImportRequest.php`
- [ ] Add `template()` and `import()` to `app/Http/Controllers/{Resource}Controller.php`
- [ ] Update `routes/web.php` (add template & import routes)
- [ ] Update `resources/views/{resource}/index.blade.php` (add import section)
- [ ] Confirm all tests **PASS** with `php artisan test --compact`

### Phase 4: Refactor (AI AGENT)

- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Eliminate any duplication introduced during Green phase
- [ ] Confirm all tests still pass

---

## Common Patterns

### Data Map Patterns

**Direct Mapping (1:1):**
```markdown
### Data Map
- kode_bank : code
- nama_bank : name
```
Implementation: `$model->code = $row['kode_bank']`

**Reference Lookup (Many-to-One):**
```markdown
### Data Map
- kode_bank : get bank's id by bank's code
```
Implementation: `$bank = Bank::where('code', $row['kode_bank'])->first(); $model->bank_id = $bank->id`

**Display-Only (Not Stored):**
```markdown
# Note: 'nama_bank' column in Excel is display-only (VLOOKUP), not stored
```
Implementation: Skip 'nama_bank' when creating model

**Mixed Pattern:**
```markdown
### Data Map
- kode_bank : get bank's id by bank's code
- nomor_rekening : account_number
- atas_nama : by_name
# 'nama_bank' is display-only, omitted from Data Map
```

### Simple Import (No External Dependencies)

Use `updateOrCreate` for idempotent imports:
```php
{Resource}::updateOrCreate(
    ['unique_key' => $row['key']],
    [
        'field1' => $row['column1'],
        'field2' => $row['column2'],
        'unique_key' => $row['key'],
    ]
);
```

### Complex Import (With External References)

Load related models once for performance (not per-row):
```php
$relatedModels = RelatedModel::all();

foreach ($rows as $row) {
    $related = $relatedModels->where('code', $row['code'])->first();

    if (! $related) {
        $this->errors[] = "Related model not found: {$row['code']}";
        continue;
    }

    {Resource}::create([...]);
}
```

### Template with Example Data

Use existing data for realistic examples:
```php
$existing = Model::first();

$example = $existing
    ? [$existing->field, fake()->name()]
    : ['EXAMPLE', fake()->name()];
```

### Excel Formulas in Templates

Include VLOOKUP for referenced data:
```php
'=VLOOKUP(A2, RelatedSheet!A:B, 2, FALSE)'
```

---

## Common Issues & Solutions

### Data Map Not Followed

**Problem:** Import fails or data goes to wrong fields.

**Solution:** Always follow the Data Map section from documentation:
```php
// WRONG: Using Excel column name as model field
$model->kode_bank = $row['kode_bank'];

// CORRECT: Using Data Map mapping
// Data Map: kode_bank : code
$model->code = $row['kode_bank'];
```

### Import Fails Silently

**Problem:** Import succeeds but no data created.

**Solution:** Check column names in Excel match `WithHeadingRow` format. Excel headers should use snake_case.

### Validation Not Working

**Problem:** Invalid data passes validation.

**Solution:** Validation rules must use Excel column names (not database field names):
```php
// Use Excel column name (from documentation)
'kode_bank' => 'required'

// NOT database field name (from Data Map)
'code' => 'required' // WRONG
```

### Permission Denied on Import

**Problem:** 403 error when importing.

**Solution:** Verify user has `{RESOURCE}_MANAGE` permission, not just `{RESOURCE}_VIEW`.

### Template Download Shows Raw Data

**Problem:** Template downloads as CSV or shows raw text.

**Solution:** Ensure route returns BinaryFileResponse and controller method exists:
```php
public function template(): BinaryFileResponse
{
    return {Resource}Service::template();
}
```

---

## Quick Reference

### Import Interfaces

- `SkipsEmptyRows` - Ignore empty rows in Excel
- `ToCollection` - Process data as Laravel Collection
- `WithHeadingRow` - Use first row as column names
- `WithValidation` - Enable validation during import
- `WithMultipleSheets` - Handle multi-sheet Excel files

### Export Interfaces

- `FromCollection` - Export from Laravel Collection
- `ShouldAutoSize` - Auto-adjust column widths
- `WithTitle` - Set sheet title
- `WithMultipleSheets` - Create multi-sheet Excel files

### Service Facade

```php
use Maatwebsite\Excel\Facades\Excel;

// Store for testing
Excel::store(new Export, 'filename.xlsx', 'local');

// Download for export
Excel::download(new Export, 'filename.xlsx');

// Import
Excel::import(new Import, 'filepath.xlsx');
```

This workflow ensures consistent, maintainable, and fully tested import implementations that follow Laravel and Laravolt best practices.
