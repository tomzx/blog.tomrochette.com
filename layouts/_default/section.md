{{ with .File }}{{ readFile (printf "content/%s" .Path) }}{{ end }}
