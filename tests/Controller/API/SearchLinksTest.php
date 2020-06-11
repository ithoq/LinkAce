<?php

namespace Tests\Controller\API;

use App\Models\Link;
use App\Models\LinkList;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchLinksTest extends ApiTestCase
{
    use RefreshDatabase;

    public function testUnauthorizedRequest(): void
    {
        $response = $this->getJson('api/v1/search/links');

        $response->assertUnauthorized();
    }

    public function testWithoutQuery(): void
    {
        $response = $this->getJson('api/v1/search/links', $this->generateHeaders());

        $response->assertJsonValidationErrors([
            'query',
            'only_lists',
            'only_tags',
        ]);
    }

    public function testRegularSearchResult(): void
    {
        $link = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
        ]);

        $link2 = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://another-example.org',
        ]);

        // This link must not be present in the results
        $excludedLink = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.com',
        ]);

        $url = sprintf('api/v1/search/links?query=%s', 'example');
        $response = $this->getJson($url, $this->generateHeaders());

        $response->assertStatus(200)
            ->assertJsonFragment([
                'current_page' => 1,
            ])
            ->assertJsonFragment([
                'url' => $link->url,
            ])
            ->assertJsonFragment([
                'url' => $link2->url,
            ])
            ->assertJsonMissing([
                'url' => $excludedLink->url,
            ]);
    }

    public function testSearchByTitle(): void
    {
        $link = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'title' => 'Test Title',
        ]);

        // This link must not be present in the results
        $excludedLink = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'title' => 'Nobody cares',
        ]);

        $url = sprintf('api/v1/search/links?query=%s&search_title=1', 'Test');
        $response = $this->getJson($url, $this->generateHeaders());

        $response->assertStatus(200)
            ->assertJsonFragment([
                'url' => $link->url,
            ])
            ->assertJsonMissing([
                'url' => $excludedLink->url,
            ]);
    }

    public function testSearchByDescription()
    {
        $link = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.com',
            'description' => 'Example description',
        ]);

        // This link must not be present in the results
        $excludedLink = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.org',
            'description' => 'Lorem Ipsum',
        ]);

        $url = sprintf('api/v1/search/links?query=%s&search_description=1', 'Example');
        $response = $this->getJson($url, $this->generateHeaders());

        $response->assertStatus(200)
            ->assertJsonFragment([
                'url' => $link->url,
            ])
            ->assertJsonMissing([
                'url' => $excludedLink->url,
            ]);
    }

    public function testSearchPrivateOnly()
    {
        $link = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.com',
            'is_private' => true,
        ]);

        // This link must not be present in the results
        $excludedLink = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.org',
            'is_private' => false,
        ]);

        $url = sprintf('api/v1/search/links?query=%s&private_only=1', 'test');
        $response = $this->getJson($url, $this->generateHeaders());

        $response->assertStatus(200)
            ->assertJsonFragment([
                'url' => $link->url,
            ])
            ->assertJsonMissing([
                'url' => $excludedLink->url,
            ]);
    }

    public function testSearchBrokenOnly()
    {
        $link = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.com',
            'status' => Link::STATUS_BROKEN,
        ]);

        // This link must not be present in the results
        $excludedLink = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.org',
        ]);

        $url = sprintf('api/v1/search/links?query=%s&broken_only=1', 'test');
        $response = $this->getJson($url, $this->generateHeaders());

        $response->assertStatus(200)
            ->assertJsonFragment([
                'url' => $link->url,
            ])
            ->assertJsonMissing([
                'url' => $excludedLink->url,
            ]);
    }

    public function testSearchWithLists()
    {
        $list = factory(LinkList::class)->create([
            'user_id' => $this->user->id,
            'name' => 'Scientific Articles',
        ]);

        $link = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.com',
        ]);

        $link->lists()->sync([$list->id]);

        // This link must not be present in the results
        $excludedLink = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.org',
        ]);

        $url = sprintf('api/v1/search/links?only_lists=%s', $list->id);
        $response = $this->getJson($url, $this->generateHeaders());

        $response->assertStatus(200)
            ->assertJsonFragment([
                'url' => $link->url,
            ])
            ->assertJsonMissing([
                'url' => $excludedLink->url,
            ]);
    }

    public function testSearchWithTags()
    {
        $tag = factory(Tag::class)->create([
            'user_id' => $this->user->id,
            'name' => 'artificial-intelligence',
        ]);

        $link = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.com',
        ]);

        $link->tags()->sync([$tag->id]);

        // This link must not be present in the results
        $excludedLink = factory(Link::class)->create([
            'user_id' => $this->user->id,
            'url' => 'https://test.org',
        ]);

        $url = sprintf('api/v1/search/links?only_tags=%s', $tag->id);
        $response = $this->getJson($url, $this->generateHeaders());

        $response->assertStatus(200)
            ->assertJsonFragment([
                'url' => $link->url,
            ])
            ->assertJsonMissing([
                'url' => $excludedLink->url,
            ]);
    }
}
