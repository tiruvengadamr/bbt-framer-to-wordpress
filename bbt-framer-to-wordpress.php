<?php
/**
 * Plugin Name: BBT Framer to WordPress
 * Description: Import blog posts from a CSV file into WordPress. Supports featured image download from URL, custom field mapping, slug‑based duplicate skipping, and batch processing with AJAX. Tracks total, imported, skipped and failed rows with progress feedback. Developed by Bytes Brothers.
 * Version: 2.0
 * Author: Bytes Brothers
 * Author URI: https://bytesbrothers.com
 * License: GPL-2.0-or-later
 * Text Domain: bbt-framer-to-wordpress
 * Domain Path: /languages
 *
 * This plugin is an evolution of the original CSV Post Importer and has been rebranded for Bytes Brothers. It is intended to be open source
 * under the GPL and may be redistributed and/or modified under the same license.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'BBT_Framer_Importer' ) ) {
    /**
     * Main plugin class.
     */
    class BBT_Framer_Importer {
        /**
         * Menu slug for the admin page.
         *
         * @var string
         */
        private $menu_slug = 'bbt-framer-to-wordpress';

        /**
         * Number of CSV rows to process per AJAX request.
         *
         * @var int
         */
        private $batch_size = 10;

        /**
         * Option key used to store import state between requests.
         *
         * @var string
         */
        private $state_option = '_bbt_framer_importer_state';

        /**
         * Constructor. Hooks into WordPress actions.
         */
        public function __construct() {
            // Load text domain for translations.
            add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
            // Register admin menu.
            add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
            // Enqueue scripts and styles.
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            // AJAX handlers.
            add_action( 'wp_ajax_bbt_framer_prepare', array( $this, 'ajax_prepare_import' ) );
            add_action( 'wp_ajax_bbt_framer_process_batch', array( $this, 'ajax_process_batch' ) );
            add_action( 'wp_ajax_bbt_framer_retry_failed', array( $this, 'ajax_retry_failed' ) );
        }

        /**
         * Load the plugin text domain.
         */
        public function load_textdomain() {
            load_plugin_textdomain( 'bbt-framer-to-wordpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        /**
         * Register the submenu page under Tools.
         */
        public function register_menu_page() {
            add_submenu_page(
                'tools.php',
                __( 'BBT Framer Importer', 'bbt-framer-to-wordpress' ),
                __( 'BBT Framer Importer', 'bbt-framer-to-wordpress' ),
                'manage_options',
                $this->menu_slug,
                array( $this, 'render_admin_page' )
            );
        }

        /**
         * Enqueue JavaScript and CSS for the admin page.
         *
         * @param string $hook Current admin page hook suffix.
         */
        public function enqueue_assets( $hook ) {
            if ( 'tools_page_' . $this->menu_slug !== $hook ) {
                return;
            }
            // Register and enqueue JS.
            wp_register_script(
                'bbt-framer-importer-js',
                plugins_url( 'framer-importer.js', __FILE__ ),
                array( 'jquery' ),
                '2.0',
                true
            );
            wp_localize_script(
                'bbt-framer-importer-js',
                'BBTFramerImporter',
                array(
                    'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                    'nonce'         => wp_create_nonce( 'bbt_framer_importer_nonce' ),
                    'batchSize'     => $this->batch_size,
                    'headersLabel'  => __( 'CSV Column', 'bbt-framer-to-wordpress' ),
                    'mapToLabel'    => __( 'Map To', 'bbt-framer-to-wordpress' ),
                    'metaKeyLabel'  => __( 'Custom Field Key', 'bbt-framer-to-wordpress' ),
                    'errorNoFile'   => __( 'Please select a CSV file.', 'bbt-framer-to-wordpress' ),
                    'errorNoMapping' => __( 'Please map at least one column.', 'bbt-framer-to-wordpress' ),
                )
            );
            wp_enqueue_script( 'bbt-framer-importer-js' );
            // Register and enqueue CSS.
            wp_register_style(
                'bbt-framer-importer-css',
                plugins_url( 'framer-importer.css', __FILE__ ),
                array(),
                '2.0'
            );
            wp_enqueue_style( 'bbt-framer-importer-css' );
        }

        /**
         * Render the admin page UI.
         */
        public function render_admin_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __( 'You do not have sufficient permissions to access this page.', 'bbt-framer-to-wordpress' ) );
            }
            ?>
            <div class="wrap bbt-framer-importer">
                <h1><?php esc_html_e( 'BBT Framer to WordPress Importer', 'bbt-framer-to-wordpress' ); ?></h1>
                <p><?php esc_html_e( 'Import blog posts from a CSV file created with BBT Framer into WordPress. Map columns to post fields, download featured images, store alt text and additional images, and skip existing posts by slug.', 'bbt-framer-to-wordpress' ); ?></p>
                <!-- Branding note -->
                <p><em><?php echo wp_kses_post( sprintf( __( 'Plugin by %1$s', 'bbt-framer-to-wordpress' ), '<a href="https://bytesbrothers.com" target="_blank">Bytes Brothers</a>' ) ); ?></em></p>
                <!-- Step 1: File upload -->
                <div id="bbt-import-step1" class="bbt-import-step">
                    <h2><?php esc_html_e( 'Step 1: Upload CSV', 'bbt-framer-to-wordpress' ); ?></h2>
                    <form id="bbt-upload-form" method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'bbt_framer_upload', 'bbt_framer_upload_nonce' ); ?>
                        <input type="file" id="bbt_csv_file" name="csv_file" accept=".csv" required />
                        <p class="description">
                            <?php esc_html_e( 'Choose a CSV file from your computer. The first row should contain column headers.', 'bbt-framer-to-wordpress' ); ?>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary" id="bbt-upload-button">
                                <?php esc_html_e( 'Upload & Continue', 'bbt-framer-to-wordpress' ); ?>
                            </button>
                        </p>
                    </form>
                </div>
                <!-- Step 2: Column mapping -->
                <div id="bbt-import-step2" class="bbt-import-step" style="display:none;">
                    <h2><?php esc_html_e( 'Step 2: Map Columns', 'bbt-framer-to-wordpress' ); ?></h2>
                    <form id="bbt-map-form">
                        <input type="hidden" name="token" id="bbt_map_token" value="" />
                        <!-- Mapping table will be inserted via JS -->
                        <p>
                            <label>
                                <input type="checkbox" id="bbt-skip-duplicates" name="skip_duplicates" value="1" />
                                <?php esc_html_e( 'Skip posts with duplicate slugs', 'bbt-framer-to-wordpress' ); ?>
                            </label>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary" id="bbt-map-button">
                                <?php esc_html_e( 'Start Import', 'bbt-framer-to-wordpress' ); ?>
                            </button>
                        </p>
                    </form>
                </div>
                <!-- Step 3: Import progress -->
                <div id="bbt-import-step3" class="bbt-import-step" style="display:none;">
                    <h2><?php esc_html_e( 'Step 3: Importing…', 'bbt-framer-to-wordpress' ); ?></h2>
                    <div id="bbt-import-progress"></div>
                    <div id="bbt-import-results"></div>
                    <p id="bbt-import-retry-container" style="display:none;">
                        <button class="button" id="bbt-import-retry">
                            <?php esc_html_e( 'Retry Failed Rows', 'bbt-framer-to-wordpress' ); ?>
                        </button>
                    </p>
                </div>
            </div>
            <?php
        }

        /**
         * AJAX callback: prepare import. Handles file upload, stores file, returns headers and mapping options.
         */
        public function ajax_prepare_import() {
            check_ajax_referer( 'bbt_framer_importer_nonce', 'nonce' );
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( __( 'You do not have permission to perform this action.', 'bbt-framer-to-wordpress' ), 403 );
            }
            if ( empty( $_FILES['csv_file'] ) || ! isset( $_FILES['csv_file']['tmp_name'] ) ) {
                wp_send_json_error( __( 'No file uploaded.', 'bbt-framer-to-wordpress' ) );
            }
            $file = $_FILES['csv_file'];
            $file_ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
            if ( strtolower( $file_ext ) !== 'csv' ) {
                wp_send_json_error( __( 'Please upload a CSV file.', 'bbt-framer-to-wordpress' ) );
            }
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );
            if ( isset( $uploaded['error'] ) ) {
                wp_send_json_error( $uploaded['error'] );
            }
            $attachment_id = wp_insert_attachment( array(
                'post_mime_type' => 'text/csv',
                'post_title'     => sanitize_file_name( $file['name'] ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ), $uploaded['file'] );
            if ( ! is_wp_error( $attachment_id ) ) {
                wp_update_attachment_metadata( $attachment_id, array() );
            }
            $handle = fopen( $uploaded['file'], 'r' );
            if ( ! $handle ) {
                wp_send_json_error( __( 'Failed to open uploaded file.', 'bbt-framer-to-wordpress' ) );
            }
            $headers = fgetcsv( $handle );
            fclose( $handle );
            if ( empty( $headers ) ) {
                wp_send_json_error( __( 'The uploaded CSV appears to be empty or corrupted.', 'bbt-framer-to-wordpress' ) );
            }
            $headers = array_map( function( $h ) {
                return sanitize_text_field( trim( $h ) );
            }, $headers );
            $token = wp_generate_password( 12, false, false );
            $state = array(
                'file'        => $uploaded['file'],
                'attachment'  => $attachment_id,
                'headers'     => $headers,
                'mapping'     => array(),
                'skip_dupes'  => false,
                'total'       => 0,
                'imported'    => 0,
                'skipped'     => 0,
                'failed'      => 0,
                'failed_rows' => array(),
                'current'     => 0
            );
            $all_states = get_option( $this->state_option, array() );
            $all_states[ $token ] = $state;
            update_option( $this->state_option, $all_states, false );
            // Field options.
            $fields = array(
                'post_title'   => __( 'Post Title', 'bbt-framer-to-wordpress' ),
                'post_content' => __( 'Post Content', 'bbt-framer-to-wordpress' ),
                'post_excerpt' => __( 'Post Excerpt', 'bbt-framer-to-wordpress' ),
                'post_name'    => __( 'Slug', 'bbt-framer-to-wordpress' ),
                'post_date'    => __( 'Post Date', 'bbt-framer-to-wordpress' ),
                'image_url'    => __( 'Featured Image URL', 'bbt-framer-to-wordpress' ),
                'image_alt'    => __( 'Featured Image Alt Text', 'bbt-framer-to-wordpress' ),
                'additional_images' => __( 'Additional Image URLs (comma separated)', 'bbt-framer-to-wordpress' ),
                'custom_field' => __( 'Custom Field (specify key)', 'bbt-framer-to-wordpress' )
            );
            wp_send_json_success( array(
                'token'   => $token,
                'headers' => $headers,
                'fields'  => $fields
            ) );
        }

        /**
         * AJAX callback: process batch.
         */
        public function ajax_process_batch() {
            check_ajax_referer( 'bbt_framer_importer_nonce', 'nonce' );
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( __( 'You do not have permission to perform this action.', 'bbt-framer-to-wordpress' ), 403 );
            }
            $token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
            $mapping = isset( $_POST['mapping'] ) && is_array( $_POST['mapping'] ) ? $_POST['mapping'] : array();
            $meta_keys = isset( $_POST['meta_keys'] ) && is_array( $_POST['meta_keys'] ) ? $_POST['meta_keys'] : array();
            $skip_duplicates = ! empty( $_POST['skip_duplicates'] );
            $retry = ! empty( $_POST['retry'] );
            $all_states = get_option( $this->state_option, array() );
            if ( empty( $all_states[ $token ] ) ) {
                wp_send_json_error( __( 'Invalid import session.', 'bbt-framer-to-wordpress' ) );
            }
            $state = $all_states[ $token ];
            if ( empty( $state['mapping'] ) ) {
                $valid_mapping = array();
                foreach ( $state['headers'] as $header ) {
                    if ( ! isset( $mapping[ $header ] ) ) {
                        continue;
                    }
                    $type = sanitize_text_field( $mapping[ $header ] );
                    $allowed = array( 'post_title', 'post_content', 'post_excerpt', 'post_name', 'post_date', 'image_url', 'image_alt', 'additional_images', 'custom_field' );
                    if ( ! in_array( $type, $allowed, true ) ) {
                        continue;
                    }
                    $valid_mapping[ $header ] = $type;
                }
                $state['mapping'] = $valid_mapping;
                $state['meta_keys'] = array();
                if ( ! empty( $meta_keys ) ) {
                    foreach ( $meta_keys as $header => $key ) {
                        $state['meta_keys'][ $header ] = sanitize_key( $key );
                    }
                }
                $state['skip_dupes'] = $skip_duplicates;
                $state['total'] = $this->count_rows( $state['file'] );
            }
            $rows_to_process = array();
            if ( $retry ) {
                $rows_to_process = $state['failed_rows'];
                $state['failed_rows'] = array();
            } else {
                for ( $i = 0; $i < $this->batch_size; $i++ ) {
                    $row_index = $state['current'] + $i;
                    if ( $row_index < $state['total'] ) {
                        $rows_to_process[] = $row_index;
                    }
                }
                $state['current'] += count( $rows_to_process );
            }
            if ( empty( $rows_to_process ) ) {
                $all_states[ $token ] = $state;
                update_option( $this->state_option, $all_states, false );
                wp_send_json_success( array(
                    'done'      => true,
                    'imported'  => $state['imported'],
                    'skipped'   => $state['skipped'],
                    'failed'    => $state['failed'],
                    'total'     => $state['total'],
                    'retryable' => ! empty( $state['failed_rows'] )
                ) );
            }
            $handle = fopen( $state['file'], 'r' );
            if ( ! $handle ) {
                wp_send_json_error( __( 'Unable to open CSV file.', 'bbt-framer-to-wordpress' ) );
            }
            fgetcsv( $handle );
            $current_index = 0;
            $processed = 0;
            $failed_rows_in_batch = array();
            while ( ( $data = fgetcsv( $handle ) ) !== false ) {
                if ( $processed >= count( $rows_to_process ) ) {
                    break;
                }
                if ( ! in_array( $current_index, $rows_to_process, true ) ) {
                    $current_index++;
                    continue;
                }
                $row = array();
                foreach ( $state['headers'] as $idx => $header ) {
                    $row[ $header ] = isset( $data[ $idx ] ) ? $data[ $idx ] : '';
                }
                try {
                    $this->import_row( $row, $state );
                    $state['imported']++;
                } catch ( Exception $e ) {
                    $state['failed']++;
                    $failed_rows_in_batch[] = $current_index;
                }
                $processed++;
                $current_index++;
            }
            fclose( $handle );
            $state['failed_rows'] = array_merge( $state['failed_rows'], $failed_rows_in_batch );
            $all_states[ $token ] = $state;
            update_option( $this->state_option, $all_states, false );
            $done = ( $state['current'] >= $state['total'] ) && empty( $state['failed_rows'] );
            wp_send_json_success( array(
                'done'      => $done,
                'imported'  => $state['imported'],
                'skipped'   => $state['skipped'],
                'failed'    => $state['failed'],
                'total'     => $state['total'],
                'retryable' => ! empty( $state['failed_rows'] )
            ) );
        }

        /**
         * AJAX callback: retry failed rows.
         */
        public function ajax_retry_failed() {
            $_POST['retry'] = 1;
            $this->ajax_process_batch();
        }

        /**
         * Count rows excluding header.
         */
        private function count_rows( $file_path ) {
            $count = 0;
            if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
                fgetcsv( $handle );
                while ( fgetcsv( $handle ) !== false ) {
                    $count++;
                }
                fclose( $handle );
            }
            return $count;
        }

        /**
         * Import a single row.
         *
         * @param array $row   Row data.
         * @param array $state State array passed by reference.
         * @throws Exception When failure occurs.
         */
        private function import_row( $row, &$state ) {
            $post_data = array(
                'post_type'   => 'post',
                'post_status' => 'publish',
            );
            $meta_input = array();
            $image_url = '';
            $image_alt = '';
            $additional_images = '';
            foreach ( $state['mapping'] as $header => $type ) {
                $value = isset( $row[ $header ] ) ? $row[ $header ] : '';
                switch ( $type ) {
                    case 'post_title':
                        $post_data['post_title'] = wp_kses_post( $value );
                        break;
                    case 'post_content':
                        $post_data['post_content'] = wp_kses_post( $value );
                        break;
                    case 'post_excerpt':
                        $post_data['post_excerpt'] = wp_kses_post( $value );
                        break;
                    case 'post_name':
                        $post_data['post_name'] = sanitize_title( $value );
                        break;
                    case 'post_date':
                        $post_data['post_date'] = $value;
                        $post_data['post_date_gmt'] = get_gmt_from_date( $value );
                        break;
                    case 'image_url':
                        $image_url = esc_url_raw( $value );
                        break;
                    case 'image_alt':
                        $image_alt = sanitize_text_field( $value );
                        break;
                    case 'additional_images':
                        $additional_images = sanitize_text_field( $value );
                        break;
                    case 'custom_field':
                        if ( isset( $state['meta_keys'][ $header ] ) && ! empty( $state['meta_keys'][ $header ] ) ) {
                            $meta_key = $state['meta_keys'][ $header ];
                            $meta_input[ $meta_key ] = maybe_unserialize( $value );
                        }
                        break;
                    default:
                        break;
                }
            }
            // Duplicate slug check.
            if ( ! empty( $state['skip_dupes'] ) ) {
                $slug_to_check = '';
                if ( ! empty( $post_data['post_name'] ) ) {
                    $slug_to_check = $post_data['post_name'];
                } elseif ( ! empty( $post_data['post_title'] ) ) {
                    $slug_to_check = sanitize_title( $post_data['post_title'] );
                }
                if ( ! empty( $slug_to_check ) ) {
                    global $wpdb;
                    $existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s LIMIT 1", $slug_to_check, 'post' ) );
                    if ( $existing_id ) {
                        $state['skipped']++;
                        return;
                    }
                }
            }
            // Insert post.
            $post_id = wp_insert_post( $post_data, true );
            if ( is_wp_error( $post_id ) ) {
                throw new Exception( $post_id->get_error_message() );
            }
            foreach ( $meta_input as $key => $value ) {
                update_post_meta( $post_id, $key, $value );
            }
            if ( ! empty( $additional_images ) ) {
                update_post_meta( $post_id, 'additional_images', $additional_images );
            }
            if ( ! empty( $image_url ) ) {
                $attachment_id = $this->sideload_image( $image_url, $post_id, $image_alt );
                if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
                    set_post_thumbnail( $post_id, $attachment_id );
                } else {
                    throw new Exception( 'Failed to download featured image.' );
                }
            }
        }

        /**
         * Download and attach image.
         *
         * @param string $url
         * @param int    $post_id
         * @param string $alt_text
         * @return int|WP_Error
         */
        private function sideload_image( $url, $post_id, $alt_text = '' ) {
            
            // Validate URL
            if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
                return new WP_Error( 'invalid_url', 'Invalid URL provided for image download' );
            }
            
            if ( ! function_exists( 'media_sideload_image' ) ) {
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }
            
            $tmp = download_url( $url );
            
            if ( is_wp_error( $tmp ) ) {
                return $tmp;
            }
            
            // Check if temp file exists and get file info
            if ( ! file_exists( $tmp ) ) {
                return new WP_Error( 'temp_file_missing', 'Downloaded temp file is missing');
            }
            
            $file_size = filesize( $tmp );
            
            if ( $file_size === 0 ) {
                @unlink( $tmp );
                return new WP_Error( 'empty_file', 'Downloaded file is empty' );
            }
            
            $file_array = array();
            $file_array['name'] = basename( parse_url( $url, PHP_URL_PATH ) );
            $file_array['tmp_name'] = $tmp;
            
            // Check if filename is valid
            if ( empty( $file_array['name'] ) ) {
                $file_array['name'] = 'image_' . time() . '.jpg'; // Fallback name
            }
            
            $attachment_id = 0;
            try {
                $attachment_id = media_handle_sideload( $file_array, $post_id );
                
                if ( is_wp_error( $attachment_id ) ) {
                    @unlink( $tmp );
                    return $attachment_id;
                }
                
                // Set alt text if provided
                if ( ! empty( $alt_text ) ) {
                    update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt_text ) );
                }
                
            } catch ( Exception $e ) {
                @unlink( $tmp );
                return new WP_Error( 'sideload_error', $e->getMessage() );
            }
            
            return $attachment_id;
        }
    }
    new BBT_Framer_Importer();
}