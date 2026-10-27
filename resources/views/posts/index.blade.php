@extends('layouts.site')

@section('title', app(\App\Services\UiText::class)->get('post.seo_title'))
@section('meta_description', app(\App\Services\UiText::class)->get('post.seo_description'))

@section('content')
    <section class="bg-slate-50 py-12">
        <div class="site-container">
            <nav aria-label="@ui('common.breadcrumb')" class="text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:text-toyota">@ui('common.home')</a><span class="mx-2">/</span><span>@ui('post.title')</span></nav>
            <h1 class="mt-4 text-3xl font-bold sm:text-4xl">@ui('post.title')</h1>
            <p class="mt-3 max-w-2xl leading-7 text-slate-600">@ui('post.description')</p>
        </div>
    </section>
    <section class="site-container py-10">
        @if($posts->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $post)
                    <article class="rounded-2xl border border-slate-200 p-6">
                        <a href="{{ route('posts.show', $post->slug) }}" class="post-cover-link">
                            <img src="{{ Storage::disk('public')->url($post->cover_image_path) }}" alt="{{ $post->cover_image_alt }}" loading="lazy" decoding="async" width="1200" height="675" class="post-cover-image">
                        </a>
                        <p class="text-xs text-slate-500">{{ $post->published_at?->format('d/m/Y') }}</p>
                        <h2 class="mt-2 text-xl font-bold"><a class="hover:text-toyota" href="{{ route('posts.show', $post->slug) }}">{{ $post->title }}</a></h2>
                        @if($post->excerpt)<p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $post->excerpt }}</p>@endif
                        @if($post->source_url)<p class="mt-2 text-xs text-slate-500">Nguồn: <a href="{{ $post->source_url }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-2">Toyota Việt Nam</a></p>@endif
                        <a class="mt-5 inline-flex font-semibold text-toyota hover:underline" href="{{ route('posts.show', $post->slug) }}">@ui('post.read_more')</a>
                    </article>
                @endforeach
            </div>
            <div class="mt-8">{{ $posts->links() }}</div>
        @else
            <p class="rounded-xl bg-slate-50 p-6 text-slate-600">@ui('post.empty')</p>
        @endif
    </section>
@endsection
