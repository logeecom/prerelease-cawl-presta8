#!/bin/bash

# Step 1: Install phpunit/phpunit as a dev dependency
if ! composer show --locked | grep -q "^phpunit/phpunit"; then
  composer require --dev phpunit/phpunit:^4.8
fi

# Step 2: Run PHPUnit tests
echo -e "\nRunning PHPUnit tests..."
report="phpunit-report.txt"
./vendor/bin/phpunit >"$report"
phpunit_exit_code=$?

cat "$report"

# Check if the report contains issues
if [ $phpunit_exit_code -eq 0 ]; then
  echo "All PHPUnit tests passed."
  rm -f "phpunit-report.txt"
else
  echo "Some PHPUnit tests have failed."
  exit 1  # Set the exit status to indicate failure
fi