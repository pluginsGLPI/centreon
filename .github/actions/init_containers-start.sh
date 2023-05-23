#!/bin/bash
set -e -u -x -o pipefail

docker-compose pull --quiet
docker-compose up --no-start
docker-compose start