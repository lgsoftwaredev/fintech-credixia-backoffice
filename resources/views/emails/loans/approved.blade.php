@component('mail::message')
# Tu préstamo fue aprobado

Tu préstamo #{{ $loan->id }} fue aprobado. Revisa condiciones y calendario de pagos.


Gracias,<br>
{{ config('app.name') }}
@endcomponent
