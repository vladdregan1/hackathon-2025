#!/bin/bash

# Default database file
DB_FILE=${1:-db.sqlite}

# Check if SQLite is installed
if ! command -v sqlite3 &> /dev/null; then
    echo "Error: SQLite3 is not installed."
    exit 1
fi

# Check if the database file exists
if [ ! -f "$DB_FILE" ]; then
    echo "Database file '$DB_FILE' does not exist. Creating it..."
    sqlite3 "$DB_FILE" "VACUUM;"
fi

echo "Applying migrations to database: $DB_FILE"

# Iterate over all migration_*.sql files in the current directory
for sql_file in migration_*.sql; do
    if [ -f "$sql_file" ]; then
        echo "Applying migration: $sql_file..."
        sqlite3 "$DB_FILE" < "$sql_file"
        if [ $? -eq 0 ]; then
            echo "Successfully applied $sql_file"
        else
            echo "Error applying $sql_file"
        fi
    else
        echo "No migration files found."
        break
    fi
done

echo "Migration process completed."