#!/usr/bin/env bash

drush php-eval "\Drupal::state()->delete('psh_deploy_fail')"
