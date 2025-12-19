# API Localization Documentation

## Overview

The API supports multiple languages through Laravel's built-in localization system. All API responses, error messages, and validation messages can be returned in different languages based on the client's preference.

## Supported Locales

Currently supported locales:
- `en` - English
- `id` - Indonesian (default)

## How to Specify Locale

### Method 1: Query Parameter (Recommended)

Add the `lang` parameter to any API request:

```
GET /api/v1/users?lang=en
POST /api/v1/auth/login?lang=id
```

### Method 2: Accept-Language Header

Set the `Accept-Language` HTTP header:

```
Accept-Language: en-US,en;q=0.9,id;q=0.8
```

### Priority Order

1. `lang` query parameter (highest priority)
2. `Accept-Language` header
3. Default locale from configuration (Indonesian)

## For Developers

### Using Translation Keys in Controllers

Instead of hardcoding messages, use translation keys:

**❌ Bad:**
```php
return response()->json([
    'message' => 'User created successfully'
]);
```

**✅ Good:**
```php
use App\Support\ApiResponse;

class UserController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        // ... create user logic
        
        return $this->created($user, 'messages.resource_created', [
            'resource' => 'User'
        ]);
    }
}
```

### Available ApiResponse Methods

All methods support translation keys and parameter substitution:

```php
// Success response
$this->success($data, 'messages.success', $params);

// Created response (201)
$this->created($data, 'messages.created', $params);

// Error response
$this->error('messages.error', $params, $statusCode);

// Not found (404)
$this->notFound('messages.not_found', $params);

// Unauthorized (401)
$this->unauthorized('messages.unauthorized', $params);

// Forbidden (403)
$this->forbidden('messages.forbidden', $params);

// Validation error (422)
$this->validationError($errors, 'messages.validation_failed', $params);
```

### Parameter Substitution

Use placeholders in translation strings:

**Translation file (`lang/en/messages.php`):**
```php
'resource_created' => ':resource created successfully.',
'welcome_user' => 'Welcome, :name!',
```

**Controller:**
```php
return $this->success($user, 'messages.welcome_user', [
    'name' => $user->name
]);
```

### Pluralization

Use `trans_choice()` for pluralization:

**Translation file:**
```php
'items_count' => '{0} No items|{1} :count item|[2,*] :count items',
```

**Usage:**
```php
$message = trans_choice('messages.items_count', $count);
```

### Using TranslationService

For advanced translation needs:

```php
use App\Services\TranslationService;

class SomeController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function index()
    {
        // Translate with specific locale
        $message = $this->translationService->trans('messages.success', [], 'en');

        // Check if translation exists
        if ($this->translationService->hasTranslation('messages.custom_key')) {
            // ...
        }

        // Get supported locales
        $locales = $this->translationService->getSupportedLocales();

        // Get current locale
        $currentLocale = $this->translationService->getCurrentLocale();
    }
}
```

## Translation File Structure

Translation files are located in `lang/{locale}/` directory:

```
lang/
├── en/
│   ├── auth.php
│   ├── messages.php
│   ├── validation.php
│   └── ...
└── id/
    ├── auth.php
    ├── messages.php
    ├── validation.php
    └── ...
```

### messages.php Structure

```php
<?php

return [
    // General messages
    'success' => 'Success.',
    'error' => 'An error occurred.',
    'created' => 'Created successfully.',
    
    // Messages with parameters
    'resource_created' => ':resource created successfully.',
    'welcome_user' => 'Welcome, :name!',
    
    // Pluralization
    'items_count' => '{0} No items|{1} :count item|[2,*] :count items',
    
    // Module-specific messages
    'users' => [
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
    ],
];
```

## Adding a New Locale

### Step 1: Create Translation Directory

```bash
mkdir lang/fr
```

### Step 2: Copy Translation Files

```bash
cp lang/en/*.php lang/fr/
```

### Step 3: Translate Strings

Edit each file in `lang/fr/` and translate all strings to French.

### Step 4: Update Configuration

Add the new locale to `config/app.php`:

```php
'supported_locales' => ['en', 'id', 'fr'],
```

### Step 5: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

## Validation Messages

Validation messages are automatically localized using `lang/{locale}/validation.php`.

### Custom Validation Messages

```php
$validator = Validator::make($data, $rules, [
    'email.required' => __('validation.required', ['attribute' => 'email']),
    'email.email' => __('validation.email', ['attribute' => 'email']),
]);
```

### Custom Attribute Names

```php
$validator = Validator::make($data, $rules, [], [
    'email' => __('attributes.email'),
    'password' => __('attributes.password'),
]);
```

## Exception Messages

All exceptions are automatically localized:

- `ModelNotFoundException` → `messages.not_found`
- `AuthenticationException` → `messages.unauthenticated`
- `AuthorizationException` → `messages.forbidden`
- `ValidationException` → `messages.validation_failed`

Custom exceptions can also use translation keys:

```php
throw new ResourceNotFoundException(__('messages.user_not_found'));
```

## Best Practices

### 1. Always Use Translation Keys

Never hardcode messages in controllers or services.

### 2. Organize Translation Keys

Group related translations:

```php
'users' => [
    'created' => '...',
    'updated' => '...',
    'deleted' => '...',
],
```

### 3. Use Descriptive Keys

```php
// ❌ Bad
'msg1' => 'Success',

// ✅ Good
'user_created_successfully' => 'User created successfully',
```

### 4. Keep Translations Consistent

Ensure all locales have the same keys:

```bash
# Check for missing keys
php artisan lang:check
```

### 5. Use Parameters for Dynamic Content

```php
// ❌ Bad
'message' => 'User John created',

// ✅ Good
'message' => 'User :name created',
```

## Testing Localization

### Test with Different Locales

```php
public function test_endpoint_returns_localized_message()
{
    App::setLocale('en');
    $response = $this->get('/api/v1/users');
    $response->assertJson(['message' => 'Success.']);

    App::setLocale('id');
    $response = $this->get('/api/v1/users');
    $response->assertJson(['message' => 'Berhasil.']);
}
```

### Test with Query Parameter

```php
$response = $this->get('/api/v1/users?lang=en');
$response->assertJson(['message' => 'Success.']);
```

## Troubleshooting

### Translation Not Working

1. Check if translation key exists in the file
2. Clear cache: `php artisan config:clear && php artisan cache:clear`
3. Check locale is supported in `config/app.php`
4. Verify translation file syntax (valid PHP array)

### Missing Translation Key

If a translation key is missing, the key itself will be returned. Check logs for warnings:

```
[WARNING] Missing translation key: messages.custom_key
```

### Locale Not Changing

1. Verify middleware is registered in `bootstrap/app.php`
2. Check `lang` parameter or `Accept-Language` header is set correctly
3. Ensure locale is in supported locales list

## Performance Considerations

### Translation Caching

In production, translations are cached automatically. After updating translation files:

```bash
php artisan config:cache
php artisan cache:clear
```

### Locale Detection

Locale detection happens once per request in middleware. No performance impact on subsequent operations.

## API Response Format

All API responses follow this structure:

```json
{
    "success": true,
    "message": "Success.",
    "data": { ... },
    "meta": { ... },
    "errors": null
}
```

The `message` field is always localized based on the request locale.

## Examples

### Example 1: User Registration

**Request:**
```http
POST /api/v1/auth/register?lang=en
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secret123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User created successfully.",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### Example 2: Validation Error

**Request:**
```http
POST /api/v1/auth/login?lang=id
Content-Type: application/json

{
    "email": "invalid-email",
    "password": ""
}
```

**Response:**
```json
{
    "success": false,
    "message": "Data yang Anda kirim tidak valid. Periksa kembali isian Anda.",
    "errors": {
        "email": ["Email harus berupa alamat email yang valid."],
        "password": ["Password wajib diisi."]
    }
}
```

### Example 3: Not Found Error

**Request:**
```http
GET /api/v1/users/999?lang=en
```

**Response:**
```json
{
    "success": false,
    "message": "The resource you are looking for was not found.",
    "data": null
}
```

## Support

For questions or issues related to localization, please contact the development team or refer to the Laravel localization documentation: https://laravel.com/docs/localization
