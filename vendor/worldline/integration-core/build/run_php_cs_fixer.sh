#!/bin/bash

# Step 1: Install friendsofphp/php-cs-fixer as a dev dependency
if ! composer show --locked | grep -q "^friendsofphp/php-cs-fixer"; then
  composer require --dev friendsofphp/php-cs-fixer
fi

# Step 2: Run php cs fixer analysis on src folder
echo -e "\nRunning PHP CS Fixer..."
report="php_cs_fixer-report.txt"
./vendor/bin/php-cs-fixer fix --rules=@PSR1,@PSR2 --dry-run --diff --verbose ./src >"$report"
php_cs_fixer_exit_code=$?

cat "$report"

# Check if the report contains issues
if [ $php_cs_fixer_exit_code -eq 0 ]; then
  echo "PHP CS Fixer found no issues."
  rm -f "php_cs_fixer-report.txt"
else
  echo "PHP CS Fixer found issues in your code."
  exit 1  # Set the exit status to indicate failure
fi