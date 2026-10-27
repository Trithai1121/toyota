<div class="max-h-[60vh] overflow-auto">
    @if($differences === [])
        <p class="text-sm text-gray-600">@ui('page.revision_no_changes')</p>
    @else
        <table class="w-full border-collapse text-left text-sm">
            <thead>
                <tr class="border-b">
                    <th class="p-2">@ui('page.revision_field')</th>
                    <th class="p-2">@ui('page.revision_previous')</th>
                    <th class="p-2">@ui('page.revision_current')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($differences as $difference)
                    <tr class="border-b align-top">
                        <th class="p-2 font-mono font-medium">{{ $difference['path'] }}</th>
                        <td class="whitespace-pre-wrap p-2 text-red-700">{{ $difference['before'] }}</td>
                        <td class="whitespace-pre-wrap p-2 text-green-700">{{ $difference['after'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
