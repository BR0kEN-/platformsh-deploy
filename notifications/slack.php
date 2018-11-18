#!/usr/bin/env php
<?php

/**
 * @file
 * Send notification to Slack from a Platform.sh.
 *
 * @example
 * The script takes the following arguments:
 * - The exit status code of a hook. If it's "0" then we ok.
 * - The name of an action: either "build" or "deploy".
 * @code
 * php slack.php 0 deploy
 * @endcode
 */

use Maknz\Slack\Client as Slack;

foreach ([
  // Non-fatal missings that just disallows to use this script locally.
  [
    'message' => 'The "%s" environment variable is missing. Unable to continue.',
    'variables' => ['PLATFORM_ROUTES', 'PLATFORM_BRANCH', 'PLATFORM_APP_DIR'],
  ],
  // Cannot send the message to the unknown location.
  [
    'message' => 'Please configure the "%s" environment variable (https://docs.platform.sh/configuration/app/variables.html).',
    'variables' => ['SLACK_WEBHOOK_URI', 'SLACK_CHANNEL', 'SLACK_SENDER'],
  ],
] as $exit_code => $group) {
  foreach ($group['variables'] as $variable) {
    empty($_ENV[$variable]) && printf($group['message'] . '%s', $variable, PHP_EOL) && exit($exit_code);
  }
}

// Go to the directory with root "composer.json".
chdir($_ENV['PLATFORM_APP_DIR']);
// Do not assume the "vendor" by default. It might be changed.
require_once trim(`composer config vendor-dir`) . '/autoload.php';

foreach (json_decode(base64_decode($_ENV['PLATFORM_ROUTES'])) as $url => $route) {
  if ($route->type === 'upstream') {
    // The upstream has been found.
    break;
  }
}

// The upstream and exit code both known.
if (isset($url)) {
  $slack = new Slack($_ENV['SLACK_WEBHOOK_URI']);

  // The hook exited with "0" status code.
  if ((int) $argv[1] === 0) {
    $icon = 'metal';
    $color = 'good';
    $state = 'succeeded';
  }
  else {
    $icon = 'fire';
    $color = 'danger';
    $state = 'failed';
  }

  $slack->sendMessage(
    $slack
      ->createMessage()
      ->to($_ENV['SLACK_CHANNEL'])
      ->from($_ENV['SLACK_SENDER'])
      ->setText("The {$argv[2]} has been $state.")
      ->setIcon(":$icon:")
      ->attach([
        'color' => $color,
        'fallback' => '',
        'fields' => [
          [
            'title' => 'Environment',
            'value' => $_ENV['PLATFORM_BRANCH'],
          ],
          [
            'title' => 'URL',
            'value' => $url,
          ],
        ],
      ])
  );
}
