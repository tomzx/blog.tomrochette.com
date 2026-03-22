# Pandoc Export Generation

This directory contains the scripts and templates for generating PDF, EPUB, and BIBTeX exports of blog posts using Pandoc.

## Files

- `generate-exports.sh` - Main script that generates exports for all blog posts
- `pandoc/` - Directory containing Pandoc templates and assets
  - `epub/` - EPUB templates and CSS
    - `default.html` - HTML template for EPUB generation
    - `default.css` - CSS styling for EPUB
  - `latex/` - LaTeX templates
    - `default.tex` - LaTeX template for PDF generation
  - `csl/` - CSL (Citation Style Language) files
    - `transactions-on-computer-systems.csl` - Citation style for academic references

## How It Works

The `generate-exports.sh` script:

1. Iterates through all Markdown files in `content/blog/`
2. For each post, generates:
   - **PDF** using Pandoc's LaTeX engine with custom template
   - **EPUB** using Pandoc's EPUB3 engine with custom HTML template and CSS
   - **BIBTeX** (if a `.bib` file exists alongside the Markdown file)
3. Places the generated files in the `public/exports/` directory
4. Includes metadata (author, date, git commit hash, etc.) in the exports

## Usage

### Manual Local Generation

```bash
# Install prerequisites (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install -y pandoc texlive-xetex texlive-fonts-recommended texlive-latex-extra texlive-lang-english

# Run the script
./bin/generate-exports.sh
```

### CI/CD Integration

The script is automatically run in the GitHub Actions workflow after Hugo builds the site. See `.github/workflows/hugo-build-deploy.yml` for details.

### Download Links

Generated exports are available at:
- PDF: `/exports/{slug}.pdf`
- EPUB: `/exports/{slug}.epub`
- BIBTeX: `/exports/{slug}.bib` (only if bibliography exists)

## Templates

### PDF Template

The LaTeX template (`pandoc/latex/default.tex`) includes:
- Document class configuration
- Font and encoding settings
- Geometry (margins)
- Hyperref configuration
- Table of contents support
- Bibliography support

### EPUB Template

The HTML template (`pandoc/epub/default.html`) includes:
- XHTML 1.1 DOCTYPE
- Title and metadata
- CSS styling
- Author and date information
- Git commit reference

## Metadata

The script extracts the following from each blog post:

- **Title** - From frontmatter `title` field
- **Date** - From frontmatter `date` field, formatted as "Month Day, Year"
- **Author** - Hardcoded as "Tom Rochette"
- **Institute** - Hardcoded as "Coreteks Inc."
- **Git Commit** - Short SHA of current git HEAD
- **Git URL** - Link to the file in GitHub repository

## Bibliography Support

If a `.bib` file exists alongside a blog post (e.g., `my-post.md` has `my-post.bib`), the script will:

1. Include the bibliography in PDF exports
2. Enable citation linking
3. Copy the `.bib` file to the exports directory for direct download
4. Use CSL style for citation formatting (if available)

## Requirements

- Pandoc (version 2.0+)
- LaTeX (for PDF generation):
  - texlive-xetex
  - texlive-fonts-recommended
  - texlive-latex-extra
  - texlive-lang-english

## Troubleshooting

### PDF Generation Fails

Ensure all required LaTeX packages are installed:
```bash
sudo apt-get install -y texlive-xetex texlive-fonts-recommended texlive-latex-extra texlive-lang-english
```

### Missing Exports

Check that:
1. The blog post exists in `content/blog/`
2. The frontmatter includes a `title` and `date`
3. Hugo has built the site first (public directory exists)

### BIBTeX Not Showing

BIBTeX exports are only generated if a `.bib` file exists alongside the Markdown file. The download link will also only appear in the UI if the bibliography file exists.

## Migration from Grav

This replaces the on-demand generation in the old Grav CMS plugin (`user/plugins/tomrochette/src/tomzx/TomRochette/PandocExport.php`). The key differences:

1. **Static generation** - Exports are generated during build, not on-demand
2. **No caching logic** - Files are regenerated on each build
3. **Simplified deployment** - No PHP runtime required
4. **Better performance** - Pre-generated files serve faster
