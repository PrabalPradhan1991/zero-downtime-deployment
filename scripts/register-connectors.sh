#!/bin/sh

echo "Waiting for Kafka Connect to start..."
while true; do
  STATUS=$(curl -s -o /dev/null -w %{http_code} http://kafka-connect:8083/)
  if [ "$STATUS" = "200" ]; then
    echo "Kafka Connect is up!"
    break
  fi
  echo "Kafka Connect not ready (status: $STATUS), waiting 5 seconds..."
  sleep 5
done

echo "Registering connectors from /config directory..."
# Check if there are any json files to avoid errors
if ls /config/*.json 1> /dev/null 2>&1; then
  for f in /config/*.json; do
    if [ -e "$f" ]; then
      echo "Registering $f"
      curl -i -X POST -H "Accept:application/json" -H "Content-Type:application/json" http://kafka-connect:8083/connectors/ -d "@$f"
      echo ""
    fi
  done
else
  echo "No connector configurations found in /config directory."
fi

echo "Finished registering connectors."
