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

/**
 * @param string $message
 * @param int|null $exit
 */
function error(string $message, ?int $exit = NULL): void {
  static $errors = [];

  if ($exit === NULL) {
    $errors[] = $message;
  }
  elseif (!empty($errors)) {
    foreach ($errors as $error) {
      echo "---> $error\n";
    }

    exit($exit);
  }
}

if (count($argv) !== 3) {
  error('You must pass an exit code as a first argument and the name of an operation as the second one.');
}

if (isset($argv[1])) {
  if (!is_numeric($argv[1])) {
    error('The first argument - an exit code of a previous command - must be numeric.');
  }

  // The hook exited with a non-zero status code.
  if ($argv[1] > 0) {
    $icon = 'fire';
    $color = 'danger';
    $state = 'failed';
  }
  else {
    $icon = 'metal';
    $color = 'good';
    $state = 'succeeded';
  }
}

foreach ([
  // Non-fatal missings that just disallows to use this script locally.
  [
    'message' => 'The "%s" environment variable is missing. Unable to continue.',
    'variables' => ['PLATFORM_ROUTES', 'PLATFORM_BRANCH'],
  ],
  // Cannot send the message to the unknown location.
  [
    'message' => 'Please configure the "%s" environment variable (https://docs.platform.sh/configuration/app/variables.html).',
    'variables' => ['SLACK_WEBHOOK_URI', 'SLACK_CHANNEL', 'SLACK_SENDER'],
  ],
] as $exit_code => $group) {
  foreach ($group['variables'] as $variable) {
    if (empty($_ENV[$variable])) {
      error(sprintf($group['message'], $variable));
    }
  }
}

error('^', 66);

foreach (json_decode(base64_decode($_ENV['PLATFORM_ROUTES'])) as $url => $route) {
  if ($route->type === 'upstream' && $route->primary) {
    // The upstream has been found.
    break;
  }
}

if (!isset($url)) {
  error('The primary upstream is unknown.', 99);
}

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $_ENV['SLACK_WEBHOOK_URI']);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
  'text' => "The {$argv[2]} has been $state.",
  'channel' => $_ENV['SLACK_CHANNEL'],
  'username' => $_ENV['SLACK_SENDER'],
  'icon_emoji' => ":$icon:",
  'attachments' => [
    [
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
    ],
  ],
]));

curl_exec($ch);
curl_close($ch);
