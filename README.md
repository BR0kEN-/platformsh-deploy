# Platform.sh

The deploy/build scripts for Platform.sh that needs to lay near `.platform.app.yaml`.

## Hooks runner

The [hooks/hook.sh](hook.sh) is a Bash script that is capable to run `deploy` or `build` hook.

Example of usage in `.platform.app.yaml`.

```yaml
hooks:
  build: 'bash pfqaplatform_deploy/hook.sh build'
  # Send a notification via Slack after the deploy.
  deploy: 'bash pfqaplatform_deploy/hook.sh deploy slack'
```

### Configuration

To setup the runner properly you may need to edit available variables at the top of the file.

- `ACTION` - the action to perform, defaults to `$1`.
- `NOTIFIER` - one of the available notification clients (the name of a `*.php` script in the `.deploy/notification` subdirectory) , defaults to `$2`.

## Process

Within the `.deploy/$ACTION/` you may consider creating two handlers:

  - `_succeeded.sh` - the file that will be included to a runtime once all commands in a process will successfully end.
  - `_failed.sh` - the same as above, but only after the first non-zero return code (a process will be terminated).

Available environment variables:

- `PLATFORM_BRANCH` - the name of a Git branch an environment exists for (the `unknown-at-build-stage` if the action is `build`).

*This script can be run locally if it doesn't rely on Platform.sh environment.*

### Specific environment handlers

The `build` hook executes in an isolation, therefore, an environment cannot be determined. The opposite situation for `deploy` and this gives a possibility to perform environment-specific actions. The following Bash scripts may be included (after non-specific) to a runtime context if exist:

- `.deploy/$ACTION/$PLATFORM_BRANCH/$ACTION.sh`
- `.deploy/$ACTION/$PLATFORM_BRANCH/_succeeded.sh`
- `.deploy/$ACTION/$PLATFORM_BRANCH/_failed.sh`

### Notifications

Independently on a status of one of the hooks, you might consider sending the notification.

#### Slack

Make sure your `.platform.app.yaml` has the following contents:

```yaml
variables:
  env:
    SLACK_WEBHOOK_URI: 'https://hooks.slack.com/services/ID1/ID2/ID3'
    SLACK_CHANNEL: '#my-slack-channel'
    SLACK_SENDER: 'My Slack channel'
```

#### Custom

- Create the `NAME.php` inside the `.deploy/notification`.
- Pass the `NAME` as a second argument to the `hook.sh`.
- The `NAME.php` will receive two arguments: `EXIT_CODE` and `ACTION`.

Example:

```yaml
hooks:
  # Notify with ".deploy/notification/NAME.php" about the "build".
  build: 'bash pfqaplatform_deploy/hook.sh build NAME'
  # Notify with ".deploy/notification/telegram.php" about the "deploy".
  deploy: 'bash pfqaplatform_deploy/hook.sh deploy telegram'
```
