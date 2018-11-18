#!/usr/bin/env bash

# Do not rebuild the cache after running database updates.
drush updatedb --cache-clear=0 -y
# Forcibly rebuild the cache. This is needed because if no
# DB updates weren't run then the rebuild not happen.
drush cache-rebuild -y
drush config-import -y
drush entity-updates -y
