#!/bin/bash

cd app/Models

# Archivo de salida (guarda en la raíz del proyecto; ajusta si prefieres otra ruta)
OUTPUT="../../models_content.txt"

# Vacía el archivo antes de empezar
: > "$OUTPUT"

EXCLUDE=( "PersonalAccessToken.php" "Team.php" "TeamInvitation.php" "PasswordReset.php" "Sanctum" "SanctumToken.php")

find . -type f -name '*.php' ! -iname '*passport*' | while read file; do
    filename=$(basename "$file")
    skip=false

    # Excluir archivos nativos
    for native in "${EXCLUDE[@]}"; do
        if [[ "$filename" == "$native" ]]; then
            skip=true
            break
        fi
    done

    # Excluir archivos que empiezan con 'Oauth'
    if [[ "$filename" == Oauth* ]]; then
        skip=true
    fi

    if [ "$skip" = false ]; then
        echo "========== $file ==========" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
        echo -e "\n" >> "$OUTPUT"
    fi
done

echo "¡Listo! El contenido está guardado en $OUTPUT"
