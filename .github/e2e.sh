#!/usr/bin/env bash

echo "===================================================="
echo "Testing Site: ${TERMINUS_SITE}"
echo "===================================================="

echo "Installing Plugin: "
terminus self:plugin:install "${PLUGIN_DIR}"
echo "===================================================="

echo "Run composer:logs"
OUTPUT=terminus composer:logs "${TERMINUS_SITE}.dev"
echo $OUTPUT
echo $OUTPUT | grep -q "Composer version"

echo "===================================================="

echo "Run composer:logs-update"
OUTPUT=terminus composer:logs-update "${TERMINUS_SITE}.dev"
echo $OUTPUT
echo $OUTPUT | grep -q "Composer version"
echo "===================================================="

echo "Done!"
echo "===================================================="
