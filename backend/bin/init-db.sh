#!/bin/bash

# GuildTracker Database Initialization Script
# This script initializes a fresh database with all necessary data

set -e  # Exit on error

echo "🎮 GuildTracker - Database Initialization"
echo "=========================================="
echo ""

# Check if ..env.local exists
if [ ! -f ..env.local ]; then
    echo "⚠️  Warning: .env.local not found"
    echo "Please create .env.local with your database configuration"
    echo ""
    echo "Example:"
    echo "DATABASE_URL=\"postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8\""
    echo ""
    exit 1
fi

# Parse command line arguments
DROP_DB=false
SKIP_WOW=false
SKIP_REALMS=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --drop)
            DROP_DB=true
            shift
            ;;
        --skip-wow)
            SKIP_WOW=true
            shift
            ;;
        --skip-realms)
            SKIP_REALMS=true
            shift
            ;;
        --help)
            echo "Usage: ./bin/init-db.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --drop         Drop existing database before initialization (⚠️  DESTRUCTIVE)"
            echo "  --skip-wow     Skip WoW raids/bosses data import"
            echo "  --skip-realms  Skip realm metadata population"
            echo "  --help         Show this help message"
            echo ""
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Build command arguments
CMD_ARGS=""
if [ "$DROP_DB" = true ]; then
    CMD_ARGS="$CMD_ARGS --drop"
fi
if [ "$SKIP_WOW" = true ]; then
    CMD_ARGS="$CMD_ARGS --skip-wow-data"
fi
if [ "$SKIP_REALMS" = true ]; then
    CMD_ARGS="$CMD_ARGS --skip-realms"
fi

# Run the initialization command
echo "🚀 Starting database initialization..."
echo ""

php bin/console app:init-database $CMD_ARGS

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo ""
    echo "✅ Database initialization completed successfully!"
    echo ""
    echo "Next steps:"
    echo "  1. Start the backend: symfony server:start"
    echo "  2. Start the frontend: cd ../frontend && npm run dev"
    echo ""
else
    echo ""
    echo "❌ Database initialization failed with code $EXIT_CODE"
    echo ""
    exit $EXIT_CODE
fi
