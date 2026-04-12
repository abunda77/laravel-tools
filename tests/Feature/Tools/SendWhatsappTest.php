<?php

namespace Tests\Feature\Tools;

use App\Livewire\Tools\SendWhatsapp;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SendWhatsappTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_whatsapp_page_requires_authentication(): void
    {
        $response = $this->get(route('tools.send-whatsapp'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_send_whatsapp_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tools.send-whatsapp'));

        $response->assertOk();
        $response->assertSeeLivewire(SendWhatsapp::class);
    }

    public function test_send_whatsapp_can_send_message_using_basic_auth_credentials_from_config(): void
    {
        $this->configureRequestSettings();
        $this->configureWhatsappCredentials();

        Http::fake([
            'http://46.102.156.214:3003/send/message' => Http::response([
                'code' => 'SUCCESS',
                'message' => 'Message sent to 6281310307754@s.whatsapp.net (server timestamp: 2026-04-12 02:05:23 +0000 UTC)',
                'results' => [
                    'message_id' => '3EB0ECAD575DA35654E202',
                    'status' => 'Message sent to 6281310307754@s.whatsapp.net (server timestamp: 2026-04-12 02:05:23 +0000 UTC)',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SendWhatsapp::class)
            ->set('phone', '6281310307754@s.whatsapp.net')
            ->set('message', 'selamat malam bro')
            ->set('replyMessageId', '')
            ->set('isForwarded', false)
            ->set('duration', 86400)
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('result.code', 'SUCCESS')
            ->assertSet('result.messageId', '3EB0ECAD575DA35654E202')
            ->assertSet('result.request.phone', '6281310307754@s.whatsapp.net')
            ->assertSet('result.request.duration', 86400)
            ->assertSee('Response JSON');

        Http::assertSent(function ($request) {
            $authorizationHeader = $request->header('Authorization')[0] ?? null;

            return $request->url() === 'http://46.102.156.214:3003/send/message'
                && $authorizationHeader === 'Basic '.base64_encode('Username:Password')
                && $request['phone'] === '6281310307754@s.whatsapp.net'
                && $request['message'] === 'selamat malam bro'
                && $request['reply_message_id'] === ''
                && $request['is_forwarded'] === false
                && $request['duration'] === 86400;
        });
    }

    public function test_send_whatsapp_requires_configured_credentials(): void
    {
        $this->configureRequestSettings();
        config([
            'tools.whatsapp.base_url' => 'http://46.102.156.214:3003',
            'tools.whatsapp.username' => '',
            'tools.whatsapp.password' => '',
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SendWhatsapp::class)
            ->set('phone', '6281310307754@s.whatsapp.net')
            ->set('message', 'selamat malam bro')
            ->call('send')
            ->assertSet('errorMessage', 'WHATSAPP_API_USERNAME belum diatur di environment.');
    }

    public function test_send_whatsapp_returns_error_when_phone_format_is_invalid(): void
    {
        $this->configureRequestSettings();
        $this->configureWhatsappCredentials();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SendWhatsapp::class)
            ->set('phone', '6281310307754')
            ->set('message', 'selamat malam bro')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', 'Nomor tujuan harus memakai format WhatsApp JID, contoh 6281310307754@s.whatsapp.net.');
    }

    private function configureRequestSettings(): void
    {
        app(SystemSettings::class)->putMany([
            'request_timeout_seconds' => 30,
            'request_retry_times' => 1,
            'request_retry_sleep_ms' => 100,
            'queue_connection' => 'database',
        ]);
    }

    private function configureWhatsappCredentials(): void
    {
        config([
            'tools.whatsapp.base_url' => 'http://46.102.156.214:3003',
            'tools.whatsapp.username' => 'Username',
            'tools.whatsapp.password' => 'Password',
        ]);
    }
}
