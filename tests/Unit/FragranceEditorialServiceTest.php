<?php

namespace Tests\Unit;

use App\Models\Perfume;
use App\Services\FragranceEditorialService;
use Tests\TestCase;

class FragranceEditorialServiceTest extends TestCase
{
    public function test_it_keeps_sauvage_concentrations_and_flankers_distinct(): void
    {
        $perfume = new Perfume(['name' => 'Dior Sauvage', 'slug' => 'dior-sauvage', 'brand' => 'Dior', 'concentration' => 'EDP']);
        $profile = FragranceEditorialService::forPerfume($perfume);

        $this->assertTrue($profile['verified']);
        $this->assertSame('pyramid', $profile['mode']);
        $this->assertSame(['Bergamot Calabria'], $profile['layers']['top']['notes']);
        $this->assertContains('Ambroxan', $profile['layers']['base']['notes']);

        foreach (['EDT', 'Parfum', 'Elixir', ''] as $concentration) {
            $perfume->concentration = $concentration;
            $this->assertPending(FragranceEditorialService::forPerfume($perfume));
        }

        $perfume->concentration = 'Eau de Parfum';
        $perfume->name = 'Dior Sauvage Elixir';
        $this->assertPending(FragranceEditorialService::forPerfume($perfume));
    }

    public function test_known_slug_cannot_override_different_name_brand_or_concentration(): void
    {
        $attributes = ['name' => 'Parfums de Marly Delina Exclusif', 'slug' => 'parfums-de-marly-delina-exclusif', 'brand' => 'Parfums de Marly', 'concentration' => 'Parfum Extrait'];
        $this->assertTrue(FragranceEditorialService::forPerfume(new Perfume($attributes))['verified']);

        foreach ([['name' => 'Parfums de Marly Delina'], ['brand' => 'Another House'], ['concentration' => 'EDT'], ['slug' => 'delina-la-rosee']] as $change) {
            $this->assertPending(FragranceEditorialService::forPerfume(new Perfume(array_merge($attributes, $change))));
        }
    }

    public function test_brand_key_notes_do_not_become_an_invented_pyramid(): void
    {
        $perfume = new Perfume(['name' => 'Chanel Chance Eau Tendre EDP', 'slug' => 'chanel-chance-eau-tendre', 'brand' => 'Chanel', 'concentration' => 'Eau de Parfum']);
        $profile = FragranceEditorialService::forPerfume($perfume);

        $this->assertTrue($profile['verified']);
        $this->assertSame('keynotes', $profile['mode']);
        $this->assertSame([], $profile['layers']);
        $this->assertSame(['Hoa nhài absolute', 'Tinh chất hoa hồng'], $profile['key_notes']);
        $this->assertStringContainsString('chanel.com', $profile['sources'][0]['url']);
        $this->assertArrayNotHasKey('longevity', $profile);

        $perfume->concentration = 'EDT';
        $this->assertPending(FragranceEditorialService::forPerfume($perfume));
    }

    public function test_ambiguous_catalog_products_do_not_receive_generated_notes_or_mood_matches(): void
    {
        $examples = [
            ['name' => 'Royal Oud 1770', 'slug' => 'royal-oud-1770', 'brand' => 'Maison Privée', 'concentration' => 'Parfum'],
            ['name' => 'Nước Hoa Cristiano Ronaldo CR7', 'slug' => 'nuoc-hoa-cristiano-ronaldo-cr7', 'brand' => 'ronaldo', 'concentration' => 'EDP'],
            ['name' => 'New perfume', 'brand' => 'New house', 'concentration' => 'EDP'],
        ];

        foreach ($examples as $attributes) {
            $perfume = new Perfume($attributes);
            $perfume->id = 9;
            $this->assertPending(FragranceEditorialService::forPerfume($perfume));
        }
    }

    private function assertPending(array $profile): void
    {
        $this->assertFalse($profile['verified']);
        $this->assertSame('pending', $profile['mode']);
        $this->assertSame([], $profile['layers']);
        $this->assertSame([], $profile['key_notes']);
        $this->assertSame([], $profile['moods']);
        $this->assertSame([], $profile['sources']);
    }
}
