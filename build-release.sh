#!/bin/bash
# Build script for creating WordPress plugin release zip

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

echo "Building release for version: $VERSION"

# Change to parent directory
cd "$(dirname "$0")/.."

# Create release zip excluding development files
ZIP_NAME="auto-sri-v${VERSION}.zip"

zip -r "$ZIP_NAME" wp-auto-sri \
  -x "wp-auto-sri/tests/**" \
  -x "wp-auto-sri/vendor/**" \
  -x "wp-auto-sri/.git/**" \
  -x "wp-auto-sri/.github/**" \
  -x "wp-auto-sri/*.xml" \
  -x "wp-auto-sri/*.xml.dist" \
  -x "wp-auto-sri/*.cache" \
  -x "wp-auto-sri/*/*.cache" \
  -x "wp-auto-sri/*.log" \
  -x "wp-auto-sri/*.json" \
  -x "wp-auto-sri/.DS_Store" \
  -x "wp-auto-sri/*/.DS_Store" \
  -x "wp-auto-sri/.*" \
  -x "wp-auto-sri/assets/**" \
  -x "wp-auto-sri/wp-tests-config.php" \
  -x "wp-auto-sri/build-release.sh"

echo "✓ Release zip created: $ZIP_NAME"
