#!/usr/bin/env bash

# ------------------------------------------------------------------------------
# Configuration.
# ------------------------------------------------------------------------------

# Ongoing action.
declare -r ACTION="$1"
# One of the available notification clients (the name of a "*.php" script in
# the "notifications" subdirectory).
declare -r NOTIFIER="$2"

# ------------------------------------------------------------------------------
# DO NOT EDIT BELOW.
# ------------------------------------------------------------------------------

set -eE

inform() {
  echo "[$(date --iso-8601=seconds)]" "$@"
}

if [[ ! "$ACTION" =~ ^(build|deploy)$ ]]; then
  inform "Invalid argument. It must be either \"build\" or \"deploy\"."
  exit 19
fi

cd "$(dirname "$0")/.."

# The path to a parent directory, relative to this file (literally, one catalog above).
PROJECT_DIR="$(pwd -P)"

if [ "build" == "$ACTION" ]; then
  : "${PLATFORM_BRANCH:="unknown-at-build-stage"}"
else
  : "${PLATFORM_BRANCH:="$(git rev-parse --abbrev-ref HEAD)"}"
fi

include() {
  for SUBDIR in "" "environment/$PLATFORM_BRANCH/"; do
    INCLUDE_FILE="$PROJECT_DIR/.deploy/$SUBDIR$ACTION/$1.sh"

    if [ -f "$INCLUDE_FILE" ]; then
      inform "--- include \"$INCLUDE_FILE\"."
      . "$INCLUDE_FILE"
    fi

    unset INCLUDE_FILE
  done
}

handle_shutdown() {
  local STATUS="$?"

  if [ "$STATUS" -eq 0 ]; then
    inform "${ACTION^} successfully finished."
    include "_succeeded"
  else
    inform "${ACTION^} failed."
    include "_failed"
  fi

  if [ -f "$PROJECT_DIR/notifications/$NOTIFIER.php" ]; then
    php "$PROJECT_DIR/notifications/$NOTIFIER.php" "$STATUS" "$ACTION"
    inform "The notification to \"${NOTIFIER^}\" has been sent."
  fi
}

trap handle_shutdown EXIT

inform "${ACTION^}ing..."
include "$ACTION"

inform "Printing the environment..."
env
