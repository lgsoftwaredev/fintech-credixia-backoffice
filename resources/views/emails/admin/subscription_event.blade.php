@component('mail::message')
# {{ $title }}

{{ $message }}

@isset($meta)
@component('mail::panel')
@foreach($meta as $k => $v)
**{{ $k }}:** {{ is_scalar($v) ? $v : json_encode($v) }}
@endforeach
@endcomponent
@endisset

Gracias,<br>
{{ config('app.name') }}
@endcomponent
