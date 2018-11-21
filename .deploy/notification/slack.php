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
function error(string $message, int $exit = NULL) {
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
    'variables' => ['PLATFORM_ROUTES', 'PLATFORM_BRANCH', 'PLATFORM_APPLICATION_NAME'],
  ],
  // Cannot send the message to the unknown location.
  [
    'message' => 'Please configure the "%s" environment variable (https://docs.platform.sh/configuration/app/variables.html).',
    'variables' => ['SLACK_WEBHOOK_URI', 'SLACK_CHANNEL', 'SLACK_SENDER'],
  ],
] as $exit_code => $group) {
  foreach ($group['variables'] as $variable) {
    if (empty($_ENV[$variable])) {
      // Try to access the variable.
      $_ENV[$variable] = getenv($variable);
    }

    if (empty($_ENV[$variable])) {
      error(sprintf($group['message'], $variable));
    }
  }
}

error('^', 66);

$message = [
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
      ],
    ],
  ],
];

foreach (json_decode(base64_decode($_ENV['PLATFORM_ROUTES'])) as $url => $route) {
  if (isset($route->upstream) && $route->upstream === $_ENV['PLATFORM_APPLICATION_NAME']) {
    $message['attachments'][0]['fields'][] = [
      'title' => 'URL',
      'value' => $url,
    ];
    break;
  }
}

// The "curl_*()" functions cannot be used because "nodejs" environments have
// very limited PHP interpreter without many extensions ("curl" among them).
// This variant of sending a POST request uses only PHP built-in functions
// and will work even with a minimalistic setup.
file_get_contents($_ENV['SLACK_WEBHOOK_URI'], FALSE, stream_context_create([
  'http' => [
    'method' => 'POST',
    'header' => ['Content-Type: application/json'],
    'content' => json_encode($message),
  ],
]));
