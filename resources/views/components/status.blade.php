@props(['status'])
@php($text=$status instanceof \BackedEnum?$status->value:$status)
@php($tone=match($text){'Eligible','Met'=>'eligible','Not Eligible','Not Met'=>'ineligible','Manual Verification Required'=>'manual',default=>'neutral'})
<span class="status-badge {{ $tone }}"><span aria-hidden="true">{{ match($tone){'eligible'=>'✓','ineligible'=>'×','manual'=>'!',default=>'–'} }}</span>{{ $text==='Not Applicable'?'N/A':$text }}</span>
