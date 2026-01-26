# Auth Module Factories - Comprehensive Guide

## 📚 Overview

The Auth module uses Laravel factories with enhanced states for generating realistic test data. All factories are located in:
- `Modules/Auth/database/factories/` - Auth specific factories
- `database/factories/UserFactory.php` - User factory (root level)

## 🏭 Factory Classes

### UserFactory
Location: `database/factories/UserFactory.php`

The main factory for creating users with comprehensive state management.

#### Basic Usage
```php
// Create single user
User::factory()->create();

// Create multiple users
User::factory()->count(10)->create();

// Create and get attributes without saving
User::factory()->make();
```

#### States Available

##### Status States
```php
// Create active user (verified email)
User::factory()->active()->create();

// Create pending user (no email verification)
User::factory()->pending()->create();

// Create inactive user
User::factory()->inactive()->create();

// Create banned user
User::factory()->banned()->create();

// Create unverified user (old state)
User::factory()->unverified()->create();
```

##### Profile States
```php
// User with complete profile data
User::factory()->withCompleteProfile()->create();

// User with minimal profile data (only required fields)
User::factory()->withMinimalProfile()->create();

// User with password not set (created by admin)
User::factory()->passwordNotSet()->create();
```

##### Other States
```php
// Create deleted/trashed user
User::factory()->deleted()->create();

// Chain multiple states
User::factory()
    ->active()
    ->withCompleteProfile()
    ->count(5)
    ->create();
```

#### Custom Attributes
```php
// Override specific attributes
User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active',
]);

// With state and custom attributes
User::factory()
    ->active()
    ->create([
        'name' => 'Jane Smith',
    ]);
```

#### Hook Methods
```php
// After creating user
User::factory()
    ->afterCreating(function (User $user) {
        // Assign role
        $user->assignRole('Student');
        // Create related data
        $user->privacySettings()->create([...]);
    })
    ->create();

// Before creating
User::factory()
    ->beforeCreating(function (array $attributes) {
        $attributes['email'] = 'custom-' . $attributes['email'];
        return $attributes;
    })
    ->create();
```

### JwtRefreshTokenFactory
Location: `Modules/Auth/database/factories/JwtRefreshTokenFactory.php`

Creates JWT refresh tokens for testing token management.

#### States
```php
// Valid token
JwtRefreshToken::factory()->valid()->create();

// Revoked token
JwtRefreshToken::factory()->revoked()->create();

// Expired token
JwtRefreshToken::factory()->expired()->create();

// Replaced token (in rotation chain)
JwtRefreshToken::factory()->replaced()->create();
```

#### Example Usage
```php
// Create tokens for a user
$user = User::factory()->active()->create();

// Create multiple valid tokens (multi-device)
JwtRefreshToken::factory()
    ->count(3)
    ->for($user)
    ->valid()
    ->create();

// Create token rotation chain
$token1 = JwtRefreshToken::factory()->for($user)->create();
$token2 = JwtRefreshToken::factory()->for($user)->create([
    'replaced_by' => $token1->id,
]);
```

### OtpCodeFactory
Location: `Modules/Auth/database/factories/OtpCodeFactory.php`

Generates OTP codes for verification flows.

#### States
```php
// For email verification
OtpCode::factory()->forEmailVerification()->create();

// For password reset
OtpCode::factory()->forPasswordReset()->create();

// For login
OtpCode::factory()->forLogin()->create();

// Expired code
OtpCode::factory()->expired()->create();

// Consumed code
OtpCode::factory()->consumed()->create();

// Valid code (not consumed, not expired)
OtpCode::factory()->valid()->create();
```

#### Example Usage
```php
// Email verification OTP
$otp = OtpCode::factory()
    ->forEmailVerification()
    ->for(User::factory()->pending())
    ->create();

// Password reset OTP
OtpCode::factory()
    ->forPasswordReset()
    ->count(5)
    ->create();

// Mix of valid and expired
OtpCode::factory()
    ->count(3)
    ->valid()
    ->create();

OtpCode::factory()
    ->count(2)
    ->expired()
    ->create();
```

### LoginActivityFactory
Location: `Modules/Auth/database/factories/LoginActivityFactory.php`

Tracks user login attempts and sessions.

#### States
```php
// Successful login
LoginActivity::factory()->successful()->create();

// Failed login
LoginActivity::factory()->failed()->create();

// Active session (logged in but not out)
LoginActivity::factory()->active()->create();

// Completed session (logged out)
LoginActivity::factory()->completed()->create();
```

#### Example Usage
```php
$user = User::factory()->active()->create();

// Create activity history
LoginActivity::factory()
    ->count(10)
    ->for($user)
    ->successful()
    ->create();

// Mix of active and completed sessions
LoginActivity::factory()
    ->count(5)
    ->for($user)
    ->active()
    ->create();

LoginActivity::factory()
    ->count(5)
    ->for($user)
    ->completed()
    ->create();
```

### UserActivityFactory
Location: `Modules/Auth/database/factories/UserActivityFactory.php`

Generates user activity records.

#### States
```php
// Enrollment activity
UserActivity::factory()->enrollment()->create();

// Course completion
UserActivity::factory()->completion()->create();

// Quiz/assignment submission
UserActivity::factory()->submission()->create();

// Achievement unlocked
UserActivity::factory()->achievement()->create();

// Badge earned
UserActivity::factory()->badgeEarned()->create();

// Certificate earned
UserActivity::factory()->certificateEarned()->create();
```

#### Example Usage
```php
$user = User::factory()->create();

// Create various activities
collect([
    'enrollment',
    'completion',
    'submission',
    'badgeEarned',
])->each(fn($type) =>
    UserActivity::factory()
        ->$type()
        ->for($user)
        ->count(3)
        ->create()
);
```

### ProfilePrivacySettingFactory
Location: `Modules/Auth/database/factories/ProfilePrivacySettingFactory.php`

Creates privacy settings for user profiles.

#### States
```php
// Public profile
ProfilePrivacySetting::factory()->public()->create();

// Private profile
ProfilePrivacySetting::factory()->private()->create();

// Friends only profile
ProfilePrivacySetting::factory()->friendsOnly()->create();
```

#### Example Usage
```php
// Public profile with all fields visible
ProfilePrivacySetting::factory()
    ->public()
    ->for(User::factory())
    ->create();

// Mix of privacy settings
User::factory()
    ->count(10)
    ->create()
    ->each(function ($user) {
        ProfilePrivacySetting::factory()
            ->for($user)
            ->state([
                'profile_visibility' => fake()->randomElement(['public', 'private', 'friends_only']),
            ])
            ->create();
    });
```

### Other Factories

#### ProfileAuditLogFactory
```php
ProfileAuditLog::factory()
    ->count(5)
    ->for(User::factory())
    ->create();
```

#### PasswordResetTokenFactory
```php
PasswordResetToken::factory()
    ->count(3)
    ->create();
```

#### SocialAccountFactory
```php
SocialAccount::factory()
    ->for(User::factory())
    ->create();
```

## 🔗 Factory Relationships

### Using `for()` Method
```php
// Explicitly associate with user
JwtRefreshToken::factory()->for($user)->create();

// Or let factory create related user
JwtRefreshToken::factory()->for(User::factory())->create();
```

### Using `has()` Method
```php
// Create user with related tokens
User::factory()
    ->has(JwtRefreshToken::factory()->count(3))
    ->create();

// Create user with privacy settings and activities
User::factory()
    ->has(ProfilePrivacySetting::factory(), 'privacySettings')
    ->has(UserActivity::factory()->count(5))
    ->create();
```

## 🎯 Common Testing Patterns

### Test Complete User Registration Flow
```php
public function test_registration_flow()
{
    // Create pending user
    $user = User::factory()->pending()->create();

    // Create verification OTP
    $otp = OtpCode::factory()
        ->forEmailVerification()
        ->for($user)
        ->create();

    // Verify email
    $this->post('/api/auth/verify-email', [
        'token' => $otp->meta['token_hash'],
        'uuid' => $otp->uuid,
    ])->assertSuccess();

    // Verify user is now active
    $this->assertTrue($user->fresh()->email_verified_at !== null);
}
```

### Test Multi-Device Login
```php
public function test_multi_device_login()
{
    $user = User::factory()->active()->create();

    // Create tokens for 3 devices
    $tokens = JwtRefreshToken::factory()
        ->count(3)
        ->for($user)
        ->valid()
        ->create();

    // Each token should be usable
    foreach ($tokens as $token) {
        $this->post('/api/auth/refresh', [
            'refresh_token' => $token->plain_token,
        ])->assertSuccess();
    }
}
```

### Test Privacy Settings
```php
public function test_privacy_filtering()
{
    // Create users with different privacy settings
    $publicUser = User::factory()
        ->has(ProfilePrivacySetting::factory()->public())
        ->create();

    $privateUser = User::factory()
        ->has(ProfilePrivacySetting::factory()->private())
        ->create();

    // Test access
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->getJson("/api/users/{$publicUser->id}/profile")
        ->assertSuccess();

    $this->actingAs($viewer)
        ->getJson("/api/users/{$privateUser->id}/profile")
        ->assertForbidden();
}
```

### Test Activity Tracking
```php
public function test_user_activity_tracking()
{
    $user = User::factory()
        ->has(UserActivity::factory()
            ->count(5)
            ->enrollment()
        )
        ->create();

    // Get user activities
    $this->actingAs($user)
        ->getJson('/api/profile/activities')
        ->assertSuccess()
        ->assertJsonCount(5, 'data');
}
```

## 📝 Best Practices

### 1. Use Appropriate States
```php
// Good - clear intent
User::factory()->active()->withCompleteProfile()->create();

// Avoid - unclear defaults
User::factory()->create();
```

### 2. Use Sequences for Unique Values
```php
// Generate unique emails in sequence
User::factory()
    ->sequence(
        ['email' => 'user1@test.com'],
        ['email' => 'user2@test.com'],
        ['email' => 'user3@test.com'],
    )
    ->count(3)
    ->create();
```

### 3. Create Realistic Data
```php
// Good - realistic profile
User::factory()
    ->withCompleteProfile()
    ->active()
    ->create();

// Avoid - incomplete data
User::factory()->create();
```

### 4. Use Hooks for Complex Data
```php
// Create with related data using afterCreating
User::factory()
    ->afterCreating(function (User $user) {
        $user->assignRole('Instructor');
        JwtRefreshToken::factory()
            ->count(2)
            ->for($user)
            ->create();
    })
    ->create();
```

### 5. Keep Tests Isolated
```php
// Each test should create its own data
public function test_something()
{
    $user = User::factory()->create();
    // Test...
}
```

## 🚀 Performance Tips

### Use `fake()` Helper
```php
// Lazy - creates immediately
User::factory()->count(1000)->create();

// Better - chunk creation
User::factory()
    ->lazy()
    ->each(fn($user) => $user->save());
```

### Disable Model Events for Large Batches
```php
use Illuminate\Database\Eloquent\Model;

Model::unguarded(function () {
    User::factory()->count(1000)->create();
});
```

### Use Database Transactions in Tests
```php
public function test_something()
{
    $this->withoutEvents(); // Disable events
    
    User::factory()->count(100)->create();
    
    // Test...
}
```

## 📚 References

- [Laravel Factory Documentation](https://laravel.com/docs/factories)
- [Faker PHP Documentation](https://fakerphp.github.io/)
- [Spatie Laravel Faker Factory Helpers](https://github.com/spatie/laravel-faker-factory-helpers)
