<?php

namespace Tests\Feature;

use App\Models\Perfume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SamplingAtelierTest extends TestCase
{
    use RefreshDatabase;

    public function test_sampling_uses_five_ml_stock_and_keeps_native_form_fallback(): void
    {
        $export = getenv('SOOPI_EXPORT_SAMPLING') === '1';
        if (! $export) {
            $this->withoutVite();
        }
        foreach ([
            ['Dior Sauvage', 'Dior', 'sauvage'], ['Miss Dior Blooming Bouquet EDT', 'Dior', 'miss-dior'],
            ['Chance Eau Tendre', 'Chanel', 'chanel'], ['Delina Exclusif', 'Parfums de Marly', 'delina'],
            ['Rose Prick', 'Tom Ford', 'rose-prick'], ['Musc Noir Rose', 'Narciso Rodriguez', 'miss-dior'],
            ['In Love With You', 'Armani', 'chanel'], ['Pera Granita', 'Guerlain', 'delina'],
        ] as $index => [$name, $brand, $image]) {
            Perfume::create(['name' => $name, 'slug' => 'sample-fixture-'.$index, 'brand' => $brand,
                'gender' => $index % 2 ? 'nu' : 'nam', 'volume_ml' => 100, 'price' => 3000000,
                'stock' => 0, 'stock_5ml' => 3, 'is_active' => true, 'image_url' => 'images/gallery/'.$image.'.webp']);
        }
        $fullOnly = Perfume::create(['name' => 'Full bottle only', 'slug' => 'no-five-ml', 'brand' => 'Soopi',
            'gender' => 'unisex', 'volume_ml' => 100, 'price' => 3000000, 'stock' => 20, 'stock_5ml' => 0, 'is_active' => true]);
        $response = $this->get('/')->assertOk()->assertSee('data-typewriter', false)
            ->assertSee('method="GET" data-sample-form', false)
            ->assertViewHas('sampleCandidates', fn ($items) => $items->count() === 8 && ! $items->contains('id', $fullOnly->id));
        $candidates = $response->viewData('sampleCandidates');
        $chosen = collect([3, 1, 4, 0, 2])->map(fn ($index) => $candidates[$index]->id)->all();
        $builder = $this->get('/hop-thu-mui?'.http_build_query(['size' => 5, 'samples' => $chosen]))
            ->assertOk()->assertViewHas('initialSamples', fn ($items) => $items->pluck('id')->all() === $chosen);
        if ($export) {
            $directory = storage_path('app/backups/sampling-atelier');
            File::ensureDirectoryExists($directory);
            File::put($directory.'/home.html', $response->getContent());
            File::put($directory.'/selected.html', $builder->getContent());
        }
    }

    public function test_sold_out_sample_cards_are_clear_and_still_allow_product_details(): void
    {
        $this->withoutVite();
        $product = Perfume::create(['name' => 'Bottle with no samples', 'slug' => 'empty-samples', 'brand' => 'Soopi',
            'gender' => 'unisex', 'volume_ml' => 100, 'price' => 3000000, 'stock' => 20, 'stock_5ml' => 0, 'is_active' => true]);
        $this->get('/')->assertOk()->assertSee('Các mẫu 5 ml hiện đang tạm hết.')
            ->assertSee('data-sample-unavailable-reason="Mẫu 5 ml tạm hết"', false)
            ->assertSee('data-quick-view="'.route('perfumes.quick-view', $product).'"', false)
            ->assertViewHas('homeSampleAvailability', fn ($items) => $items[$product->id] === 0);
    }

    public function test_native_five_ml_stock_and_cart_allocations_are_reflected_on_homepage(): void
    {
        $this->withoutVite();
        $product = Perfume::create(['name' => 'Mini perfume', 'slug' => 'mini-native', 'brand' => 'Soopi',
            'gender' => 'unisex', 'volume_ml' => 5, 'price' => 200000, 'stock' => 1, 'stock_5ml' => 0, 'is_active' => true]);
        $this->get('/')->assertOk()->assertViewHas('homeSampleAvailability', fn ($items) => $items[$product->id] === 1);
        $this->withSession(['cart' => [$product->id => 1]])->get('/')->assertOk()
            ->assertViewHas('homeSampleAvailability', fn ($items) => $items[$product->id] === 0)->assertSee('Đã đủ trong giỏ');
    }
}
