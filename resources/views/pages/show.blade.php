@extends('layouts.site')

@section('title', ($page->seo_title ?: $page->title) . ' | ' . config('app.name'))
@section('meta_description', $description)

@section('content')
    @if(!empty($preview))
        <div class="bg-amber-100 px-4 py-3 text-center font-semibold text-amber-950">@ui('preview.banner')</div>
    @endif
    @if($blocks->isNotEmpty())
        @foreach($blocks as $block)
            <x-page-block :block="$block" :page="$page" :vehicles="$vehicles" :posts="$posts" :promotions="$promotions" />
        @endforeach
    @elseif($content)
        <article class="site-container max-w-4xl whitespace-pre-line py-12 leading-8 text-slate-700">{{ $content }}</article>
    @endif
@endsection
