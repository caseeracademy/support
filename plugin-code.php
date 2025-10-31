<?php
/**
 * Plugin Name:       Caseer Academy API Gateway
 * Plugin URI:        https://caseer.academy
 * Description:       Provides a custom REST API and webhooks for the Caseer Academy app.
 * Version:           1.5.1
 * Author:            Cabdiraxmaan Caseer
 * Author URI:        https://caseer.academy
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       caseer-api-gateway
 */
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// --- IMPORTANT SECURITY ---
// Change this key to a long, random, and secret string.
define('MY_APP_SECRET_KEY', 'C@533r3c');

/**
 * Main class for the Caseer Academy API Gateway.
 */
class Caseer_Academy_API_Gateway
{
    /**
     * Constructor. Hooks into WordPress actions.
     */
    public function __construct()
    {
        // REST API Routes
        add_action('rest_api_init', [$this, 'register_routes']);

        // Admin Settings Page
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        // Webhook Triggers
        add_action('woocommerce_order_status_changed', [$this, 'trigger_order_status_webhook'], 10, 4);
        add_action('woocommerce_new_order', [$this, 'trigger_new_order_webhook'], 10, 2);
    }

    // --- Admin Settings Page ---

    /**
     * Add admin menu page for webhook settings.
     */
    public function add_admin_menu()
    {
        add_options_page(
            'Caseer API Gateway Settings',
            'Caseer API Gateway',
            'manage_options',
            'caseer-api-gateway',
            [$this, 'create_settings_page']
        );
    }

    /**
     * Register the settings for the webhook URL.
     */
    public function register_settings()
    {
        register_setting('caseer_api_gateway_options', 'caseer_api_webhook_url', 'esc_url_raw');
    }

    /**
     * Create the HTML for the settings page.
     */
    public function create_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>Caseer API Gateway Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('caseer_api_gateway_options');
        do_settings_sections('caseer-api-gateway-options');
        ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Order Status Webhook URL</th>
                        <td><input type="url" name="caseer_api_webhook_url" value="<?php echo esc_attr(get_option('caseer_api_webhook_url')); ?>" class="regular-text" placeholder="https://yourapi.com/webhook-receiver"/>
                        <p class="description">Enter the URL to which order status updates should be sent.</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // --- Webhook Functionality ---

    /**
     * Wrapper for the 'new order' hook to call the main webhook function.
     */
    public function trigger_new_order_webhook($order_id, $order)
    {
        $this->trigger_order_status_webhook($order_id, 'new', $order->get_status(), $order);
    }

    /**
     * Triggered when a WooCommerce order status changes OR a new order is created.
     */
    public function trigger_order_status_webhook($order_id, $old_status, $new_status, $order)
    {
        $webhook_url = get_option('caseer_api_webhook_url');

        if (empty($webhook_url)) {
            return; // No URL set, so we do nothing.
        }

        $customer = $order->get_user();
        $items_data = [];
        foreach ($order->get_items() as $item) {
            $items_data[] = ['name' => $item->get_name(), 'quantity' => $item->get_quantity()];
        }

        $payload = [
            'event' => $old_status === 'new' ? 'order_created' : 'order_status_changed',
            'order_id' => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'customer' => [
                'id' => $order->get_customer_id(),
                'email' => $order->get_billing_email(),
                'name' => $order->get_billing_first_name().' '.$order->get_billing_last_name(),
                'billing_phone' => $order->get_billing_phone(),
            ],
            'items' => $items_data,
        ];

        $args = [
            'body' => json_encode($payload),
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'timeout' => 45,
            'redirection' => 5,
            'blocking' => false, // Don't wait for the response to avoid slowing down the checkout.
            'sslverify' => true,
        ];

        wp_remote_post($webhook_url, $args);
    }

    // --- REST API Functionality ---

    /**
     * Register all custom API endpoints for the app.
     */
    public function register_routes()
    {
        $namespace = 'my-app/v1';

        register_rest_route($namespace, '/user/create', ['methods' => 'POST', 'callback' => [$this, 'create_user'], 'permission_callback' => [$this, 'check_secret_key_permission']]);
        register_rest_route($namespace, '/admin/latest-students', ['methods' => 'GET', 'callback' => [$this, 'get_latest_students'], 'permission_callback' => [$this, 'check_secret_key_permission']]);
        register_rest_route($namespace, '/admin/reset-password', ['methods' => 'POST', 'callback' => [$this, 'admin_reset_password'], 'permission_callback' => [$this, 'check_secret_key_permission']]);
        register_rest_route($namespace, '/admin/search-users', ['methods' => 'GET', 'callback' => [$this, 'admin_search_users'], 'permission_callback' => [$this, 'check_secret_key_permission']]);
        register_rest_route($namespace, '/admin/student/(?P<id>\d+)', ['methods' => 'GET', 'callback' => [$this, 'admin_get_student_details'], 'permission_callback' => [$this, 'check_secret_key_permission']]);
        register_rest_route($namespace, '/courses', ['methods' => 'GET', 'callback' => [$this, 'get_all_courses'], 'permission_callback' => '__return_true']);
        register_rest_route($namespace, '/my-courses', ['methods' => 'GET', 'callback' => [$this, 'get_my_courses'], 'permission_callback' => [$this, 'user_is_logged_in']]);
        register_rest_route($namespace, '/course/(?P<id>\d+)', ['methods' => 'GET', 'callback' => [$this, 'get_course_details'], 'permission_callback' => [$this, 'user_is_logged_in']]);
        register_rest_route($namespace, '/lesson/(?P<id>\d+)', ['methods' => 'GET', 'callback' => [$this, 'get_lesson_details'], 'permission_callback' => [$this, 'user_is_logged_in']]);
        register_rest_route($namespace, '/user/profile', ['methods' => 'GET', 'callback' => [$this, 'get_user_profile'], 'permission_callback' => [$this, 'user_is_logged_in']]);
        register_rest_route($namespace, '/initiate-payment', ['methods' => 'POST', 'callback' => [$this, 'initiate_payment'], 'permission_callback' => [$this, 'user_is_logged_in']]);
    }

    public function check_secret_key_permission(WP_REST_Request $request)
    {
        $sent_key = $request->get_header('X-Secret-Key');

        return ! empty($sent_key) && hash_equals(MY_APP_SECRET_KEY, $sent_key);
    }

    public function user_is_logged_in(WP_REST_Request $request)
    {
        return get_current_user_id() > 0;
    }

    public function create_user(WP_REST_Request $request)
    {
        $params = $request->get_json_params();

        $username = sanitize_user($params['username'] ?? '', true);
        $first_name = sanitize_text_field($params['first_name'] ?? '');
        $last_name = sanitize_text_field($params['last_name'] ?? '');
        $email = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Username, email, and password are required fields.'], 400);
        }

        if (username_exists($username)) {
            return new WP_REST_Response(['success' => false, 'message' => 'This username is already taken.'], 409);
        }

        if (! is_email($email)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Invalid email format provided.'], 400);
        }

        if (email_exists($email)) {
            return new WP_REST_Response(['success' => false, 'message' => 'An account with this email address already exists.'], 409);
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return new WP_REST_Response(['success' => false, 'message' => $user_id->get_error_message()], 500);
        }

        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name.' '.$last_name,
        ]);

        $student_role = 'customer';
        if (get_role($student_role)) {
            $user = new WP_User($user_id);
            $user->set_role($student_role);
        }

        return new WP_REST_Response(['success' => true, 'message' => 'User created successfully. You can now log in.', 'user_id' => $user_id], 201);
    }

    public function get_latest_students(WP_REST_Request $request)
    {
        $student_role = 'customer';

        if (! get_role($student_role)) {
            return new WP_REST_Response(['success' => false, 'message' => 'The WordPress "customer" role does not exist on this site.'], 500);
        }

        $args = [
            'role' => $student_role,
            'number' => 10,
            'orderby' => 'user_registered',
            'order' => 'DESC',
        ];

        $user_query = new WP_User_Query($args);
        $students = $user_query->get_results();
        $students_data = [];

        if (! empty($students)) {
            foreach ($students as $student) {
                $students_data[] = [
                    'id' => $student->ID,
                    'display_name' => $student->display_name,
                    'email' => $student->user_email,
                    'registration_date' => $student->user_registered,
                ];
            }
        }

        return new WP_REST_Response($students_data, 200);
    }

    public function admin_reset_password(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $email = sanitize_email($params['email'] ?? '');
        $new_password = $params['new_password'] ?? '';

        if (empty($email) || empty($new_password)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Email and new_password are required fields.'], 400);
        }

        $user = get_user_by('email', $email);

        if (! $user) {
            return new WP_REST_Response(['success' => false, 'message' => 'No user found with that email address.'], 404);
        }

        wp_set_password($new_password, $user->ID);

        return new WP_REST_Response(['success' => true, 'message' => 'Password for '.$email.' has been updated successfully.'], 200);
    }

    public function admin_search_users(WP_REST_Request $request)
    {
        $term = sanitize_text_field($request->get_param('term'));

        if (empty($term)) {
            return new WP_REST_Response(['success' => false, 'message' => 'A search term is required.'], 400);
        }

        $student_role = 'customer';
        if (! get_role($student_role)) {
            return new WP_REST_Response(['success' => false, 'message' => 'The WordPress "customer" role does not exist on this site.'], 500);
        }

        $args = [
            'role' => $student_role,
            'search' => '*'.esc_attr($term).'*',
            'search_columns' => ['user_login', 'user_nicename', 'user_email', 'display_name'],
        ];

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        $users_data = [];

        if (! empty($users)) {
            foreach ($users as $user) {
                $users_data[] = [
                    'id' => $user->ID,
                    'display_name' => $user->display_name,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                ];
            }
        }

        return new WP_REST_Response($users_data, 200);
    }

    public function admin_get_student_details(WP_REST_Request $request)
    {
        if (! function_exists('wc_get_orders') || ! function_exists('tutor_utils')) {
            return new WP_REST_Response(['message' => 'WooCommerce or Tutor LMS is not active.'], 500);
        }

        $user_id = (int) $request['id'];
        $user_data = get_userdata($user_id);

        if (! $user_data || ! in_array('customer', $user_data->roles)) {
            return new WP_REST_Response(['message' => 'Student not found.'], 404);
        }

        $enrolled_courses_query = tutor_utils()->get_enrolled_courses_by_user($user_id);
        $enrolled_courses_data = [];
        if ($enrolled_courses_query && $enrolled_courses_query->have_posts()) {
            while ($enrolled_courses_query->have_posts()) {
                $enrolled_courses_query->the_post();
                $course_id = get_the_ID();
                $enrolled_courses_data[] = ['id' => $course_id, 'title' => get_the_title(), 'thumbnail_url' => get_the_post_thumbnail_url($course_id, 'medium_large')];
            }
            wp_reset_postdata();
        }

        $orders = wc_get_orders(['customer_id' => $user_id, 'limit' => -1]);
        $order_history = [];
        foreach ($orders as $order) {
            $items = [];
            foreach ($order->get_items() as $item) {
                $items[] = ['name' => $item->get_name()];
            }
            $order_history[] = ['order_id' => $order->get_id(), 'date' => $order->get_date_created()->date('Y-m-d'), 'total' => $order->get_total(), 'status' => $order->get_status(), 'items' => $items];
        }

        $response_data = [
            'id' => $user_data->ID,
            'username' => $user_data->user_login,
            'display_name' => $user_data->display_name,
            'email' => $user_data->user_email,
            'registration_date' => $user_data->user_registered,
            'enrolled_courses' => $enrolled_courses_data,
            'order_history' => $order_history,
        ];

        return new WP_REST_Response($response_data, 200);
    }

    public function get_all_courses()
    {
        if (! function_exists('tutor_utils') || ! function_exists('wc_get_product')) {
            return new WP_REST_Response(['message' => 'Required plugins (Tutor LMS, WooCommerce) are not active.'], 500);
        }
        $args = ['post_type' => 'courses', 'post_status' => 'publish', 'posts_per_page' => -1];
        $courses_query = new WP_Query($args);
        $courses_data = [];
        if ($courses_query->have_posts()) {
            while ($courses_query->have_posts()) {
                $courses_query->the_post();
                $course_id = get_the_ID();
                $product_id = get_post_meta($course_id, '_tutor_course_product_id', true);
                $product = $product_id ? wc_get_product($product_id) : null;
                $courses_data[] = [
                    'id' => $course_id,
                    'title' => get_the_title(),
                    'thumbnail_url' => get_the_post_thumbnail_url($course_id, 'medium_large'),
                    'price' => $product ? $product->get_price() : '0.00',
                ];
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response($courses_data, 200);
    }

    public function get_my_courses()
    {
        if (! function_exists('tutor_utils')) {
            return new WP_REST_Response(['message' => 'Tutor LMS is not active.'], 500);
        }
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            return new WP_REST_Response(['message' => 'User not authenticated.'], 401);
        }
        $enrolled_courses = tutor_utils()->get_enrolled_courses_by_user($user_id);
        $courses_data = [];
        if ($enrolled_courses && $enrolled_courses->have_posts()) {
            while ($enrolled_courses->have_posts()) {
                $enrolled_courses->the_post();
                $course_id = get_the_ID();
                $progress = tutor_utils()->get_course_completed_percent($course_id, $user_id);
                $courses_data[] = [
                    'id' => $course_id,
                    'title' => get_the_title(),
                    'thumbnail_url' => get_the_post_thumbnail_url($course_id, 'medium_large'),
                    'progress_percent' => round($progress),
                ];
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response($courses_data, 200);
    }

    public function get_course_details(WP_REST_Request $request)
    {
        if (! function_exists('tutor_utils')) {
            return new WP_REST_Response(['message' => 'Tutor LMS is not active.'], 500);
        }
        $course_id = (int) $request['id'];
        $user_id = get_current_user_id();
        if (! tutor_utils()->is_enrolled($course_id, $user_id)) {
            return new WP_REST_Response(['message' => 'You are not enrolled in this course.'], 403);
        }
        $course = get_post($course_id);
        if (! $course) {
            return new WP_REST_Response(['message' => 'Course not found.'], 404);
        }
        $topics = tutor_utils()->get_topics($course_id);
        $topics_data = [];
        if ($topics->have_posts()) {
            while ($topics->have_posts()) {
                $topics->the_post();
                $topic_id = get_the_ID();
                $lessons_data = [];
                $quizzes_data = [];
                $contents = tutor_utils()->get_course_contents_by_topic($topic_id, -1);
                if ($contents->have_posts()) {
                    while ($contents->have_posts()) {
                        $contents->the_post();
                        if (get_post_type() === 'tutor_quiz') {
                            $quizzes_data[] = ['id' => get_the_ID(), 'title' => get_the_title()];
                        } else {
                            $lessons_data[] = ['id' => get_the_ID(), 'title' => get_the_title()];
                        }
                    }
                }
                $topics_data[] = ['id' => $topic_id, 'title' => get_the_title(), 'lessons' => $lessons_data, 'quizzes' => $quizzes_data];
            }
            wp_reset_postdata();
        }
        $response_data = [
            'id' => $course_id,
            'title' => $course->post_title,
            'full_content' => apply_filters('the_content', $course->post_content),
            'topics' => $topics_data,
        ];

        return new WP_REST_Response($response_data, 200);
    }

    public function get_lesson_details(WP_REST_Request $request)
    {
        if (! function_exists('tutor_utils')) {
            return new WP_REST_Response(['message' => 'Tutor LMS is not active.'], 500);
        }
        $lesson_id = (int) $request['id'];
        $user_id = get_current_user_id();
        $course_id = tutor_utils()->get_course_id_by_subcontent($lesson_id);
        if (! $course_id || ! tutor_utils()->is_enrolled($course_id, $user_id)) {
            return new WP_REST_Response(['message' => 'You do not have access to this lesson.'], 403);
        }
        $lesson = get_post($lesson_id);
        if (! $lesson || $lesson->post_type !== 'lesson') {
            return new WP_REST_Response(['message' => 'Lesson not found.'], 404);
        }
        $video_info = tutor_utils()->get_video_info($lesson_id);
        $response_data = [
            'id' => $lesson_id,
            'title' => $lesson->post_title,
            'content' => apply_filters('the_content', $lesson->post_content),
            'video' => $video_info ? $video_info : null,
        ];

        return new WP_REST_Response($response_data, 200);
    }

    public function get_user_profile()
    {
        if (! function_exists('wc_get_orders') || ! function_exists('tutor_utils')) {
            return new WP_REST_Response(['message' => 'WooCommerce or Tutor LMS is not active.'], 500);
        }
        $user_id = get_current_user_id();
        $user_data = get_userdata($user_id);
        if (! $user_data) {
            return new WP_REST_Response(['message' => 'User not found.'], 404);
        }
        $enrolled_courses_query = tutor_utils()->get_enrolled_courses_by_user($user_id);
        $enrolled_courses_data = [];
        if ($enrolled_courses_query && $enrolled_courses_query->have_posts()) {
            while ($enrolled_courses_query->have_posts()) {
                $enrolled_courses_query->the_post();
                $course_id = get_the_ID();
                $enrolled_courses_data[] = ['id' => $course_id, 'title' => get_the_title(), 'thumbnail_url' => get_the_post_thumbnail_url($course_id, 'medium_large')];
            }
            wp_reset_postdata();
        }
        $orders = wc_get_orders(['customer_id' => $user_id, 'limit' => -1]);
        $order_history = [];
        foreach ($orders as $order) {
            $items = [];
            foreach ($order->get_items() as $item) {
                $items[] = ['name' => $item->get_name()];
            }
            $order_history[] = ['order_id' => $order->get_id(), 'date' => $order->get_date_created()->date('Y-m-d'), 'total' => $order->get_total(), 'status' => $order->get_status(), 'items' => $items];
        }
        $response_data = [
            'display_name' => $user_data->display_name,
            'email' => $user_data->user_email,
            'enrolled_courses' => $enrolled_courses_data,
            'order_history' => $order_history,
        ];

        return new WP_REST_Response($response_data, 200);
    }

    public function initiate_payment(WP_REST_Request $request)
    {
        if (! function_exists('wc_create_order') || ! function_exists('tutor_utils')) {
            return new WP_REST_Response(['success' => false, 'message' => 'WooCommerce or Tutor LMS is not active.', 'error_code' => 'plugin_inactive'], 500);
        }

        $params = $request->get_json_params();
        $course_id = isset($params['course_id']) ? (int) $params['course_id'] : 0;
        $phone_number_raw = isset($params['payment_phone_number']) ? sanitize_text_field($params['payment_phone_number']) : '';

        if (empty($course_id) || empty($phone_number_raw)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Course ID and phone number are required.', 'error_code' => 'missing_params'], 400);
        }
        if (! preg_match('/^(6|9)[0-9]{8}$/', $phone_number_raw)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Invalid phone number format.', 'error_code' => 'invalid_phone'], 400);
        }

        $user_id = get_current_user_id();
        if (tutor_utils()->is_enrolled($course_id, $user_id)) {
            return new WP_REST_Response(['success' => false, 'message' => 'You are already enrolled in this course.', 'error_code' => 'already_enrolled'], 400);
        }

        $product_id = get_post_meta($course_id, '_tutor_course_product_id', true);

        if (empty($product_id)) {
            return new WP_REST_Response(['success' => false, 'message' => 'This course is not available for purchase.', 'error_code' => 'product_not_found'], 404);
        }

        try {
            $order = wc_create_order(['customer_id' => $user_id]);
            $product = wc_get_product($product_id);
            $order->add_product($product, 1);
            $order->set_address(get_user_meta($user_id, 'billing', true), 'billing');
            $order->calculate_totals();
            $order_id = $order->get_id();

            $waafi_gateway_settings = get_option('woocommerce_waafi_payment_settings');
            if (empty($waafi_gateway_settings)) {
                throw new Exception('Waafi payment gateway settings are not configured.');
            }

            $phone_number = '252'.$phone_number_raw;
            $request_body = [
                'schemaVersion' => '1.1', 'requestId' => 'R'.time().$order_id, 'timestamp' => gmdate('Y-m-d H:i:s'), 'channelName' => 'WEB', 'serviceName' => 'API_PURCHASE',
                'serviceParams' => [
                    'merchantUid' => $waafi_gateway_settings['merchant_uid'], 'apiUserId' => $waafi_gateway_settings['api_user_id'], 'apiKey' => $waafi_gateway_settings['api_key'],
                    'paymentMethod' => 'MWALLET_ACCOUNT', 'payerInfo' => ['accountNo' => $phone_number],
                    'transactionInfo' => ['referenceId' => 'ORD-'.$order_id.'-'.wp_rand(1000, 9999), 'invoiceId' => 'INV-'.$order_id, 'amount' => (string) round($order->get_total(), 2), 'currency' => 'USD', 'description' => 'Payment for Order #'.$order_id],
                ],
            ];

            $response = wp_remote_post($waafi_gateway_settings['api_endpoint_url'], [
                'method' => 'POST', 'timeout' => 90, 'headers' => ['Content-Type' => 'application/json; charset=utf-8'], 'body' => json_encode($request_body), 'sslverify' => true,
            ]);

            if (is_wp_error($response)) {
                throw new Exception('Connection Error: '.$response->get_error_message());
            }

            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            if (isset($response_data['responseCode']) && $response_data['responseCode'] == '2001') {
                $transaction_id = $response_data['params']['transactionId'] ?? 'N/A';
                $order->payment_complete($transaction_id);
                $order->add_order_note('Waafi payment successful via API. Transaction ID: '.esc_html($transaction_id));
                $order->save();

                return new WP_REST_Response(['success' => true, 'message' => 'Payment successful. You are now enrolled.', 'order_id' => $order_id], 200);
            } else {
                $error_msg = $response_data['responseMsg'] ?? 'Unknown error occurred.';
                $error_code = $response_data['responseCode'] ?? 'N/A';
                throw new Exception(sprintf('%s (Code: %s)', esc_html($error_msg), esc_html($error_code)));
            }

        } catch (Exception $e) {
            if (isset($order) && $order) {
                $order->update_status('failed', 'API Payment Error: '.$e->getMessage());
            }

            return new WP_REST_Response(['success' => false, 'message' => $e->getMessage(), 'error_code' => 'payment_failed'], 400);
        }
    }
}

/**
 * Begins execution of the plugin.
 */
function caseer_api_gateway_run()
{
    new Caseer_Academy_API_Gateway;
}
add_action('plugins_loaded', 'caseer_api_gateway_run');
