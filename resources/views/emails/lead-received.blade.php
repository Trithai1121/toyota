<h1>@ui('email.lead_title')</h1>
<p>@ui('email.lead_id'): {{ $lead->id }}</p>
<p>@ui('email.request_type'): @ui($lead->request_type === 'test_drive' ? 'lead.test_drive' : 'lead.quote')</p>
<p>@ui('email.full_name'): {{ $lead->full_name }}</p>
<p>@ui('email.phone'): {{ $lead->phone }}</p>
<p>@ui('email.vehicle'): {{ $lead->vehicle?->name ?? app(\App\Services\UiText::class)->get('email.not_selected') }}</p>
<p>@ui('email.region'): {{ $lead->region }}</p>
<p>@ui('email.desired_date'): {{ $lead->desired_date?->format('d/m/Y') ?? app(\App\Services\UiText::class)->get('email.not_provided') }}</p>
<p>@ui('email.note'): {{ $lead->customer_note ?: app(\App\Services\UiText::class)->get('email.none') }}</p>
