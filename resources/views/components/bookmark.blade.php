@props(['scholarship','saved'=>false])
<form method="POST" action="{{ route($saved?'bookmarks.destroy':'bookmarks.store',$scholarship) }}" class="d-inline">@csrf @if($saved) @method('DELETE') @endif<button class="btn btn-soft" aria-label="{{ $saved?'Remove saved':'Save' }} {{ $scholarship->name }}" aria-pressed="{{ $saved?'true':'false' }}">{{ $saved?'✓ Saved':'♧ Save' }}</button></form>
