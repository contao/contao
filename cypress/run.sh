#!/bin/bash

DIR=$(dirname "$(readlink -f "$0")")

# The manager-bundle is mirrored instead of symlinked, so the console script
# finds the correct project directory. Therefore the folder needs to be removed
# before the Composer update.
rm -rf "$DIR/webspace/vendor/contao/manager-bundle"
composer up --working-dir="$DIR/webspace"

# If the -i flag is given, Cypress will open in the interactive GUI. Otherwise,
# the tests are run from the CLI without the GUI.
COMMAND="run"
if [[ ${1-} == "-i" ]]; then
    COMMAND="open"
fi

symfony server:start --port=8765 --dir="$DIR/webspace" --daemon
yarn cypress $COMMAND --config-file "$DIR/cypress.config.js"
symfony server:stop --dir="$DIR/webspace"

exit 0
