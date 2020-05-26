<?php
/**
 * Plugin Name: WooDonation
 * Description: This plugin is responsible for setting donation on product
 * Author: Hasib Muhammad<hasibmu99@gmail.com>
 * Author URI: https://github.com/hasibmuhammad
 * Version: 1.0
 * Text Domain: woo-donation
 */

if( ! defined( 'ABSPATH' ) ) die;

if ( ! function_exists( 'is_woocommerce_activated' ) ) {

	function is_woocommerce_activated() {

        if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
        
	}
}

// -----------------------------------------
// 1. Add custom field input @ Product Data > Variations > Single Variation
function wd_variation_options_pricing( $loop, $variation_data, $variation ) {

    woocommerce_wp_text_input([

        'id' => 'woo_donation_field['. $loop .']',

        'class' => 'short',

        'label' => __( 'Donation amount', 'woo-donation' ),

        'value' => get_post_meta( $variation->ID, 'woo_donation_field', true )

    ]);
}
add_action( 'woocommerce_variation_options_pricing', 'wd_variation_options_pricing', 10, 3 );

// -----------------------------------------
// 2. Save custom field on product variation save
function wd_save_donation_field_variations( $variation_id, $i ) {

    $wd_donation_field = $_POST['woo_donation_field'][$i];

    if( isset( $wd_donation_field ) ) {

        update_post_meta( $variation_id, 'woo_donation_field', sanitize_text_field( $wd_donation_field ) );

    }

}
add_action( 'woocommerce_save_product_variation', 'wd_save_donation_field_variations', 10, 2 );

// -----------------------------------------
// 3. Store custom field value into variation data
function wd_add_donation_field_data( $variations ) {

    $variations['woo_donation_field'] = '

        <div class="woocommerce_woo_donation_field">

            Donation Amount: 

            <span>'. get_post_meta( $variations['variation_id'], 'woo_donation_field', true ) .'</span>

        </div>';

    return $variations;
}
add_filter( 'woocommerce_available_variation', 'wd_add_donation_field_data' );

function wd_managing_donation_during_purchase( $order_id ) {
    
    $order = wc_get_order( $order_id );
    
    $items = $order->get_items();
    
    foreach( $items as $item_id => $item_data ) {
        
        $quantity = $item_data->get_quantity();
        
        $product = $item_data->get_product();

        $variation_id = $product->get_id();

        $current_product_donation_amount = get_post_meta( $variation_id, 'woo_donation_field', true ) * $quantity;

        $current_stored_total_donation_amount = get_option( '__woo_total_donation_amount_' );

        $total_donation = $current_product_donation_amount + $current_stored_total_donation_amount;

        update_option( '__woo_total_donation_amount_', $total_donation );

        $current_stored_total_product_price = get_option( '__woo_total_sold_price' );

        $current_product_price = $product->get_price() * $quantity;

        $total_sold_price = $current_stored_total_product_price + $current_product_price;

        update_option( '__woo_total_sold_price', $total_sold_price );
    }
}
add_action( 'woocommerce_thankyou', 'wd_managing_donation_during_purchase' );

function wd_woo_donation_amount_shortcode() {

    $total_donation = get_option( '__woo_total_donation_amount_' );

    if( $total_donation == '' ) $total_donation = 0;

    $donation_amount_text = __( 'Total Donation : ', 'woo-donation' );

    $html = <<<EOD

<div class="woo-donation-amount">
    <h4>
        $donation_amount_text $$total_donation
    </h4>
</div>

EOD;
        return $html;

}
add_shortcode( 'woo-donation', 'wd_woo_donation_amount_shortcode' );

function wd_woo_total_sells_shortcode() {

    $total_sold_price = get_option( '__woo_total_sold_price' );

    if( $total_sold_price == '' ) $total_sold_price = 0;

    $total_sold_amount_text = __( 'Total Sell : ', 'woo-donation' );

    $html = <<<EOD

<div class="woo-total-amount">
    <h4>
        $total_sold_amount_text $$total_sold_price
    </h4>
</div>

EOD;
        return $html;

}
add_shortcode( 'woo-total-sell', 'wd_woo_total_sells_shortcode' );