#!/bin/bash

# Script to analyze and remove unnecessary comments
# This script will show what comments exist in the 3 modules

echo "=== Analyzing Comments in Auth, Enrollments, Schemes Modules ==="
echo ""

echo "1. DocBlock comments (/** */)"
grep -rn --include="*.php" "^\s*/\*\*" Modules/{Auth,Enrollments,Schemes}/app/Services/ | wc -l

echo ""
echo "2. Single-line comments (//)"
grep -rn --include="*.php" "^\s*//" Modules/{Auth,Enrollments,Schemes}/app/Services/ | wc -l

echo ""
echo "3. Sample DocBlocks found:"
grep -rn --include="*.php" -A3 "^\s*/\*\*" Modules/{Auth,Enrollments,Schemes}/app/Services/ | head -30

echo ""
echo "4. Sample inline comments found:"
grep -rn --include="*.php" "^\s*//" Modules/{Auth,Enrollments,Schemes}/app/Services/ | head -20
