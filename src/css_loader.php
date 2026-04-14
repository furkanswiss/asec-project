<?php
/**
 * CSS Loader System
 * This file manages CSS loading to prevent conflicts and duplications
 */

// Track loaded CSS files to prevent duplicates
if (!isset($GLOBALS['loaded_css_files'])) {
    $GLOBALS['loaded_css_files'] = [];
}

/**
 * Load CSS files in the correct order
 * @param array $page_specific_css Array of page-specific CSS files to load
 */
function load_css($page_specific_css = []) {
    // Base CSS files that should be loaded on every page
    $base_css = [
        'main.css', // Our new main CSS file that imports common styles
    ];
    
    // External CSS resources
    $external_css = [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'
    ];
    
    // Output external CSS first
    foreach ($external_css as $css) {
        if (!in_array($css, $GLOBALS['loaded_css_files'])) {
            echo '<link rel="stylesheet" href="' . $css . '">' . PHP_EOL;
            $GLOBALS['loaded_css_files'][] = $css;
        }
    }
    
    // Output base CSS
    foreach ($base_css as $css) {
        if (!in_array($css, $GLOBALS['loaded_css_files'])) {
            echo '<link rel="stylesheet" href="css/' . $css . '">' . PHP_EOL;
            $GLOBALS['loaded_css_files'][] = $css;
        }
    }
    
    // Output page-specific CSS
    foreach ($page_specific_css as $css) {
        if (!in_array($css, $GLOBALS['loaded_css_files'])) {
            echo '<link rel="stylesheet" href="css/' . $css . '">' . PHP_EOL;
            $GLOBALS['loaded_css_files'][] = $css;
        }
    }
}
?>
