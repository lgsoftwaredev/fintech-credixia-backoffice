#!/bin/bash
# Script: export-api-file.sh
# Exporta el contenido de routes/api.php a api-routes.txt

INPUT="routes/api.php"
OUTPUT="api-routes.txt"

if [ -f "$INPUT" ]; then
  cp "$INPUT" "$OUTPUT"
  echo "✅ Contenido de $INPUT exportado en $OUTPUT"
else
  echo "❌ No se encontró $INPUT"
fi
