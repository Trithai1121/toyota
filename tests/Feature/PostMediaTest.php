<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_detail_shows_cover_fallback_and_seo_article_metadata(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('posts/covers/test.webp', 'image data');

        $post = Post::create([
            'title' => 'Bài viết kiểm tra ảnh',
            'slug' => 'bai-viet-kiem-tra-anh',
            'excerpt' => 'Mô tả bài viết.',
            'source_url' => 'https://www.toyota.com.vn/tin-tuc/san-pham/bai-viet',
            'cover_image_path' => 'posts/covers/test.webp',
            'cover_image_alt' => 'Toyota trên đường phố',
            'image_credit' => 'Ảnh do đại lý được cấp quyền sử dụng',
            'body' => '<p>Nội dung.</p>',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee('og:type" content="article', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('Toyota trên đường phố', false)
            ->assertSee('Nguồn tham khảo', false)
            ->assertSee('Ảnh do đại lý được cấp quyền sử dụng', false)
            ->assertSee('Bài viết kiểm tra ảnh');
    }

    public function test_published_posts_without_image_and_source_are_not_public(): void
    {
        $post = Post::create([
            'title' => 'Bài chưa đủ ảnh',
            'slug' => 'bai-chua-du-anh',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('posts.index'))->assertDontSee($post->title);
        $this->get(route('posts.show', $post->slug))->assertNotFound();
    }

    public function test_post_body_keeps_images_and_youtube_but_removes_script_and_untrusted_iframe(): void
    {
        $post = Post::create([
            'title' => 'Bài viết an toàn',
            'slug' => 'bai-viet-an-toan',
            'body' => '<p>An toàn</p><script>alert(1)</script><img src="https://gknh.thuenhahcm.com/storage/posts/content/anh.webp" alt="Ảnh minh họa" onerror="alert(1)"><iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe><iframe src="https://evil.example/embed/1"></iframe>',
            'status' => 'draft',
        ]);

        $this->assertStringContainsString('<img', $post->body);
        $this->assertStringContainsString('alt="Ảnh minh họa"', $post->body);
        $this->assertStringContainsString('src="/storage/posts/content/anh.webp"', $post->body);
        $this->assertStringNotContainsString('gknh.thuenhahcm.com', $post->body);
        $this->assertStringContainsString('youtube-nocookie.com/embed/abc', $post->body);
        $this->assertStringNotContainsString('<script', $post->body);
        $this->assertStringNotContainsString('onerror', $post->body);
        $this->assertStringNotContainsString('evil.example', $post->body);
    }
}
