#!/bin/bash

echo "🚀 Deploying ANVIG_BOT..."

# 1. Pull latest code
echo "📦 Pulling latest code..."
git pull

# 2. Build Container
echo "🏗️  Building Docker Image..."
docker compose build --no-cache

# 3. Start Services
echo "🔥 Starting Services..."
docker compose up -d

# 4. Cleanup
echo "🧹 Pruning unused images..."
docker image prune -f

echo "✅ Deployment Complete! App running at http://localhost:8000"
