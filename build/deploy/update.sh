#!/bin/bash

git pull

# Install dependencies
php composer.phar self-update
php composer.phar install
php composer.phar install -d user/plugins/tomrochette

# Update grav plugins
bin/gpm update -y

# Page update
cd user/pages
git fetch
git reset --hard origin/master
# Restore pages mtime from git commit time
git restore-mtime