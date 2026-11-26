<?php

namespace Tests\Feature;

use App\Filament\Auth\RequestPasswordReset;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\ResetPassword as ResetPasswordPage;
use Filament\Facades\Filament;
use Filament\Notifications\Notification as Toast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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
        Notification::assertSentTo($admin, ResetPassword::class, fn (ResetPassword $n) => str_contains($n->url, '/admin/password-reset/reset'));
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
        $notification = new ResetPassword('token');
        $notification->url = 'https://shop.test/admin/password-reset/reset?x=1';
        $mail = $notification->toMail(User::factory()->make());
        $html = (string) $mail->render();

        $this->assertSame('إعادة تعيين كلمة المرور', $mail->subject);
        $this->assertStringContainsString('dir="rtl"', $html);
        foreach (['Hello!', 'Regards', 'You are receiving', 'having trouble', 'All rights reserved'] as $english) {
            $this->assertStringNotContainsString($english, $html);
        }
    }
}
