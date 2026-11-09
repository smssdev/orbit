<?php

use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

test('pages are in English and left to right by default', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr"', false)
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('locale.current', 'en')
            ->where('locale.dir', 'ltr')
            ->where('translations', []));
});

test('the browser language picks Arabic for visitors', function (): void {
    $this->withHeader('Accept-Language', 'ar-SA,ar;q=0.9,en;q=0.5')
        ->get(route('home'))
        ->assertSee('<html lang="ar" dir="rtl"', false)
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('locale.current', 'ar')
            ->where('translations.Log in', 'تسجيل الدخول'));
});

test('visitors switch language with a cookie', function (): void {
    $response = $this->from(route('login'))
        ->put(route('locale.update'), ['locale' => 'ar']);

    $response->assertRedirect(route('login'))->assertCookie('locale', 'ar', encrypted: false);

    $this->withUnencryptedCookie('locale', 'ar')
        ->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('locale.current', 'ar'));
});

test('signed-in users keep their language on the account', function (): void {
    $user = User::factory()->withWorkspace()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->put(route('locale.update'), ['locale' => 'ar'])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->locale)->toBe('ar')
        ->and($user->fresh()->preferredLocale())->toBe('ar');

    // The account wins over the cookie and the browser.
    $this->actingAs($user->fresh())
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('dashboard'))
        ->assertSee('dir="rtl"', false);
});

test('only supported languages can be chosen', function (string $locale): void {
    $this->put(route('locale.update'), ['locale' => $locale])
        ->assertSessionHasErrors('locale');
})->with(['fr', '', 'arabic']);

test('validation messages follow the language', function (): void {
    $this->withUnencryptedCookie('locale', 'ar')
        ->post(route('login.store'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('مطلوب');
});

test('invitation emails go out in the inviter\'s language', function (): void {
    Notification::fake();
    $workspace = Workspace::factory()->create(['name' => 'Acme']);
    $owner = $workspace->owner;
    $owner->forceFill(['locale' => 'ar'])->save();
    $owner->switchWorkspace($workspace);

    $this->actingAs($owner)->post(route('workspace.invitations.store'), [
        'email' => 'sara@example.com',
        'role' => 'member',
    ]);

    Notification::assertSentTo(
        new AnonymousNotifiable,
        WorkspaceInvitationNotification::class,
        fn (WorkspaceInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->locale === 'ar'
            && $notifiable->routes['mail'] === 'sara@example.com',
    );
});

test('new accounts keep the language they signed up in', function (): void {
    $this->withUnencryptedCookie('locale', 'ar')->post(route('register.store'), [
        'name' => 'Sara Ahmad',
        'email' => 'sara@example.com',
        'password' => 'a-Strong-pass-123!',
        'password_confirmation' => 'a-Strong-pass-123!',
    ]);

    expect(User::where('email', 'sara@example.com')->first()?->locale)->toBe('ar');
});
