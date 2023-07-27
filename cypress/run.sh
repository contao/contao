#!/bin/bash

DIR=$(dirname "$(readlink -f "$0")")

# The manager-bundle is mirrored instead of symlinked, so the console script
# finds the correct project directory. Therefore the folder needs to be removed
# before the Composer update.
rm -rf "$DIR/webspace/vendor/contao/manager-bundle"
composer up --working-dir="$DIR/webspace"

symfony server:start --port=8765 --dir="$DIR/webspace" --daemon
yarn cypress run --config-file "$DIR/cypress.config.js"
symfony server:stop --dir="$DIR/webspace"

exit 0
