#!/bin/bash
# Regenerar autoload de Composer
composer dump-autoload

# Limpiar caches y refrescar la app
php artisan optimize:clear

php artisan queue:restart

# Iniciar servidor Laravel en puerto 8000
php artisan serve --host=0.0.0.0 --port=8000 &

# Iniciar workers de las colas (default y moderation)
php artisan queue:work --queue=default,moderation &

# Mantener procesos en foreground
wait
