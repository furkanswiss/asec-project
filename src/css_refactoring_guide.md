# CSS Refactoring Guide for ASEC Project

## Problem
The project has multiple CSS conflicts due to:
1. CSS files being loaded multiple times
2. Many separate CSS files with overlapping styles
3. No clear CSS architecture or organization

## Solution
We've implemented a CSS management system that:
1. Creates a centralized CSS loading system
2. Prevents duplicate CSS loading
3. Maintains proper CSS specificity order

## How to Implement on All Pages

### Step 1: Update Each PHP Page
For each PHP page in your project, update the `<head>` section to use the new CSS loader:

```php
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title</title>
    <?php 
    include_once 'css_loader.php';
    // Load only page-specific CSS files
    load_css([
        'page-specific.css',
        'another-page-specific.css'
    ]);
    ?>
</head>
<body>
    <?php include 'header.php'; ?>
    <!-- Rest of your page content -->
```

### Step 2: Remove Duplicate CSS Links
Make sure to remove any duplicate CSS links that appear after the header inclusion, like:
```php
<?php include 'header.php'; ?>
<link rel="stylesheet" href="css/some-file.css"> <!-- Remove these -->
```

### Step 3: Organize CSS Files
Consider consolidating similar CSS files. For example:
- Combine small related CSS files
- Move common styles to main.css
- Use a naming convention like BEM to avoid conflicts

### Step 4: Resolving Specific Conflicts
For any remaining CSS conflicts:
1. Use more specific selectors for important styles
2. Consider using `!important` sparingly for critical styles
3. Ensure styles are loaded in the correct order (general → specific)

## CSS Best Practices
1. Use a consistent naming convention
2. Group related styles together
3. Comment your CSS for better maintainability
4. Consider using CSS variables for common values
5. Minimize the use of !important

## Testing
After implementing these changes, test each page to ensure:
1. All styles are applied correctly
2. No visual regressions occur
3. Responsive design still works as expected
