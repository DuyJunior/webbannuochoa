<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_information_pages_publish_confirmed_contacts_without_invented_shop_details(): void
    {
        $this->withoutVite();
        config(['storefront.address' => null, 'storefront.support_hours' => null, 'storefront.contact_email' => null]);
        $this->get(route('store.contact'))->assertOk()
            ->assertSee('0344216466')->assertSee('khanhduy.nguyentran.39')->assertSee('ntkduy_1312/')
            ->assertDontSee('hathu.perfume')->assertDontSee('info-shop-details', false)
            ->assertDontSee('mailto:', false)->assertSee(route('store.privacy'), false);
        $this->get(route('store.privacy'))->assertOk()->assertSee('Chính sách riêng tư')
            ->assertSee('GHN')->assertSee('MoMo')->assertSee('Groq')->assertSee('cookie')
            ->assertSee('03/10/2026')->assertSee(route('store.contact'), false);
    }

    public function test_confirmed_optional_details_are_visible_and_escaped(): void
    {
        $this->withoutVite();
        config(['storefront.address' => 'Bán online <script>bad()</script>',
            'storefront.support_hours' => '09:00–18:00', 'storefront.contact_email' => 'support@example.test']);
        $this->get(route('store.contact'))->assertOk()->assertSee('Bán online &lt;script&gt;bad()&lt;/script&gt;', false)
            ->assertDontSee('<script>bad()</script>', false)->assertSee('09:00–18:00')
            ->assertSee('mailto:support@example.test', false);
    }

    public function test_footer_faq_and_social_links_share_the_owner_confirmed_profiles(): void
    {
        $this->withoutVite();
        foreach (['store.faq', 'register'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('hathu.perfume')
                ->assertSee(config('storefront.facebook_url'), false)
                ->assertSee(config('storefront.instagram_url'), false)
                ->assertSee(route('store.privacy'), false)->assertSee(route('store.contact'), false);
        }
    }
}
