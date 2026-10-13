@props(['scholarship'])
@php($manualNotes = $scholarship->manualVerificationNotes())
@if($manualNotes !== '')
<aside class="manual-requirements-alert" role="note" aria-label="Special requirements">
    <strong>Manual verification required</strong>
    <p>{{ $manualNotes }}</p>
    <small>Confirm these requirements with the scholarship provider.</small>
</aside>
@endif
