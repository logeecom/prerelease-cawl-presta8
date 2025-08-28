#!/bin/bash

# Step 1: Install php-parallel-lint/php-parallel-lint as a dev dependency
if ! composer show --locked | grep -q "^php-parallel-lint/php-parallel-lint"; then
  composer require --dev php-parallel-lint/php-parallel-lint
fi

# Step 2: Run php parallel lint analysis on src folder
echo -e "\nRunning PHP Parallel Lint..."
report="php_parallel_lint-report.txt"
./vendor/bin/parallel-lint ./src >"$report"
php_parallel_lint_exit_code=$?

cat "$report"

# Check if the report contains issues
if [ $php_parallel_lint_exit_code -eq 0 ]; then
  echo "PHP Parallel Lint found no issues."
  rm -f "php_parallel_lint-report.txt"
else
  echo "PHP Parallel Lint found issues in your code."
  exit 1  # Set the exit status to indicate failure
fi