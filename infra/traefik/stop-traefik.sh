#!/bin/bash

# GuildTracker - Traefik Stop Script
# Stops all running Traefik processes

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Stopping Traefik...${NC}"

# Find Traefik processes
TRAEFIK_PIDS=$(ps aux | grep -i traefik | grep -v grep | grep -v stop-traefik | awk '{print $2}')

if [ -z "$TRAEFIK_PIDS" ]; then
    echo -e "${GREEN}No Traefik processes found running.${NC}"
    exit 0
fi

echo -e "${YELLOW}Found Traefik processes: $TRAEFIK_PIDS${NC}"

# Kill the processes
sudo kill $TRAEFIK_PIDS 2>/dev/null

# Wait a bit and check if they're still running
sleep 1
STILL_RUNNING=$(ps aux | grep -i traefik | grep -v grep | grep -v stop-traefik | awk '{print $2}')

if [ -n "$STILL_RUNNING" ]; then
    echo -e "${YELLOW}Processes still running, forcing kill...${NC}"
    sudo kill -9 $STILL_RUNNING 2>/dev/null
fi

echo -e "${GREEN}Traefik stopped successfully.${NC}"
