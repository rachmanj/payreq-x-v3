<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PreventHtmlResponseCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_html_page_includes_no_store_cache_control(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('0', $response->headers->get('Expires'));
    }

    public function test_json_ajax_response_includes_no_store_cache_control(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('menu.search.items'));

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('0', $response->headers->get('Expires'));
    }

    public function test_existing_cache_control_on_json_is_preserved(): void
    {
        Route::middleware('web')->get('/__test/prevent-html-cache-json-explicit', function () {
            return response()->json(['ok' => true], 200, [
                'Cache-Control' => 'public, max-age=600',
            ]);
        });

        $response = $this->getJson('/__test/prevent-html-cache-json-explicit');

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=600', $cacheControl);
        $this->assertStringNotContainsString('no-store', $cacheControl);
    }

    public function test_file_download_response_is_not_altered(): void
    {
        Route::middleware('web')->get('/__test/prevent-html-cache-download', function () {
            return response('file-bytes', 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="export.xlsx"',
                'Cache-Control' => 'private, max-age=3600',
            ]);
        });

        $response = $this->get('/__test/prevent-html-cache-download');

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('max-age=3600', $cacheControl);
        $this->assertStringNotContainsString('no-store', $cacheControl);
        $this->assertNull($response->headers->get('Pragma'));
    }

    public function test_existing_cache_control_on_html_is_preserved(): void
    {
        Route::middleware('web')->get('/__test/prevent-html-cache-explicit', function () {
            return response('<html><body>cached page</body></html>', 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'public, max-age=600',
            ]);
        });

        $response = $this->get('/__test/prevent-html-cache-explicit');

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=600', $cacheControl);
        $this->assertStringNotContainsString('no-store', $cacheControl);
    }
}
