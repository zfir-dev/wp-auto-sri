#!/bin/bash
# Script to copy plugin files to SVN directory for WordPress.org upload

# Get version from argument or extract from main plugin file
if [ -n "$1" ]; then
  VERSION="$1"
else
  # Extract version from auto-sri.php
  VERSION=$(grep -m 1 "Version:" "$(dirname "$0")/auto-sri.php" | sed 's/.*Version: *\([0-9.]*\).*/\1/')
  
  if [ -z "$VERSION" ]; then
    echo "Error: Could not detect version from auto-sri.php"
    exit 1
  fi
fi

echo "Preparing release for version: $VERSION"

# Check if SVN directory exists
if [ ! -d "../auto-sri" ]; then
  echo "Error: SVN directory ../auto-sri not found"
  echo "Please run: svn checkout https://plugins.svn.wordpress.org/auto-sri ../auto-sri"
  exit 1
fi

echo "Syncing files to ../auto-sri/trunk..."

# Sync files to SVN trunk, excluding development files
rsync -av --delete \
  --exclude='tests/' \
  --exclude='vendor/' \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='*.xml' \
  --exclude='*.xml.dist' \
  --exclude='*.cache' \
  --exclude='*/*.cache' \
  --exclude='*.log' \
  --exclude='*.json' \
  --exclude='.DS_Store' \
  --exclude='*/.DS_Store' \
  --exclude='.*' \
  --exclude='assets' \
  --exclude='wp-tests-config.php' \
  --exclude='zip.sh' \
  --exclude='release.sh' \
  . ../auto-sri/trunk/

echo ""
echo "✓ Files synced to ../auto-sri/trunk/"
echo ""
echo "Next steps:"
echo "1. cd ../auto-sri"
echo "2. svn status  # Review changes"
echo "3. svn add --force trunk  # Add new files recursively"
echo "4. svn ci -m 'Release version $VERSION'  # Commit to trunk"
echo "5. svn cp trunk tags/$VERSION  # Tag the release"
echo "6. svn ci -m 'Tagging version $VERSION'  # Commit the tag"
