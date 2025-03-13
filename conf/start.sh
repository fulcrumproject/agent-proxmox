#!/bin/sh

# Create database directory if it doesn't exist
mkdir -p ../app/database

# Create SQLite database file if it doesn't exist
if [ ! -f ../app/database/agent.sqlite ]; then
    touch ../app/database/agent.sqlite
    chmod 666 ../app/database/agent.sqlite
    echo "Database file created."
fi

# Run migrations
if [ -f ../app/migrate.php ]; then
    echo "Running database migrations..."
    php ../app/migrate.php
fi

# Start supervisord
exec /usr/bin/supervisord -n -c /etc/supervisord.conf