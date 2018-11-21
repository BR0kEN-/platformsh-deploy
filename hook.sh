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

SELF_DIR="$(cd "$(dirname "$0")" && pwd -P)"
cd "$SELF_DIR/.."
# The path to a parent directory, relative to this file (literally, one catalog above).
PROJECT_DIR="$(pwd -P)"

if [ "build" == "$ACTION" ]; then
  : "${PLATFORM_BRANCH:="unknown-at-build-stage"}"
else
  : "${PLATFORM_BRANCH:="$(git rev-parse --abbrev-ref HEAD)"}"
fi

include() {
  for SUBDIR in "" "/$PLATFORM_BRANCH/"; do
    INCLUDE_FILE="$PROJECT_DIR/.deploy/$ACTION$SUBDIR/$1.sh"

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

  for SUBDIR in "$PROJECT_DIR" "$SELF_DIR"; do
    INCLUDE_FILE="$SUBDIR/.deploy/notification/$NOTIFIER.php"

    if [ -f "$INCLUDE_FILE" ]; then
      php "$INCLUDE_FILE" "$STATUS" "$ACTION"
      inform "The notification to \"${NOTIFIER^}\" has been sent."
      break
    fi
  done
}

trap handle_shutdown EXIT

inform "${ACTION^}ing..."
include "$ACTION"

inform "Printing the environment..."
env
