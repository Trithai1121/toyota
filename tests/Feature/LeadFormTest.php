<?php

namespace Tests\Feature;

use App\Jobs\SendLeadNotifications;
use App\Jobs\SendMetaLeadConversion;
use App\Mail\LeadReceived;
use App\Models\CampaignLandingPage;
use App\Models\Lead;
use Database\Seeders\ContentPagesSeeder;
use Database\Seeders\UiStringsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UiStringsSeeder::class);
    }

    public function test_valid_lead_is_saved_with_consent_and_notification_is_queued(): void
    {
        Queue::fake();

        $response = $this->post(route('leads.store'), [
            'request_type' => 'quote',
            'full_name' => 'Nguyễn Văn An',
            'phone' => '+84 90 123 4567',
            'region' => 'kon-tum',
            'consent_given' => '1',
            'utm_source' => 'google',
        ]);

        $response->assertRedirect(route('leads.thank-you'));
        $this->assertDatabaseHas('leads', [
            'full_name' => 'Nguyễn Văn An',
            'phone' => '0901234567',
            'region' => 'Kon Tum',
            'status' => 'new',
            'consent_given' => true,
            'marketing_consent' => false,
            'consent_version' => '1.0',
            'utm_source' => 'google',
        ]);

        $lead = Lead::query()->firstOrFail();
        $this->assertNotNull($lead->consented_at);
        $this->assertNotNull($lead->event_id);
        Queue::assertPushed(SendLeadNotifications::class, fn (SendLeadNotifications $job): bool => $job->leadId === $lead->id);
    }

    public function test_meta_conversion_is_queued_only_after_marketing_consent(): void
    {
        Queue::fake();
        config([
            'tracking.meta.pixel_id' => '123456789',
            'tracking.meta.capi_access_token' => 'test-token',
            'tracking.meta.graph_api_version' => 'v99.0',
        ]);
        $this->postJson(route('cookie-consent.store'), [
            'version' => 1,
            'analytics' => false,
            'marketing' => true,
        ])->assertOk()->assertJson(['saved' => true]);

        $this->post(route('leads.store'), [
            'request_type' => 'quote',
            'full_name' => 'Nguyễn Văn An',
            'phone' => '0901234567',
            'region' => 'kon-tum',
            'consent_given' => '1',
            'marketing_consent' => '1',
        ])->assertRedirect(route('leads.thank-you'));

        $lead = Lead::query()->firstOrFail();
        $this->assertTrue($lead->marketing_consent);
        $this->assertNotNull($lead->marketing_consent_at);
        Queue::assertPushed(SendMetaLeadConversion::class, fn (SendMetaLeadConversion $job): bool => $job->leadId === $lead->id);
    }

    public function test_forged_form_field_cannot_grant_marketing_consent(): void
    {
        Queue::fake();
        config([
            'tracking.meta.pixel_id' => '123456789',
            'tracking.meta.capi_access_token' => 'test-token',
            'tracking.meta.graph_api_version' => 'v99.0',
        ]);

        $this->post(route('leads.store'), [
            'request_type' => 'quote',
            'full_name' => 'Nguyễn Văn An',
            'phone' => '0901234567',
            'region' => 'kon-tum',
            'consent_given' => '1',
            'marketing_consent' => '1',
        ])->assertRedirect(route('leads.thank-you'));

        $this->assertDatabaseHas('leads', ['marketing_consent' => false]);
        Queue::assertNotPushed(SendMetaLeadConversion::class);
    }

    public function test_invalid_vietnamese_phone_is_rejected(): void
    {
        $this->from(route('leads.create'))
            ->post(route('leads.store'), [
                'request_type' => 'quote',
                'full_name' => 'Nguyễn Văn An',
                'phone' => '0123456789',
                'region' => 'kon-tum',
                'consent_given' => '1',
            ])
            ->assertRedirect(route('leads.create'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_lead_submission_requires_contact_consent(): void
    {
        $this->from(route('leads.create'))
            ->post(route('leads.store'), [
                'request_type' => 'quote',
                'full_name' => 'Nguyễn Văn An',
                'phone' => '0901234567',
                'region' => 'kon-tum',
            ])
            ->assertRedirect(route('leads.create'))
            ->assertSessionHasErrors('consent_given');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_public_lead_form_renders_without_publishing_unconfirmed_vehicle_data(): void
    {
        $this->get(route('leads.create'))
            ->assertOk()
            ->assertSee('Đăng ký nhận tư vấn')
            ->assertSee('Chính sách bảo mật');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_home_page_lead_block_renders_an_embedded_form_with_consent_and_honeypot(): void
    {
        $this->seed(ContentPagesSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Nhận báo giá')
            ->assertSee('Nhận báo giá qua Zalo')
            ->assertDontSee('Để lại thông tin, đại lý sẽ liên hệ với bạn.')
            ->assertSee('name="request_type" value="quote"', false)
            ->assertSee('name="full_name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="region"', false)
            ->assertSee('name="consent_given"', false)
            ->assertSee('name="website"', false)
            ->assertSee('x-data="leadTrackingConsent"', false)
            ->assertSee(':value="marketingConsent ?', false)
            ->assertSee('Gửi yêu cầu');
    }

    public function test_campaign_landing_renders_one_cta_linking_to_an_embedded_lead_form(): void
    {
        $campaign = CampaignLandingPage::create([
            'campaign_name' => 'Chiến dịch tư vấn',
            'slug' => 'chien-dich-tu-van',
            'headline' => 'Tìm hiểu thông tin xe phù hợp',
            'is_published' => true,
        ]);

        $this->get(route('campaigns.show', $campaign->slug))
            ->assertOk()
            ->assertSee('href="#campaign-lead-form"', false)
            ->assertSee('id="campaign-lead-form"', false)
            ->assertSee('name="campaign_landing_page_id" value="'.$campaign->id.'"', false)
            ->assertSee('name="consent_given"', false)
            ->assertDontSee('type=test_drive');
    }

    public function test_honeypot_submission_is_rejected_without_saving_a_lead(): void
    {
        $this->from(route('leads.create'))
            ->post(route('leads.store'), [
                'website' => 'spam',
                'request_type' => 'quote',
                'full_name' => 'Nguyễn Văn An',
                'phone' => '0901234567',
                'region' => 'kon-tum',
                'consent_given' => '1',
            ])
            ->assertRedirect(route('leads.create'))
            ->assertSessionHasErrors('form');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_lead_notification_job_sends_email_when_email_channel_is_configured(): void
    {
        Mail::fake();
        config(['services.lead_notifications.email' => 'sales@example.test']);
        config(['services.lead_notifications.telegram_bot_token' => null]);
        config(['services.lead_notifications.telegram_chat_id' => null]);
        $lead = Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Nguyễn Văn An',
            'phone' => '0901234567',
            'region' => 'Gia Lai',
            'consent_given' => true,
            'consent_version' => '1.0',
            'consented_at' => now(),
            'event_id' => (string) str()->uuid(),
        ]);

        (new SendLeadNotifications($lead->id))->handle();

        Mail::assertSent(LeadReceived::class, fn (LeadReceived $mail): bool => $mail->lead->is($lead));
    }

    public function test_lead_form_is_rate_limited(): void
    {
        Queue::fake();
        $payload = [
            'request_type' => 'quote',
            'full_name' => 'Nguyễn Văn An',
            'phone' => '0901234567',
            'region' => 'kon-tum',
            'consent_given' => '1',
        ];

        foreach (range(1, 5) as $index) {
            $this->post(route('leads.store'), [...$payload, 'phone' => '090123456'.$index])
                ->assertRedirect(route('leads.thank-you'));
        }

        $this->post(route('leads.store'), $payload)->assertTooManyRequests();
        $this->assertDatabaseCount('leads', 5);
    }

    public function test_repeating_the_same_submission_token_saves_only_one_lead(): void
    {
        Queue::fake();
        $payload = [
            'request_type' => 'quote',
            'submission_token' => (string) str()->uuid(),
            'full_name' => 'Nguyễn Văn An',
            'phone' => '0901234567',
            'region' => 'kon-tum',
            'consent_given' => '1',
        ];

        $this->post(route('leads.store'), $payload)->assertRedirect(route('leads.thank-you'));
        $this->post(route('leads.store'), $payload)->assertRedirect(route('leads.thank-you'));
        $this->post(route('leads.store'), $payload)->assertRedirect(route('leads.thank-you'));

        $this->assertDatabaseCount('leads', 1);
        Queue::assertPushed(SendLeadNotifications::class, 1);
    }

    public function test_empty_lead_form_returns_vietnamese_field_errors(): void
    {
        $this->from(route('leads.create'))
            ->post(route('leads.store'), ['submission_token' => (string) str()->uuid()])
            ->assertRedirect(route('leads.create'))
            ->assertSessionHasErrors([
                'request_type',
                'full_name' => 'Vui lòng nhập họ tên.',
                'phone',
                'region',
                'consent_given',
            ]);
    }

    public function test_test_drive_date_cannot_be_in_the_past(): void
    {
        $this->from(route('leads.create'))
            ->post(route('leads.store'), [
                'request_type' => 'test_drive',
                'submission_token' => (string) str()->uuid(),
                'full_name' => 'Nguyễn Văn An',
                'phone' => '0901234567',
                'region' => 'kon-tum',
                'desired_date' => today()->subDay()->toDateString(),
                'consent_given' => '1',
            ])
            ->assertRedirect(route('leads.create'))
            ->assertSessionHasErrors('desired_date');
    }

    public function test_popup_quote_is_saved_with_popup_source_and_returns_to_page(): void
    {
        Queue::fake();
        $this->from(route('home'))
            ->post(route('leads.store'), [
                'request_type' => 'quote',
                'source' => 'popup-zalo',
                'submission_token' => (string) str()->uuid(),
                'full_name' => 'Nguyễn Văn An',
                'phone' => '0901234567',
                'region' => 'kon-tum',
                'consent_given' => '1',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('popup_quote_success');

        $this->assertDatabaseHas('leads', ['source' => 'popup-zalo', 'request_type' => 'quote']);
        Queue::assertPushed(SendLeadNotifications::class, 1);
    }
}
