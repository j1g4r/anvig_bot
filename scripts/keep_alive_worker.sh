#!/bin/bash

# Define the command to run
# Using queue:work with --stop-when-empty is often used in deployments,
# but here we want a daemon that restarts on crash.
# We add --tries=3 to give jobs a chance, and --timeout=0 to allow long LLM inference.

echo "Starting Resilient Queue Worker..."
echo "Press [CTRL+C] to stop."

# Function to start a worker
start_worker() {
    while true; do
        echo "[$(date)] [Worker $1] Starting..."
        php artisan queue:work --tries=3 --timeout=0 --sleep=1
        
        EXIT_CODE=$?
        echo "[$(date)] [Worker $1] Exited with code $EXIT_CODE. Restarting in 1 second..."
        sleep 1
    done
}

# Trap to kill all child processes on exit
trap 'kill $(jobs -p)' EXIT

echo "Starting Concurrent Queue Workers (x5)..."

# Start 5 concurrent workers in background
for i in {1..5}; do
    start_worker $i &
done

# Wait for all background processes
wait
