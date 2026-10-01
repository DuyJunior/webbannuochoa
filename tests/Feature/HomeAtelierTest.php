<?php

namespace Tests\Feature;

use App\Models\Perfume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAtelierTest extends TestCase
{
    use RefreshDatabase;

    public function test_gift_recommendations_respect_the_selected_budget(): void
    {
        $this->withoutVite();
        $cheap = Perfume::create(['name'=>'Gift rose', 'slug'=>'gift-rose', 'brand'=>'Soopi', 'gender'=>'nu', 'volume_ml'=>100, 'price'=>3000000, 'sale_price'=>1500000, 'stock'=>5, 'is_active'=>true]);
        Perfume::create(['name'=>'Expensive rose', 'slug'=>'expensive-rose', 'brand'=>'Soopi', 'gender'=>'nu', 'volume_ml'=>100, 'price'=>5000000, 'stock'=>5, 'is_active'=>true]);
        $this->get('/chon-huong?occasion=hen-ho&max_price=2000000')->assertOk()
            ->assertViewHas('recommended', fn ($items) => $items->modelKeys() === [$cheap->id]);
        $this->getJson('/chon-huong?max_price=-1')->assertUnprocessable();
    }

    public function test_sample_selection_reaches_builder_and_ignores_inactive_or_unknown_ids(): void
    {
        $this->withoutVite();
        $active = Perfume::create(['name'=>'Sample', 'slug'=>'sample', 'brand'=>'Soopi', 'gender'=>'nu', 'volume_ml'=>100, 'price'=>1500000, 'stock'=>5, 'is_active'=>true]);
        $hidden = Perfume::create(['name'=>'Hidden', 'slug'=>'hidden', 'brand'=>'Soopi', 'gender'=>'nu', 'volume_ml'=>100, 'price'=>1500000, 'stock'=>5, 'is_active'=>false]);
        $this->get('/hop-thu-mui?'.http_build_query(['size'=>5, 'samples'=>[$active->id, $hidden->id, 99999]]))->assertOk()
            ->assertViewHas('initialSize', 5)->assertViewHas('initialSamples', fn ($items) => $items->pluck('id')->all() === [$active->id]);
        $this->getJson('/hop-thu-mui?size=9')->assertUnprocessable();
    }
}
