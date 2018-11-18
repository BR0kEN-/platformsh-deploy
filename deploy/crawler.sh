#!/usr/bin/env bash

# Clear Crawler's cache.
php console/console.php cache:clear
# Stop all workers and clear the queue.
bash console/manager.sh kill
# Start new workers.
bash console/manager.sh
