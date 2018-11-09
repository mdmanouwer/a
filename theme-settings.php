<?php
// $Id$

/**
 * @file
 * Handles the custom theme settings
 */

/**
 * Return the theme settings' default values from the .info and save them into the database.
 * Credit: Zen http://drupal.org/project/zen
 *
 * @param $theme
 *   The name of theme.
 */
function buho_theme_get_default_settings($theme) {
 $themes = list_themes();

 // Get the default values from the .info file.
 $defaults = !empty($themes[$theme]->info['settings']) ? $themes[$theme]->info['settings'] : array();

 if (!empty($defaults)) {
   // Merge the defaults with the theme settings saved in the database.
   $settings = array_merge($defaults, variable_get('theme_'. $theme .'_settings', array()));
   // Save the settings back to the database.
   variable_set('theme_'. $theme .'_settings', $settings);
   // If the active theme has been loaded, force refresh of Drupal internals.
   if (!empty($GLOBALS['theme_key'])) {
     theme_get_setting('', TRUE);
   }
 }

 // Return the default settings.
 return $defaults;
}

/**
 * Implementation of _settings() theme function.
 *
 * @return array
 */
function buho_settings($saved_settings) {

  // Get the default settings.
  $defaults = buho_theme_get_default_settings('buho');
  // Merge the variables and their default values
  $settings = array_merge($defaults, $saved_settings);

  // Fonts
  $form['fonts'] = array(
    '#type' => 'fieldset',
    '#title' => 'Font Options',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  // Base Font Size
  $form['fonts']['buho_font_size'] = array(
    '#type' => 'select',
    '#title' => t('Base Font Size'),
    '#default_value' => $settings['buho_font_size'],
    '#options' => buho_size_range(11, 16, 'px', 12),
    '#description' => t('Select the base font size for the theme.'),
  );

  // Base Font
  $form['fonts']['buho_font'] = array(
    '#type' => 'select',
    '#title' => t('Base Font'),
    '#default_value' =>  $settings['buho_font'],
    '#options' => buho_font_list(),
    '#description' => t('Select the base font for the theme.'),
  );

  // Headings Font
  $form['fonts']['buho_font_headings'] = array(
    '#type' => 'select',
    '#title' => t('Headings Font'),
    '#default_value' =>  $settings['buho_font_headings'],
    '#options' => buho_font_list(),
    '#description' => t('Select the base font for the heading (block, page titles and heading tags).'),
  );

  // Generate custom.css and display a link to the file
  $form['buho_css'] = array(
    '#type' => 'fieldset',
    '#title' => 'Custom CSS Generation',
    '#description' =>  buho_write_css(), // This is the function that creates the custom.css file is created... Do not remove.
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  return $form;
}

function buho_build_css() {
  // Grab the current theme settings
  $theme_settings = variable_get('theme_buho_settings', '');
  if (!empty($theme_settings)) {
    // Build an array of only the theme related settings
    $setting = array();
    foreach ($theme_settings as $key => $value) {
      if (strpos($key, 'buho_') !== FALSE) {
        $setting[$key] = $value;
      }
    }
    // Handle custom settings for each case
    $custom_css = array();
    foreach ($setting as $key => $value) {
      switch ($key) {
        // Fonts
        case 'buho_font_size':
          $custom_css[] = (!empty($value)) ? '#wrapper { font-size: '. $value .'; }' : '';
          break;
        case 'buho_font':
        $custom_css[] = 'html, body, .form-radio, .form-checkbox, .form-file, .form-select, select, .form-text, input, .form-textarea, textarea  { font-family: '. buho_font_stack($value) .'; }';
          break;
        case 'buho_font_headings':
          $custom_css[] = 'h1, h2, h3, h4, h5, h6  { font-family: '. buho_font_stack($value) .'; }';
          break;
      }
    }
    return implode("\r\n", $custom_css);
    }
}

function buho_write_css() {
  // Set the location of the custom.css file
  $file_path = file_directory_path() .'/buho/custom.css';

  // If the directory doesn't exist, create it
  file_check_directory(dirname($file_path), FILE_CREATE_DIRECTORY);

  // Generate the CSS
  $file_contents = buho_build_css();
  $output = '<div class="description">'. t('This CSS is generated by the settings chosen above and placed in the files directory: '. l($file_path, $file_path) .'. The file is generated each time this page (and only this page) is loaded. <strong class="marker">Make sure to refresh your page to see the changes</strong>') .'</div>';

  file_save_data($file_contents, $file_path, FILE_EXISTS_REPLACE);
  drupal_flush_all_caches();

  return $output;

}

/**
 * Helper function to provide a list of fonts for select list in theme settings.
 */
function buho_font_list() {
  $fonts = array(
    'Sans-serif' => array(
      'verdana' => t('Verdana'),
      'helvetica' => t('Helvetica, Arial'),
      'lucida' => t('Lucida Grande, Lucida Sans Unicode'),
      'geneva' => t('Geneva'),
      'tahoma' => t('Tahoma'),
      'century' => t('Century Gothic'),
    ),
    'Serif' => array(
      'georgia' => t('Georgia'),
      'palatino' => t('Palatino Linotype, Book Antiqua'),
      'times' => t('Times New Roman'),
    ),
  );
  return $fonts;
}

/**
 * Provides Font Stack values for theme settings which are written to custom.css
 * @see buho_font_list()
 * @param $attributes
 * @return string
 */
function buho_font_stack($font) {
  if ($font) {
    $fonts = array(
      'verdana' => '"Bitstream Vera Sans", Verdana, Arial, sans-serif',
      'helvetica' => 'Helvetica, Arial, "Nimbus Sans L", "Liberation Sans", "FreeSans", sans-serif',
      'lucida' => '"Lucida Grande", "Lucida Sans", "Lucida Sans Unicode", "DejaVu Sans", Arial, sans-serif',
      'geneva' => '"Geneva", "Bitstream Vera Serif", "Tahoma", sans-serif',
      'tahoma' => 'Tahoma, Geneva, "DejaVu Sans Condensed", sans-serif',
      'century' => '"Century Gothic", "URW Gothic L", Helvetica, Arial, sans-serif',
      'georgia' => 'Georgia, "Bitstream Vera Serif", serif',
      'palatino' => '"Palatino Linotype", "URW Palladio L", "Book Antiqua", "Palatino", serif',
      'times' => '"Free Serif", "Times New Roman", Times, serif',
    );

    foreach ($fonts as $key => $value) {
      if ($font == $key) {
        $output = $value;
      }
    }
  }
  return $output;
}

/**
 * Helper function to provide a list of sizes for use in theme settings.
 */
function buho_size_range($start = 11, $end = 16, $unit = 'px', $default = NULL) {
  $range = '';
  if (is_numeric($start) && is_numeric($end)) {
    $range = array();
    $size = $start;
    while ($size >= $start && $size <= $end) {
      if ($size == $default) {
        $range[$size . $unit] = $size . $unit .' (default)';
      }
      else {
        $range[$size . $unit] = $size . $unit;
      }
      $size++;
    }
  }
  return $range;
}
