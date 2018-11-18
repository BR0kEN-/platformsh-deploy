#!/usr/bin/env bash

# - If we're already in "admin" then the directory will be just re-entered.
# - If we're in "pfqaplatform_crawler" the "admin" has to be entered in order
#   to execute Drush from the "DRUPAL_ROOT".
cd ../admin

drush php-eval "\Drupal::state()->set('psh_deploy_fail', TRUE)"
