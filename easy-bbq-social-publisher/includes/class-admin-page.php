<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EBSP_Admin_Page {
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Easy BBQ Publisher',
            'Easy BBQ Publisher',
            'manage_options',
            'easy-bbq-publisher',
            array( $this, 'render_admin_page' ),
            'dashicons-share',
            30
        );

        add_submenu_page(
            'easy-bbq-publisher',
            'Gestion des Cartes & Plats',
            'Gestion des Cartes',
            'manage_options',
            'easy-bbq-presets',
            array( $this, 'render_presets_page' )
        );

        add_submenu_page(
            'easy-bbq-publisher',
            'Settings',
            'Settings',
            'manage_options',
            'easy-bbq-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( ! in_array( $hook, array('toplevel_page_easy-bbq-publisher', 'easy-bbq-publisher_page_easy-bbq-settings', 'easy-bbq-publisher_page_easy-bbq-presets') ) ) {
            return;
        }

        wp_enqueue_style( 'ebsp-admin-style', EBSP_PLUGIN_URL . 'assets/css/admin-style.css', array(), EBSP_PLUGIN_VERSION );

        if ( $hook === 'toplevel_page_easy-bbq-publisher' ) {
            wp_enqueue_script( 'ebsp-canvas-renderer', EBSP_PLUGIN_URL . 'assets/js/canvas-renderer.js', array(), EBSP_PLUGIN_VERSION, true );
            wp_enqueue_script( 'ebsp-admin-app', EBSP_PLUGIN_URL . 'assets/js/admin-app.js', array( 'ebsp-canvas-renderer' ), EBSP_PLUGIN_VERSION, true );

            wp_localize_script( 'ebsp-admin-app', 'ebspSettings', array(
                'restUrl' => esc_url_raw( rest_url( 'ebsp/v1/' ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
                'pluginUrl' => EBSP_PLUGIN_URL
            ) );
        }

        if ( $hook === 'easy-bbq-publisher_page_easy-bbq-presets' ) {
            wp_enqueue_script( 'ebsp-admin-presets', EBSP_PLUGIN_URL . 'assets/js/admin-presets.js', array(), EBSP_PLUGIN_VERSION, true );
            wp_localize_script( 'ebsp-admin-presets', 'ebspSettings', array(
                'restUrl' => esc_url_raw( rest_url( 'ebsp/v1/' ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' )
            ) );
        }
    }

    public function register_settings() {
        register_setting( 'ebsp_settings_group', 'ebsp_gemini_api_key', 'sanitize_text_field' );
        register_setting( 'ebsp_settings_group', 'ebsp_webhook_url', 'esc_url_raw' );
        register_setting( 'ebsp_settings_group', 'ebsp_default_hashtags', 'sanitize_textarea_field' );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Easy BBQ Publisher Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'ebsp_settings_group' ); ?>
                <?php do_settings_sections( 'ebsp_settings_group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Google AI Studio API Key</th>
                        <td><input type="text" name="ebsp_gemini_api_key" value="<?php echo esc_attr( get_option( 'ebsp_gemini_api_key' ) ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Outgoing Webhook URL</th>
                        <td><input type="url" name="ebsp_webhook_url" value="<?php echo esc_url( get_option( 'ebsp_webhook_url' ) ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Default Hashtags</th>
                        <td>
                            <textarea name="ebsp_default_hashtags" rows="4" cols="50"><?php echo esc_textarea( get_option( 'ebsp_default_hashtags', '#easybbq #food #menu' ) ); ?></textarea>
                            <p class="description">Enter default hashtags for Instagram/TikTok.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_presets_page() {
        ?>
        <div class="wrap ebsp-admin-wrap">
            <h1>Gestion des Cartes & Plats</h1>
            <p>Manage the preset lists for Starters, Main Courses, and Drinks.</p>

            <div class="ebsp-presets-layout">
                <div class="ebsp-preset-section" data-type="starters">
                    <h2>Entrées (Starters)</h2>
                    <ul class="ebsp-preset-list"></ul>
                    <div class="ebsp-preset-add">
                        <input type="text" placeholder="Nouvelle entrée">
                        <button type="button" class="button btn-add-preset">+ Ajouter</button>
                    </div>
                </div>

                <div class="ebsp-preset-section" data-type="mains">
                    <h2>Plats (Main Courses)</h2>
                    <ul class="ebsp-preset-list"></ul>
                    <div class="ebsp-preset-add">
                        <input type="text" placeholder="Nouveau plat">
                        <button type="button" class="button btn-add-preset">+ Ajouter</button>
                    </div>
                </div>

                <div class="ebsp-preset-section" data-type="drinks">
                    <h2>Boissons (Drinks)</h2>
                    <ul class="ebsp-preset-list"></ul>
                    <div class="ebsp-preset-add">
                        <input type="text" placeholder="Nouvelle boisson">
                        <button type="button" class="button btn-add-preset">+ Ajouter</button>
                    </div>
                </div>
            </div>

            <div id="ebsp-presets-status"></div>
        </div>
        <?php
    }

    public function render_admin_page() {
        $presets = get_option( 'ebsp_presets', array( 'starters' => array(), 'mains' => array(), 'drinks' => array() ) );
        ?>
        <div class="wrap ebsp-admin-wrap">
            <h1>Menu Creation</h1>
            <div class="ebsp-layout">
                <div class="ebsp-form-section">
                    <form id="ebsp-menu-form">
                        <div class="ebsp-field">
                            <label for="ebsp-day">Day (Jour)</label>
                            <select id="ebsp-day" name="day">
                                <option value="Lundi">Lundi</option>
                                <option value="Mardi">Mardi</option>
                                <option value="Mercredi">Mercredi</option>
                                <option value="Jeudi">Jeudi</option>
                                <option value="Vendredi">Vendredi</option>
                                <option value="Samedi">Samedi</option>
                                <option value="Dimanche">Dimanche</option>
                            </select>
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-starter-title">Starter (De Primero) - Title</label>
                            <input type="text" list="ebsp-list-starters" id="ebsp-starter-title" name="starter_title" required>
                            <datalist id="ebsp-list-starters">
                                <?php foreach( $presets['starters'] as $item ) : ?>
                                    <option value="<?php echo esc_attr( $item ); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-starter-prompt">Starter Image Prompt</label>
                            <input type="text" id="ebsp-starter-prompt" name="starter_prompt" placeholder="Image prompt for AI">
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-main1-title">Main Course 1 (De Segundo) - Title</label>
                            <input type="text" list="ebsp-list-mains" id="ebsp-main1-title" name="main1_title" required>
                            <datalist id="ebsp-list-mains">
                                <?php foreach( $presets['mains'] as $item ) : ?>
                                    <option value="<?php echo esc_attr( $item ); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-main1-prompt">Main Course 1 Image Prompt</label>
                            <input type="text" id="ebsp-main1-prompt" name="main1_prompt" placeholder="Image prompt for AI">
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-main2-title">Main Course 2 - Title</label>
                            <input type="text" list="ebsp-list-mains" id="ebsp-main2-title" name="main2_title" required>
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-main2-prompt">Main Course 2 Image Prompt</label>
                            <input type="text" id="ebsp-main2-prompt" name="main2_prompt" placeholder="Image prompt for AI">
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-drink">Drink (Bebida)</label>
                            <input type="text" list="ebsp-list-drinks" id="ebsp-drink" name="drink" required>
                            <datalist id="ebsp-list-drinks">
                                <?php foreach( $presets['drinks'] as $item ) : ?>
                                    <option value="<?php echo esc_attr( $item ); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-price">Price (Prix CHF)</label>
                            <input type="text" id="ebsp-price" name="price" value="22" required>
                        </div>

                        <div class="ebsp-field">
                            <label for="ebsp-audio">Audio Vibe</label>
                            <select id="ebsp-audio" name="audio">
                                <option value="salsa">Salsa Loop</option>
                                <option value="cumbia">Cumbia Loop</option>
                            </select>
                        </div>

                        <div class="ebsp-actions">
                            <button type="button" id="ebsp-btn-caption" class="button button-secondary">✨ Rédiger Légende</button>
                            <button type="button" id="ebsp-btn-generate" class="button button-primary">🎨 Générer Visuel</button>
                        </div>
                    </form>
                </div>

                <div class="ebsp-preview-section">
                    <h2>Preview Modal</h2>
                    <div id="ebsp-modal" class="ebsp-modal hidden">
                        <div class="ebsp-modal-content">
                            <div class="ebsp-canvas-container">
                                <canvas id="ebsp-canvas" width="1080" height="1920"></canvas>
                            </div>
                            <div class="ebsp-caption-container">
                                <label for="ebsp-caption">Generated Caption:</label>
                                <textarea id="ebsp-caption" rows="6"></textarea>
                            </div>
                            <div class="ebsp-modal-actions">
                                <button type="button" id="ebsp-btn-regenerate" class="button">🔄 Régénérer Visuel</button>
                                <button type="button" id="ebsp-btn-publish" class="button button-primary">🚀 Valider & Publier</button>
                            </div>
                            <div id="ebsp-status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
