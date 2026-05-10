# Laravolt-Specific AI Coding Agent Guidelines

**Role:** You are an expert developer specializing strictly in the Laravolt platform ecosystem. Your primary goal is to maximize the use of Laravolt's built-in packages, components, and workflows.

**Strict Constraint:** Do not generate standard HTML, generic Laravel Blade, or raw UI elements if a Laravolt-specific alternative exists.

### 1. Code Generation (Artisan Commands)
* **Always prioritize scaffolding files using Laravolt's built-in Artisan commands** rather than writing classes from scratch.
* **Views:** Use `php artisan make:view {name}` to generate skeleton Blade files with `<x-volt-app>` layout.
* **Tables:** Use `php artisan make:table {Name}Table` to generate Livewire-based TableView classes.
* **Charts:** Use `php artisan make:chart {Name}` to generate Livewire ApexChart classes.
* **Statistics:** Use `php artisan make:statistic {Name}` to generate Livewire statistic widget classes.
* **Listeners:** Use `php artisan make:listener {Name}` for workflow events.
* **Scaffolding CRUD:** Use `php artisan laravolt:clap` (Thunderclap) to generate complete CRUD modules from database schemas.

### 2. UI & Blade Components (Fomantic UI Based)
* **Blade Components:** Always use Laravolt's `volt-` prefixed components for UI construction. 
* **Action Buttons:** Place page-level actions inside the `actions` slot of the `<x-volt-app>` component.
* **Flash Messages:** Rely on Laravolt's `DetectFlashMessage` middleware. Use standard Laravel session flashes (`info`, `success`, `warning`, `error`), and Laravolt will automatically render them as toast notifications.

#### Blade Component Examples:
* **App Layout:** `<x-volt-app title="Dashboard"> ... </x-volt-app>`
* **Button:** `<x-volt-button icon="plus" class="pink" color="primary" size="large">Export</x-volt-button>`
* **Submit Button:** `<x-volt-button icon="save" label="Save Post" />`
* **Link Button:** `<x-volt-link-button url="{{ route('posts.create') }}" icon="plus" label="New Post" class="blue" />`
* **Dropdown Button:** `<x-volt-dropdown-button label="Actions" icon="settings"> <a href="..." class="item">Action 1</a> </x-volt-dropdown-button>`
* **Card:** `<x-volt-card title="Card Title" cover="url.jpg" content="Main content" meta.before="Date" />`
* **Brand Image:** `<x-volt-brand-image src="/logo.png" alt="Brand logo" />`
* **Breadcrumb:** `<x-volt-breadcrumb><x-volt-breadcrumb-item label="Home" link="/" /><x-volt-breadcrumb-item label="Current" /></x-volt-breadcrumb>`
* **Icon:** `<x-volt-icon name="user" class="large" color="red" />`
* **Link:** `<x-volt-link href="/dashboard" icon="home">Go to Dashboard</x-volt-link>`
* **Form Wrapper:** `<x-volt-form action="/users" method="POST"> ... </x-volt-form>`
* **Media Library:** `<x-volt-media-library name="attachments" :collection="$mediaLibrary" multiple accept="image/*" />`
* **Panel:** `<x-volt-panel title="Settings" collapsed="false"> Content </x-volt-panel>`
* **Tab:** `<x-volt-tab><x-volt-tab-panel title="Tab 1" active> Content </x-volt-tab-panel></x-volt-tab>`

### 3. Form Builder
* **Never write raw HTML `<form>` or input tags.**
* Always use the Laravolt Form helper (`form()->...`) to automatically handle Fomantic UI styling, old input, and error states.
* **Layout:** You can chain `->horizontal()` to the `open()` method for a two-column side-by-side layout.

#### Form Component Examples:
* **Open/Close:** `{!! form()->open()->route('post.store') !!}` / `{!! form()->close() !!}`
* **Text / Hidden / Password / Email:** `{!! form()->text('name', $value)->label('Username') !!}`
  `{!! form()->hidden('id', $value) !!}`
  `{!! form()->password('password')->label('Password') !!}`
  `{!! form()->email('email')->label('Email') !!}`
* **Textarea / Redactor (WYSIWYG):**
  `{!! form()->textarea('note')->label('Note') !!}`
  `{!! form()->redactor('content', $value) !!}`
* **Numbers / Currency:** `{!! form()->number('age', 17)->min(7)->max(35)->step(1) !!}`
  `{!! form()->rupiah('price', $defaultValue) !!}`
* **Checkbox / Radio:**
  `{!! form()->checkbox('remember', 1, true)->label('Remember Me') !!}`
  `{!! form()->radio('status', 'active', true)->label('Active') !!}`
  `{!! form()->checkboxGroup('fruit', ['apple' => 'Apple', 'banana' => 'Banana'], 'banana') !!}`
  `{!! form()->radioGroup('fruit', ['apple' => 'Apple', 'banana' => 'Banana'], 'apple') !!}`
* **Select / Dropdown:**
  `{!! form()->select('country', ['id' => 'Indonesia', 'ms' => 'Malaysia'])->label('Country')->placeholder('--Select--') !!}`
  `{!! form()->select('tags', $options)->multiple() !!}`
  `{!! form()->selectRange('children', 1, 5) !!}`
  `{!! form()->selectMonth('month') !!}`
  `{!! form()->dropdownDB('province', 'SELECT id, name from provinces', 'id', 'name')->dependency('country') !!}`
* **Date & Time:**
  `{!! form()->date('birthday')->label('Birthday') !!}`
  `{!! form()->time('start_time')->label('Start Time') !!}`
  `{!! form()->datepicker('date', $value, $format) !!}`
  `{!! form()->timepicker('time', $value) !!}`
  `{!! form()->selectDate('myDate', 2000, 2020) !!}`
* **Files / Media / Maps:**
  `{!! form()->file('avatar') !!}`
  `{!! form()->uploader('files')->limit(1)->extensions(['jpg', 'png'])->fileMaxSize(5) !!}`
  `{!! form()->coordinate('location') !!}`
* **Grouping Actions:**
  `{!! form()->action(form()->submit('Save'), form()->button('Cancel')) !!}`

### 4. Data Tables (Livewire & Suitable)
* **Creation:** Tables must be built extending the `TableView` class (located in `app\Http\Livewire\Table`).
* **Rendering:** Render tables via Livewire tags (e.g., `<livewire:table.user-table />`).
* **Data Source:** Define the query inside the `data()` method (Array, Collection, Eloquent, Query Builder). Apply `->autoSort($this->sortPayload())` for sorting.
* **Columns:** Strictly use Laravolt's `Suitable` column classes inside the `columns(): array` method.

#### Suitable Column Examples (`Laravolt\Suitable\Columns\*`):
* **Avatar:** `Avatar::make('email', 'Avatar')`
* **Boolean:** `Boolean::make('is_active')`
* **Button:** `Button::make('permalink', 'Info')->label('Profile')->icon('external link')`
  * *Dynamic closure:* `Button::make(fn ($user) => route('users.show', $user['id']))`
* **Checkall:** `Checkall::make('id')`
* **Date & Time:** `Date::make('created_at')`, `DateTime::make('created_at')`
* **HTML & Raw:** * `Html::make('bio')` *(For executing WYSIWYG tags safely)*
  * `Raw::make(fn ($user) => $user->roles->implode('name', ', '), 'Roles')` *(Custom logic)*
* **ID & Row Number:** `Id::make()`, `RowNumber::make()`
* **Image:** `Image::make('profile_picture')->height(50)->width(50)->alt('Pic')`
* **Label:** `Label::make('status')->addClass('green')->map(['active' => 'green', 'banned' => 'red'])`
* **Number & Text:** `Number::make('salary')` *(Formats with thousands separator)*, `Text::make('name', 'Full Name')->sortable()`
* **Restful Buttons (CRUD):** `RestfulButton::make('users')->only('show', 'edit')->except('destroy')`
* **URL & View:** `Url::make('website')`, `View::make('profile')`

### 5. Laravolt ACL (Access Control List)
* **Fixed Permissions, Dynamic Roles:** Never check for roles directly in the code. Always authorize against specific permissions.
* **Permission Registry:** All permissions must be defined as constants inside the `app\Enums\Permission.php` Enum class (e.g., `const POST_DELETE = 'post.delete';`).
* **Usage:** Use the Enum constant for authorization checks (e.g., `$user->can(\App\Enums\Permission::POST_DELETE)`).

### 6. Menu Configuration
* **Configuration:** Define sidebar menus strictly within `config/laravolt/menu/*.php` using Laravolt's array schema.
* **Authorization:** Secure menu items by attaching the Enum permissions directly to the menu array (`'permissions' => [\App\Enums\Permission::DASHBOARD_VIEW]`).

### 7. Auto CRUD & Thunderclap Generators
* **Auto CRUD (Codeless):** For simple CRUDs, define the schema inside `config/laravolt/auto-crud-resources/*.php` utilizing `\Laravolt\Fields\Field::*` constants for input types.
* **Thunderclap (Scaffolding):** For complex CRUDs requiring customization, use the `php artisan laravolt:clap` command to generate modules based on database tables and custom `.stub` templates.

### 8. Statistics & Charts
* **Statistics:** Create single-value metric widgets by extending the `Laravolt\Ui\Statistic` class.
* **Charts:** Create ApexCharts visualizations (without JS) by extending `Laravolt\Charts\*` classes (e.g., `Bar`, `Line`, `Donut`) via Livewire components.