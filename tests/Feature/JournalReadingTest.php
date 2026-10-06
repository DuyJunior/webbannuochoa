<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalReadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!getenv('SOOPI_JOURNAL_EXPORT')) $this->withoutVite();
    }

    public function test_reading_room_and_article_keep_localized_content_and_real_destinations(): void
    {
        $article = Article::where('slug', 'hieu-ba-tang-huong')->firstOrFail();
        foreach (['vi'=>'Bạn muốn hiểu', 'en'=>'What would you like'] as $locale=>$label) {
            $this->withSession(['locale'=>$locale]);
            $index = $this->get(route('store.journal'))->assertOk()->assertSee($label)
                ->assertSee(route('store.article', $article->slug), false)->assertSee('data-story-explorer', false);
            $story = $this->get(route('store.article', $article->slug))->assertOk()
                ->assertSee('data-reading-tools hidden', false)->assertSee('id="story-prose"', false)
                ->assertViewHas('relatedArticles', fn($related) => !$related->contains('id', $article->id));
            if (getenv('SOOPI_JOURNAL_EXPORT')) {
                file_put_contents(storage_path('app/journal-'.$locale.'.html'), $index->getContent());
                file_put_contents(storage_path('app/journal-story-'.$locale.'.html'), $story->getContent());
            }
        }
    }

    public function test_hidden_articles_are_not_linked_in_paths_library_or_related_stories(): void
    {
        $hidden = Article::where('slug', 'hieu-ba-tang-huong')->firstOrFail();
        $hidden->update(['is_published'=>false]);
        $this->get(route('store.journal'))->assertOk()->assertDontSee(route('store.article', $hidden->slug), false);
        $visible = Article::where('is_published', true)->firstOrFail();
        $this->get(route('store.article', $visible->slug))->assertOk()
            ->assertDontSee(route('store.article', $hidden->slug), false);
        $this->get(route('store.article', $hidden->slug))->assertNotFound();
    }

    public function test_archive_pagination_does_not_omit_articles_and_missing_art_has_a_fallback(): void
    {
        Article::query()->delete();
        for ($i=1; $i<=11; $i++) {
            Article::create(['title'=>'Story '.$i, 'slug'=>'story-'.$i, 'excerpt'=>'A quiet introduction.', 'body'=>'A quiet fragrance story.',
                'is_published'=>true]);
        }
        $first = $this->get(route('store.journal'))->assertOk()->assertViewHas('articles', fn($articles)=>$articles->count()===9)
            ->assertSee('page=2#thu-vien-cau-chuyen', false)->assertSee('images/journal/detail.webp', false);
        $second = $this->get(route('store.journal', ['page'=>2]))->assertOk()
            ->assertViewHas('articles', fn($articles)=>$articles->count()===2)->assertDontSee('data-story-explorer', false);
        $ids = $first->viewData('articles')->modelKeys();
        $this->assertCount(11, array_unique(array_merge($ids, $second->viewData('articles')->modelKeys())));
    }

    public function test_empty_reading_room_remains_useful_without_broken_story_links(): void
    {
        Article::query()->update(['is_published'=>false]);
        $this->get(route('store.journal'))->assertOk()->assertSee('Những trang hương đang được viết.')
            ->assertDontSee('data-story-explorer', false)->assertSee(route('store.finder'), false);
    }
}
