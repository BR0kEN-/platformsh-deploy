# Platform.sh

## Hooks runner

The [hooks/hook.sh](hook.sh) is a Bash script that is capable to run `deploy` or `build` hook.

Example of usage in `.platform.app.yaml`.

```yaml
hooks:
  build: 'bash hook.sh build'
  # Send a notification via Slack after the deploy.
  deploy: 'bash hook.sh deploy slack'
```

### Configuration

To setup the runner properly you may need to edit available variables at the top of the file.

- `ACTION` - the action to perform, defaults to `$1`.
- `NOTIFIER` - one of the available notification clients (the name of a `*.php` script in the `notifications` subdirectory) , defaults to `$2`.

## Process

Within the `.deploy/<ACTION>` you may consider creating two handlers:

  - `_succeeded.sh` - the file that will be included in a runtime once all commands in a process will successfully end.
  - `_failed.sh` - the same as above, but only after first non-zero return code (a process will be terminated).

Available environment variables:

- `PLATFORM_BRANCH` - the name of a Git branch an environment exists for (the `unknown-at-build-stage` if the action is `build`).

*This script can be run locally if it doesn't rely on Platform.sh environment.*

### Specific environment handler

The `build` hook executes in an isolation, therefore, an environment cannot be determined. The opposite situation for `deploy` and it gives the possibility to perform environment-specific actions. The following Bash scripts may be included (after non-specific) in a runtime context if exist:

- `.deploy/environment/$PLATFORM_BRANCH/<PROCESS_SUBDIR_KEY>.sh`
- `.deploy/environment/$PLATFORM_BRANCH/_succeeded.sh`
- `.deploy/environment/$PLATFORM_BRANCH/_failed.sh`

### Notifications

Independently on a status of one of the hooks, you might consider sending the notification.

#### Slack

Make sure your `.platform.app.yaml` has the following contents:

```yaml
variables:
  env:
    SLACK_WEBHOOK_URI: 'https://hooks.slack.com/services/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'
    SLACK_CHANNEL: '#pfqaplatform-ci'
    SLACK_SENDER: 'Pfizer QA Platform'
```

#### Custom

- Create the `NAME.php` inside the `notifications`.
- Pass the `NAME` as a second argument to the `hook.sh`.
- The `NAME.php` will receive two arguments: `EXIT_CODE` and `ACTION`.

Example:

```yaml
hooks:
  # Notify with "notifications/NAME.php" about the "build".
  build: 'bash hook.sh build NAME'
  # Notify with "notifications/telegram.php" about the "deploy".
  deploy: 'bash hook.sh deploy telegram'
```
