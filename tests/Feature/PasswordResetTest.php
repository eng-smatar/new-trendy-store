<?php

namespace Tests\Feature;

use App\Filament\Auth\RequestPasswordReset;
use App\Models\User;
use App\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\ResetPassword as ResetPasswordPage;
use Filament\Facades\Filament;
use Filament\Notifications\Notification as Toast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function ask(string $email): void
    {
        Livewire::test(RequestPasswordReset::class)->fillForm(['email' => $email])->call('request')
            ->assertHasNoFormErrors()
            ->assertNotified(Toast::make()->title(__('passwords.sent'))
                ->body(__('filament-panels::auth/pages/password-reset/request-password-reset.notifications.sent.body'))->success());
    }

    public function test_link_goes_only_to_real_admins_but_the_answer_is_the_same(): void
    {
        Notification::fake();
        $this->get('/admin/login')->assertSee('/admin/password-reset/request');
        $admin = User::factory()->create();

        $this->ask('nobody@example.test');
        Notification::assertNothingSent();

        $this->ask($admin->email);
        Notification::assertSentTo($admin, ResetPassword::class, fn (ResetPassword $n) => str_contains($n->toMail($admin)->actionUrl, '/admin/password-reset/reset'));
    }

    public function test_emailed_link_opens_whatever_scheme_or_host_php_sees(): void
    {
        $admin = User::factory()->create();
        $url = (new ResetPassword('token'))->toMail($admin)->actionUrl;
        $path = substr($url, strlen(url('/')));

        // As a proxy might deliver it: other scheme, other host.
        foreach ([$url, 'https://localhost'.$path, 'http://internal:8080'.$path] as $address) {
            $this->get($address)->assertOk();
        }

        $this->get(str_replace('token=token', 'token=other', $url))->assertForbidden();
        $this->get(str_replace(urlencode($admin->email), urlencode('x@y.test'), $url))->assertForbidden();
        $this->get(url('/admin/password-reset/reset?email=a%40b.test&token=t'))->assertForbidden(); // unsigned
    }

    public function test_any_signed_route_accepts_a_relative_signature(): void
    {
        Route::middleware(['web', 'signed'])->get('/signed-check/{id}', fn () => 'ok')->name('signed.check');
        Route::getRoutes()->refreshNameLookups();
        $relative = URL::signedRoute('signed.check', ['id' => 5], absolute: false);

        $this->get('https://localhost'.$relative)->assertOk();
        $this->get(str_replace('/5?', '/6?', $relative))->assertForbidden();
        $this->get(URL::signedRoute('signed.check', ['id' => 5]))->assertForbidden(); // absolute signing is not accepted
    }

    public function test_the_link_sets_a_new_password_once(): void
    {
        $admin = User::factory()->create();
        $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($admin);
        $reset = fn (string $password) => Livewire::test(ResetPasswordPage::class, ['email' => $admin->email, 'token' => $token])
            ->fillForm(['password' => $password, 'passwordConfirmation' => $password])->call('resetPassword');

        $reset('a-new-long-password')->assertHasNoFormErrors();
        $this->assertTrue(Hash::check('a-new-long-password', $admin->fresh()->password));

        $reset('another-password-2');
        $this->assertTrue(Hash::check('a-new-long-password', $admin->fresh()->password));
    }

    public function test_reset_email_is_arabic_and_right_to_left_for_an_arabic_admin(): void
    {
        app()->setLocale('ar');
        $mail = (new ResetPassword('token'))->toMail(User::factory()->make());
        $html = (string) $mail->render();

        $this->assertSame('إعادة تعيين كلمة المرور', $mail->subject);
        $this->assertStringContainsString('dir="rtl"', $html);
        foreach (['Hello!', 'Regards', 'You are receiving', 'having trouble', 'All rights reserved'] as $english) {
            $this->assertStringNotContainsString($english, $html);
        }
    }
}
