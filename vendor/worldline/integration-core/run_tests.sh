#!/bin/bash

# Define color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Run PHPUnit
./build/run_phpunit.sh
phpunit_result=$?

# Step 2: Run PHPStan
./build/run_phpstan.sh
phpstan_result=$?

# Step 3: Run PHP Parallel Lint
./build/run_php_parallel_lint.sh
php_parallel_lint_result=$?

# Step 4: Run PHP CS Fixer
./build/run_php_cs_fixer.sh
php_cs_fixer_result=$?

echo -e "\nTest Summary:"
if [ $phpunit_result -eq 0 ]; then
  echo -e "PHPUnit           ---> ${GREEN}No issues found.${NC}"
else
  echo -e "PHPUnit           ---> ${RED}Issues found.${NC}"
fi

if [ $phpstan_result -eq 0 ]; then
  echo -e "PHPStan           ---> ${GREEN}No issues found.${NC}"
else
  echo -e "PHPStan           ---> ${RED}Issues found.${NC}"
fi

if [ $php_parallel_lint_result -eq 0 ]; then
  echo -e "PHP Parallel Lint ---> ${GREEN}No issues found.${NC}"
else
  echo -e "PHP Parallel Lint ---> ${RED}Issues found.${NC}"
fi

if [ $php_cs_fixer_result -eq 0 ]; then
  echo -e "PHP CS Fixer      ---> ${GREEN}No issues found.${NC}"
else
  echo -e "PHP CS Fixer      ---> ${RED}Issues found.${NC}"
fi