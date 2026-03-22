#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
CONTENT_DIR="$PROJECT_ROOT/content/blog"
PUBLIC_DIR="$PROJECT_ROOT/public"
PANDOC_DIR="$SCRIPT_DIR/pandoc"

EPUB_TEMPLATE="$PANDOC_DIR/epub/default.html"
EPUB_CSS="$PANDOC_DIR/epub/default.css"
LATEX_TEMPLATE="$PANDOC_DIR/latex/default.tex"
CSL_STYLE="$PANDOC_DIR/csl/transactions-on-computer-systems.csl"

AUTHOR="Tom Rochette"
INSTITUTE="Coreteks Inc."
COMMIT_SHA="$(git rev-parse --short HEAD)"

echo "Generating Pandoc exports for blog posts..."

if [ ! -d "$CONTENT_DIR" ]; then
  echo "Error: Blog content directory not found: $CONTENT_DIR"
  exit 1
fi

mkdir -p "$PUBLIC_DIR/exports"

for md_file in "$CONTENT_DIR"/*.md; do
  [ -f "$md_file" ] || continue

  basename=$(basename "$md_file" .md)
  slug=$(basename "$basename")
  
  title=$(grep -m 1 '^title:' "$md_file" | sed 's/^title: //; s/^["'"'"']//; s/["'"'"']$//')
  date_str=$(grep -m 1 '^date:' "$md_file" | sed 's/^date: //; s/^["'"'"']//; s/["'"'"']$//')
  
  formatted_date=$(date -d "$date_str" +"%B %e, %Y" 2>/dev/null || echo "$date_str")

  bib_file="${md_file%.md}.bib"
  has_bib=false
  if [ -f "$bib_file" ]; then
    has_bib=true
  fi

  echo "Processing: $slug"

  pandoc_args=(
    --from="markdown+lists_without_preceding_blankline+hard_line_breaks"
    --variable="author:$AUTHOR"
    --metadata="author:$AUTHOR"
    --variable="institute:$INSTITUTE"
    --variable="colorlinks"
    --number-sections
    --variable="date:$formatted_date"
    --variable="commit-url:https://github.com/tomzx/blog.tomrochette.com-content/blob/$COMMIT_SHA/content/blog/${basename}.md"
    --variable="commit:$COMMIT_SHA"
  )

  pdf_output="$PUBLIC_DIR/exports/${slug}.pdf"
  pandoc_pdf_args=("${pandoc_args[@]}" --template="$LATEX_TEMPLATE" --variable="geometry:margin=1in")

  if [ "$has_bib" = true ]; then
    pandoc_pdf_args+=(--bibliography="$bib_file")
    pandoc_pdf_args+=(--metadata="link-citations:true")
    pandoc_pdf_args+=(--metadata="nocite:@*")
    if [ -f "$CSL_STYLE" ]; then
      pandoc_pdf_args+=(--csl="$CSL_STYLE")
    fi
  fi

  pandoc "${pandoc_pdf_args[@]}" --to="latex" --output="$pdf_output" "$md_file"
  echo "  ✓ Generated PDF: $pdf_output"

  epub_output="$PUBLIC_DIR/exports/${slug}.epub"
  pandoc_epub_args=("${pandoc_args[@]}" --template="$EPUB_TEMPLATE" --css="$EPUB_CSS")

  pandoc "${pandoc_epub_args[@]}" --to="epub3" --output="$epub_output" "$md_file"
  echo "  ✓ Generated EPUB: $epub_output"

  if [ "$has_bib" = true ]; then
    bib_output="$PUBLIC_DIR/exports/${slug}.bib"
    cp "$bib_file" "$bib_output"
    echo "  ✓ Copied BIBTeX: $bib_output"
  fi
done

echo "Pandoc export generation complete."
