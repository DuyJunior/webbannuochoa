@if(count($item->sample_names))
    <p style="font-size:12px;line-height:1.7;margin:8px 0;overflow-wrap:anywhere"><strong>Mẫu 5ml:</strong> {{ implode(' · ', $item->sample_names) }}</p>
@endif
