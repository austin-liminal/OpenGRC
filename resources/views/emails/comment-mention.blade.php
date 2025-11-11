<x-mail::message>
# You were mentioned in a comment

Hello,

**{{ $commenterName }}** mentioned you in a comment on **{{ $commentableTitle }}**.

<x-mail::panel>
{{ $commentBody }}
</x-mail::panel>

@if($commentUrl)
<x-mail::button :url="$commentUrl">
View Comment
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
