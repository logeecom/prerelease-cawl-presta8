#!/bin/bash

# Step 1: Install phpstan/phpstan as a dev dependency
if ! composer show --locked | grep -q "^phpstan/phpstan"; then
  composer require --dev phpstan/phpstan
fi

# Step 2: Run PHPStan tests
echo -e "\nRunning PHPStan analysis..."
report="phpstan-report.txt"
./vendor/bin/phpstan analyse -l 7 ./src >"$report"
phpstan_exit_code=$?

cat "$report"

# Check if the report contains issues
if [ $phpstan_exit_code -eq 0 ]; then
  echo "PHPStan found no issues."
  rm -f "phpstan-report.txt"
else
  echo "PHPStan found issues in your code."
  exit 1  # Set the exit status to indicate failure
fi