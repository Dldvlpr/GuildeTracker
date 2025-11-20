#!/bin/bash

# GuildTracker - Traefik Startup Script
# Loads environment variables and starts Traefik with proper configuration

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get the project root directory (two levels up from this script)
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

# Load environment variables from .env.local
ENV_FILE="$PROJECT_ROOT/.env.local"

if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}Error: .env.local file not found at $ENV_FILE${NC}"
    exit 1
fi

echo -e "${GREEN}Loading environment variables from .env.local...${NC}"
set -a
source "$ENV_FILE"
set +a

# Verify mkcert CA exists
if [ ! -f "$TRAEFIK_CA_ROOT" ]; then
    echo -e "${RED}Error: mkcert CA not found at $TRAEFIK_CA_ROOT${NC}"
    echo -e "${YELLOW}Run: mkcert -install${NC}"
    exit 1
fi

# Verify certificates exist
if [ ! -f "$TRAEFIK_TLS_CERT_FILE" ] || [ ! -f "$TRAEFIK_TLS_KEY_FILE" ]; then
    echo -e "${RED}Error: TLS certificates not found${NC}"
    echo -e "${YELLOW}Cert: $TRAEFIK_TLS_CERT_FILE${NC}"
    echo -e "${YELLOW}Key: $TRAEFIK_TLS_KEY_FILE${NC}"
    exit 1
fi

echo -e "${GREEN}Starting Traefik...${NC}"
echo -e "${YELLOW}Dashboard: http://localhost:8080${NC}"
echo -e "${YELLOW}Frontend: https://$TRAEFIK_FRONT_HOST${NC}"
echo -e "${YELLOW}API: https://$TRAEFIK_API_HOST${NC}"
echo ""

# Start Traefik with sudo to bind to ports 80 and 443
sudo -E traefik \
    --entrypoints.web.address=:80 \
    --entrypoints.websecure.address=:443 \
    --providers.file.directory="$PROJECT_ROOT/infra/traefik/dynamic" \
    --api.dashboard=true \
    --api.insecure=true \
    --serversTransport.rootCAs="$TRAEFIK_CA_ROOT"
