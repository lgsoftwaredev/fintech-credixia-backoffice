@component('mail::message')
# Recordatorio de pago

Tienes un pago próximo con vencimiento el **{{ optional($payment->due_date)->format('d/m/Y') }}**.


Si ya realizaste la transferencia SPEI, ignora este mensaje.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
