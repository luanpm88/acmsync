<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppController extends Controller
{
    /**
     * Check if WooCommerce is active
     **/
    public function is_woocommerce_installed() {
        return in_array( 
            'woocommerce/woocommerce.php', 
            apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) 
        );
    }

    /**
     * Connect to Acelle.
     *
     * @return \Illuminate\Http\Response
     */
    public function connect(Request $request)
    {
        header("Access-Control-Allow-Origin: *");

        if (!$this->is_woocommerce_installed()) {
            return response('WooCommerce is not available in the target WordPress instance', 404)
                ->header('Content-Type', 'text/plain');
        }

        if ($request->product_id) {
            $post = \App\Model\Post::find($request->product_id);

            $product   = wc_get_product( $post->ID );
            $image_id  = $product->get_image_id();
            $image_url = wp_get_attachment_image_url( $image_id, 'full' );

            return response()->json([
                'id' => $post->ID,
                'name' => $post->post_title,
                'price' => wc_price($product->get_price()),
                'image' => $image_url,
                'description' => substr(strip_tags($post->post_content), 0, 100),
                'link' => get_permalink( $post->ID ),
            ]);
        }

        elseif ($request->action == 'shop_info') {
            $custom_logo_id = get_theme_mod( 'custom_logo' );
            $image = wp_get_attachment_image_src( $custom_logo_id , 'full' );

            return response()->json([
                'name' => get_bloginfo('name'),
                'url' => get_site_url(),
                'logo' => isset($image[0]) ? $image[0] : '',
                'products_count' => wp_count_posts( 'product' )->publish,
                'orders_count' => wc_orders_count('wc-completed'),
                'total_sales' => 0,
            ]);
        }

        if ($request->action == 'list') {
            // Fetch all products using WP_Query
            $args = [
                'post_type'      => 'product',
                'posts_per_page' => -1, // Fetch all products
                'post_status'    => 'publish', // Only published products
            ];

            $query = new \WP_Query($args);
            $products = [];

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();

                    // Get the product object
                    $product = wc_get_product(get_the_ID());
                    $image_url = wp_get_attachment_url($product->get_image_id());

                    // Map the product data
                    $products[] = [
                        'id'          => $product->get_id(),
                        'name'        => $product->get_name(),
                        'description' => $product->get_description(),
                        'price'       => wc_price($product->get_price()),
                        'image'   => $image_url,
                        'link'        => get_permalink($product->get_id()),
                        'checkout_url' => $this->getCheckoutLink($product->get_id()),
                    ];
                }
                wp_reset_postdata();
            }

            // Return as JSON response
            wp_send_json($products);
        }
        
        if ($request->action == 'cart') {
            return response()->json(\App\Model\WoocommerceSession::getAbandondedCarts());
        }

        return \App\Model\WcProductMetaLookup::select2($request);
    }

    public function getCheckoutLink($productId, $quantity = 1) {
        if (!class_exists('WooCommerce')) {
            return ''; // Ensure WooCommerce is active
        }
    
        // Generate the add-to-cart link
        $url = wc_get_checkout_url(); // Checkout page URL
    
        // Add product ID and quantity as query parameters
        $url = add_query_arg([
            'add-to-cart' => $productId,
            'quantity'    => $quantity,
        ], $url);
    
        return $url;
    }
}
