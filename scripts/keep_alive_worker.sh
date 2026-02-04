#!/bin/bash

# Define the command to run
# Using queue:work with --stop-when-empty is often used in deployments,
# but here we want a daemon that restarts on crash.
# We add --tries=3 to give jobs a chance, and --timeout=0 to allow long LLM inference.

echo "Starting Resilient Queue Worker..."
echo "Press [CTRL+C] to stop."

while true; do
    echo "[$(date)] Starting worker..."
    php artisan queue:work --tries=3 --timeout=0
    
    EXIT_CODE=$?
    echo "[$(date)] Worker exited with code $EXIT_CODE. Restarting in 1 second..."
    sleep 1
done
