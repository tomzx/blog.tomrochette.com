# Deployment Guide

This document explains how to deploy the Hugo site to various hosting providers.

## Current Status

The site has been migrated from Grav CMS to Hugo. The deployment pipeline is configured via GitHub Actions.

## Deployment Options

### GitHub Pages (Recommended)

The site is configured to deploy to GitHub Pages by default.

**Setup:**
1. Go to your GitHub repository Settings → Pages
2. Under "Build and deployment", set Source to "GitHub Actions"
3. Push to `main` or `master` branch to trigger deployment

**Features:**
- Free hosting
- Automatic HTTPS
- Fast CDN
- Built-in CI/CD

**Configuration:**
The workflow file is located at `.github/workflows/hugo-build-deploy.yml`.

### Netlify

To deploy to Netlify instead:

1. Connect your GitHub repository to Netlify
2. Set build command: `hugo --minify`
3. Set publish directory: `public`
4. Optionally add environment variables:
   - `HUGO_VERSION`: `latest`
   - `HUGO_EXTENDED`: `true`

**Configuration:**
Create `netlify.toml` in the root:

```toml
[build]
  command = "hugo --minify"
  publish = "public"

[context.production.environment]
  HUGO_VERSION = "latest"
  HUGO_EXTENDED = "true"
```

### Vercel

To deploy to Vercel:

1. Connect your GitHub repository to Vercel
2. Set build command: `hugo --minify`
3. Set output directory: `public`

**Configuration:**
Update `vercel.json` (replacing the old PHP config):

```json
{
  "builds": [
    {
      "src": "**/*",
      "use": "@vercel/static"
    }
  ],
  "routes": [
    {
      "src": "/(.*)",
      "dest": "/$1"
    }
  ]
}
```

### Cloudflare Pages

To deploy to Cloudflare Pages:

1. Connect your GitHub repository to Cloudflare Pages
2. Set build command: `hugo --minify`
3. Set output directory: `public`

## Local Development

### Installing Hugo

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install hugo
```

**macOS:**
```bash
brew install hugo
```

**Windows:**
```powershell
choco install hugo-extended
```

### Building Locally

```bash
# Build the site
hugo

# Build with minification
hugo --minify

# Serve locally with hot reload
hugo server -D

# Serve locally with draft posts and future dated content
hugo server -D -F
```

## Continuous Deployment

The GitHub Actions workflow (`hugo-build-deploy.yml`) will automatically:

1. Build the site on every push to `main`/`master`
2. Build and create an artifact on every pull request (for preview)
3. Deploy to GitHub Pages on successful builds

### Workflow Triggers

- **Push to main/master**: Full build and deployment
- **Pull requests**: Build and upload artifact for review

### Manual Deployment

To manually trigger a deployment:

1. Go to Actions tab in GitHub
2. Select "Build and Deploy Hugo Site" workflow
3. Click "Run workflow" → Select branch → Click "Run workflow"

## Environment Variables

The following environment variables can be set in GitHub repository settings:

- `HUGO_VERSION`: Hugo version to use (default: latest)
- `HUGO_MINIFY`: Set to `true` to minify output (default: true)

## Troubleshooting

### Build Fails

1. Check the Actions tab for error logs
2. Ensure all Hugo content files have proper frontmatter
3. Verify that all dependencies are committed to the repository

### Deployment Issues

1. Ensure GitHub Pages is enabled in repository settings
2. Verify that the `gh-pages` branch is created by the workflow
3. Check that custom domain (if any) is properly configured

### Slow Builds

- Use `hugo --minify` only for production builds
- Consider using Hugo's `--quiet` flag to reduce output
- Optimize images and assets before deployment

## Migration Notes

This deployment setup replaces the old PHP/Grav deployment script (`build/deploy/update.sh`). The old deployment method has been deprecated.

For any deployment-related issues, check the GitHub Actions workflow logs first.

## Pandoc Export

The site automatically generates PDF, EPUB, and BIBTeX exports for all blog posts during the build process.

### How It Works

- **PDF Generation**: Uses Pandoc with LaTeX to create PDF versions of blog posts
- **EPUB Generation**: Uses Pandoc to create EPUB3 e-book versions
- **BIBTeX Export**: Automatically exports bibliography files if they exist

The export generation is handled by the `bin/generate-exports.sh` script, which runs after Hugo builds the site.

### Downloads

Generated exports are available at:
- `/exports/{slug}.pdf` - PDF version
- `/exports/{slug}.epub` - EPUB version
- `/exports/{slug}.bib` - BIBTeX bibliography (if available)

### Templates

Export templates are located in `bin/pandoc/`:
- `epub/default.html` - EPUB HTML template
- `epub/default.css` - EPUB styling
- `latex/default.tex` - LaTeX template for PDF

See `bin/PANDOC_README.md` for detailed documentation on the export system.
