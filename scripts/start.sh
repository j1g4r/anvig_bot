#!/bin/bash

# ANVIG_BOT Unified Startup Script
# Starts Web Server, Queue Worker, Reverb (WebSockets), and Vite (Frontend)

# Function to handle shutdown
cleanup() {
    echo "Stopping all processes..."
    kill -TERM "$PID_SERVE" 2>/dev/null
    kill -TERM "$PID_QUEUE" 2>/dev/null
    kill -TERM "$PID_REVERB" 2>/dev/null
    kill -TERM "$PID_VITE" 2>/dev/null
    kill -TERM "$PID_SERVE" 2>/dev/null
    exit 0
}

# Trap SIGINT (Ctrl+C) and SIGTERM
trap cleanup SIGINT SIGTERM

echo "🚀 Starting ANVIG_BOT System..."

# 1. Start Laravel Web Server
echo "Starting Laravel Server (Port 8000)..."
php artisan serve --port=8000 &
PID_SERVE=$!

# 2. Start Queue Worker (The Brain)
echo "Starting Queue Worker..."
php artisan queue:listen --tries=1 --timeout=0 &
PID_QUEUE=$!

# 3. Start Reverb (WebSockets)
echo "Starting Reverb (Visual Nervous System)..."
php artisan reverb:start &
PID_REVERB=$!

# 4. Start Vite (Frontend)
echo "Starting Frontend (Vite)..."
npm run dev -- --host &
PID_VITE=$!

# 5. Start serve
echo "Starting Reverb (Visual Nervous System)..."
php artisan serve --host=0.0.0.0 &
PID_SERVE=$!

echo "✅ All systems executed."
echo "   - Web: http://localhost:8000"
echo "   - PIDs: Server($PID_SERVE), Queue($PID_QUEUE), Reverb($PID_REVERB), Vite($PID_VITE)"
echo "Press Ctrl+C to stop all."

# Wait for any process to exit
wait $PID_SERVE $PID_QUEUE $PID_REVERB $PID_VITE
