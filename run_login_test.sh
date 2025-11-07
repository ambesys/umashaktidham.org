#!/bin/bash

# Simple test runner for manual login testing
# Starts PHP server and runs Selenium test

echo "🚀 Starting Manual Login Test Runner"
echo "===================================="

# Set environment
export APP_ENV=development

# Start PHP server in background
echo "📡 Starting PHP development server..."
php -S localhost:8000 > server.log 2>&1 &
SERVER_PID=$!

# Wait for server to start
echo "⏳ Waiting for server to be ready..."
sleep 3

# Check if server is running
if kill -0 $SERVER_PID 2>/dev/null; then
    echo "✅ Server started successfully (PID: $SERVER_PID)"

    # Run the Selenium test
    echo "🧪 Running Selenium login test..."
    source .venv/bin/activate && python tests/SeleniumTestSignUpLogin.py

    # Kill server
    echo "🛑 Stopping server..."
    kill $SERVER_PID
    echo "✅ Test completed"
else
    echo "❌ Server failed to start"
    exit 1
fi