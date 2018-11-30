<?php
/*
Plugin Name: Tally Order
Description: A custom plugin built for mybalancedchef.com. 
Author: Kyle Tozer
Author URI: https://kyletozer.com/
Text Domain: tally-order
*/

register_activation_hook(__FILE__, function() {
  if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    wp_die(__('This plugin requires WooCommerce! Please install it if you would like to use this plugin.'), 'tally-order');
  }
});

// append the download button to the products edit.php page
add_action('manage_posts_extra_tablenav', function() {
  $screen = get_current_screen();

  if($screen->id !== 'edit-shop_order') {
    return;
  }

  ?>
    <div class="alignleft actions">
      <input type="button" id="get_order_list" class="button action" value="Get Order List">
    </div>
  <?php
});

add_action('admin_enqueue_scripts', function() {
  // contains the necessary js that will be used to trigger the GET request that will start the CSV creating process
  wp_register_script(
    'tally-order',
    plugins_url('/main.js', __FILE__),
    array('jquery'),
    null,
    true
  );

  wp_enqueue_script('tally-order');
});


add_action('load-edit.php', function() {
  // only perform action on the products post type edit.php page and only if the custom GET query value of download is present in the request URI
  if(!isset($_GET['download']) || (!isset($_GET['post_type']) && $_GET['post_type'] !== 'shop_order')) {
    return;
  }

	global $wpdb;

  // grab the quantity of each item in an order that is in a state of processing
  $sql = "SELECT items.order_item_name AS Item, COUNT(*) AS Quantity
  FROM {$wpdb->prefix}woocommerce_order_items AS items
  INNER JOIN {$wpdb->prefix}posts AS orders
  ON items.order_id = orders.ID
  WHERE orders.post_status = 'wc-processing'
  AND orders.post_type = 'shop_order'
  AND items.order_item_type = 'line_item'
  GROUP BY items.order_item_name;";

  // generate the CSV to be downloaded with the data fetched from the query
  $items = $wpdb->get_results($sql, ARRAY_A);
  $headings = array_keys($items[0]);
  $resource = fopen('php://output', 'w');

  fputcsv($resource, $headings);

  foreach($items as $item) {
    fputcsv($resource, array_values($item));
  }

  fclose($resource);

  // prompt the user to download the resulting CSV file
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename=order_quantities.csv');
  
  die;
});