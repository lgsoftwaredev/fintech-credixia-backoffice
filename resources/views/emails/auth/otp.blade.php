@component('mail::message')
# Hola {{ $user->name ?? 'Usuario' }}

Tu código de verificación es:

@component('mail::panel')
**{{ $code }}**
@endcomponent

Este código es válido por **{{ $ttl }} minutos** y será usado para {{ $purpose === 'register' ? 'confirmar tu registro' : 'iniciar sesión' }}.

Si no solicitaste este código, puedes ignorar este mensaje.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
