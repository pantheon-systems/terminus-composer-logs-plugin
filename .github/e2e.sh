#!/usr/bin/env bash

echo "===================================================="
echo "Testing Site: ${TERMINUS_SITE}"
echo "===================================================="

echo "Installing Plugin: "
terminus self:plugin:install "${PLUGIN_DIR}"
echo "===================================================="

echo "Try to apply updates"
terminus upstream:updates:status "${TERMINUS_SITE}.dev" || true
terminus upstream:updates:apply "${TERMINUS_SITE}.dev" --yes || true
echo "===================================================="

echo "Run composer:logs"
OUTPUT=$(terminus composer:logs "${TERMINUS_SITE}.dev")
echo $OUTPUT
echo $OUTPUT | grep -q "Composer version"

echo "===================================================="

echo "Run composer:logs:upstream-update"
OUTPUT=$(terminus composer:logs:upstream-update "${TERMINUS_SITE}.dev")
echo $OUTPUT
echo $OUTPUT | grep -q "Composer version"
echo "===================================================="

echo "Done!"
echo "===================================================="
