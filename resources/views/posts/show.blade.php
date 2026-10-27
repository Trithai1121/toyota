@extends('layouts.site')

@section('title', $post->seo_title ?: $post->title . ' | ' . app(\App\Services\UiText::class)->get('post.brand'))
@section('meta_description', $post->seo_description ?: ($post->excerpt ?: app(\App\Services\UiText::class)->get('post.fallback_description')))
@section('canonical', route('posts.show', $post->slug))
@section('og_type', 'article')
@section('og_image', $post->cover_image_path && Storage::disk('public')->exists($post->cover_image_path) ? Storage::disk('public')->url($post->cover_image_path) : $defaultCover)
@push('meta')
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $post->title, 'description' => $post->seo_description ?: $post->excerpt, 'datePublished' => $post->published_at?->toIso8601String(), 'dateModified' => $post->updated_at?->toIso8601String(), 'image' => $post->cover_image_path && Storage::disk('public')->exists($post->cover_image_path) ? Storage::disk('public')->url($post->cover_image_path) : $defaultCover, 'mainEntityOfPage' => route('posts.show', $post->slug)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <article class="site-container max-w-4xl py-10">
        <nav aria-label="@ui('common.breadcrumb')" class="text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:text-toyota">@ui('common.home')</a><span class="mx-2">/</span><a href="{{ route('posts.index') }}" class="hover:text-toyota">@ui('post.title')</a><span class="mx-2">/</span><span>{{ $post->title }}</span></nav>
        <p class="mt-8 text-sm text-slate-500">{{ $post->published_at?->format('d/m/Y') }}</p>
        <h1 class="mt-2 text-3xl font-black leading-tight sm:text-4xl">{{ $post->title }}</h1>
        <img src="{{ Storage::disk('public')->url($post->cover_image_path) }}" alt="{{ $post->cover_image_alt }}" width="1200" height="675" fetchpriority="high" class="post-article-cover">
        @if($post->image_credit)<p class="mt-2 text-xs text-slate-500">Ảnh: {{ $post->image_credit }}</p>@endif
        @if($post->excerpt)<p class="mt-5 text-lg leading-8 text-slate-600">{{ $post->excerpt }}</p>@endif
        <div class="post-content mt-8 leading-8 text-slate-800">{!! app(\App\Services\PostContentSanitizer::class)->clean($post->body) !!}</div>
        @if($post->source_url)<p class="mt-6 border-t border-slate-200 pt-4 text-sm text-slate-600">Nguồn tham khảo: <a href="{{ $post->source_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline underline-offset-2">Toyota Việt Nam</a>. Nội dung được biên soạn lại; xem bài gốc tại nguồn.</p>@endif
        <div class="mt-8 flex flex-wrap gap-3" aria-label="Chia sẻ bài viết">
            <a class="button-secondary" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('posts.show', $post->slug)) }}">Chia sẻ Facebook</a>
            <button class="button-secondary" type="button" onclick="navigator.clipboard?.writeText(location.href)">Sao chép liên kết</button>
        </div>
        @if($relatedPosts->isNotEmpty())
            <section class="mt-12" aria-labelledby="related-posts-title">
                <h2 id="related-posts-title" class="text-2xl font-bold">Tin liên quan</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-3">@foreach($relatedPosts as $related)<li><a class="font-semibold text-toyota hover:underline" href="{{ route('posts.show', $related->slug) }}">{{ $related->title }}</a></li>@endforeach</ul>
            </section>
        @endif
        <a href="{{ route('posts.index') }}" class="mt-10 inline-flex font-semibold text-toyota hover:underline">@ui('post.back')</a>
    </article>
@endsection
