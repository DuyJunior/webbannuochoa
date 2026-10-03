<?php

namespace Tests\Feature;

use App\Mail\BackInStockMail;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailBrandingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
        config(['app.url' => 'https://shop.example.test']);
        URL::forceRootUrl('https://shop.example.test');
    }

    public function test_framework_verification_mail_renders_brand_without_changing_signed_action(): void
    {
        $user = (new User)->forceFill(['id' => 42, 'name' => 'An', 'email' => 'an@example.test']);
        $message = (new VerifyEmail)->toMail($user);
        $html = (string) $message->render();

        $this->assertStringContainsString('https://shop.example.test/images/brand/soopi-petal-logo.png', $html);
        $this->assertStringContainsString('width="200"', $html);
        $this->assertStringContainsString('height="73"', $html);
        $this->assertStringContainsString('alt="SOOPI · Perfume Studio"', $html);
        $this->assertStringContainsString(e($message->actionUrl), $html);
        $this->assertTrue(URL::hasValidSignature(Request::create($message->actionUrl)));
        $this->assertStringNotContainsString('notification-logo-v2.1.png', $html);
        Mail::assertNothingSent();
    }

    public function test_back_in_stock_mail_keeps_product_link_and_escaping_with_brand_header(): void
    {
        $perfume = (new Perfume)->forceFill(['id' => 9, 'name' => 'Rose & <Petal>']);
        $html = (new BackInStockMail($perfume))->render();

        $this->assertStringContainsString('https://shop.example.test/images/brand/soopi-petal-logo.png', $html);
        $this->assertStringContainsString(e($perfume->name), $html);
        $this->assertStringNotContainsString($perfume->name, $html);
        $this->assertStringContainsString(e(route('perfumes.show', $perfume)), $html);
        $this->assertStringContainsString('đã có hàng trở lại tại Soopi.', $html);
        Mail::assertNothingSent();
    }
}
