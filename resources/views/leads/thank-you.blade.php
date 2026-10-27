@extends('layouts.site')

@section('title', app(\App\Services\UiText::class)->get('thank_you.title').' | '.app(\App\Services\UiText::class)->get('common.site_name'))
@section('meta_description', app(\App\Services\UiText::class)->get('thank_you.body'))
@push('meta')
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    <section class="site-container section-space max-w-3xl">
        <div class="surface-card px-5 py-10 text-center sm:px-10 sm:py-14">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-800" aria-hidden="true">
                <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <p class="eyebrow mt-6">@ui('thank_you.confirmed')</p>
            <h1 class="mt-3 text-3xl font-bold">@ui('thank_you.title')</h1>
            <p class="mx-auto mt-4 max-w-xl leading-7 text-slate-600">@ui('thank_you.body')</p>
            <ol class="mx-auto mt-8 grid max-w-2xl gap-3 text-left sm:grid-cols-3">
                @foreach(['thank_you.step_one', 'thank_you.step_two', 'thank_you.step_three'] as $step)
                    <li class="rounded-2xl bg-canvas p-4 text-sm leading-6 text-slate-700">
                        <span class="flex size-7 items-center justify-center rounded-full bg-white font-bold text-toyota-ink shadow-sm">{{ $loop->iteration }}</span>
                        <span class="mt-3 block">@ui($step)</span>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('vehicles.index') }}" class="button-secondary mt-8">@ui('lead.view_catalog')</a>
        </div>
    </section>
@endsection
