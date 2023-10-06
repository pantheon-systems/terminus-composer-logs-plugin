#!/usr/bin/env bash

echo "===================================================="
echo "Testing Site: ${TERMINUS_SITE}"
echo "===================================================="

echo "Installing Plugin: "
terminus self:plugin:install "${PLUGIN_DIR}"
echo "===================================================="

echo "Run composer:logs"
terminus composer:logs "${TERMINUS_SITE}.dev"
echo "===================================================="

echo "Run composer:logs-update"
terminus composer:logs-update "${TERMINUS_SITE}.dev"
echo "===================================================="

echo "Done!"
echo "===================================================="
