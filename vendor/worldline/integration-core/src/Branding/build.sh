#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

for arg in "$@"; do
    case $arg in
        --brand=*)
            BRAND="${arg#*=}"
            ;;
        --inputPath=*)
            inputPath="${arg#*=}"
            ;;
        --outputPath=*)
            outputPath="${arg#*=}"
            ;;
        *)
            echo "Unknown option: $arg"
            exit 1
            ;;
    esac
done

if [ -z "$BRAND" ]; then
    echo "Building for all brands"
    yarn webpack
fi

echo "Building for brand: $BRAND"

yarn webpack --env brand="$BRAND" inputPath="$inputPath" outputPath="$outputPath"