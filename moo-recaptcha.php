<?php
/**
 * Plugin Name: Moo reCAPTCHA
 * Description: Google reCAPTCHA (v2 Invisible / v2 Checkbox / v3) for WordPress, WooCommerce, and Elementor forms. No license required.
 * Version: 1.0.0
 * Author: MooSpace
 * Text Domain: moo-recaptcha
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MOO_RECAPTCHA_VERSION', '1.0.0' );
define( 'MOO_RECAPTCHA_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOO_RECAPTCHA_URL', plugin_dir_url( __FILE__ ) );

// ─── 預設設定 ──────────────────────────────────────────────────────────────
function moo_recaptcha_defaults() {
    return [
        'site_key'              => '',
        'secret_key'            => '',
        'version'               => 'invisible', // invisible | v2 | v3
        'language'              => 'zh-TW',
        'theme'                 => 'light',     // light | dark
        'score_v3'              => 0.5,
        'error_message'         => 'reCAPTCHA 驗證失敗，請再試一次。',
        'login_form'            => 1,
        'registration_form'     => 1,
        'reset_pwd_form'        => 1,
        'comments_form'         => 1,
        'woo_login'             => 1,
        'woo_register'          => 1,
        'woo_lost_password'     => 1,
        'woo_checkout'          => 1,
        'elementor_form'        => 1,
    ];
}

function moo_recaptcha_get_options() {
    $defaults = moo_recaptcha_defaults();
    $saved    = get_option( 'moo_recaptcha_options', [] );
    return wp_parse_args( $saved, $defaults );
}

// ─── 啟用時寫入預設值 ───────────────────────────────────────────────────────
register_activation_hook( __FILE__, function() {
    if ( ! get_option( 'moo_recaptcha_options' ) ) {
        add_option( 'moo_recaptcha_options', moo_recaptcha_defaults() );
    }
});

// ─── 管理選單 ──────────────────────────────────────────────────────────────
add_action( 'admin_menu', function() {
    add_options_page(
        'Moo reCAPTCHA 設定',
        'Moo reCAPTCHA',
        'manage_options',
        'moo-recaptcha',
        'moo_recaptcha_settings_page'
    );
});

// ─── 儲存設定 ──────────────────────────────────────────────────────────────
add_action( 'admin_init', function() {
    if (
        isset( $_POST['moo_recaptcha_save'] ) &&
        check_admin_referer( 'moo_recaptcha_settings' )
    ) {
        $opts = moo_recaptcha_defaults();
        $post = $_POST;

        $opts['site_key']          = sanitize_text_field( $post['site_key'] ?? '' );
        $opts['secret_key']        = sanitize_text_field( $post['secret_key'] ?? '' );
        $opts['version']           = in_array( $post['version'] ?? '', ['invisible','v2','v3'] ) ? $post['version'] : 'invisible';
        $opts['language']          = sanitize_text_field( $post['language'] ?? 'zh-TW' );
        $opts['theme']             = ( ($post['theme'] ?? '') === 'dark' ) ? 'dark' : 'light';
        $opts['score_v3']          = min( 1, max( 0, (float)( $post['score_v3'] ?? 0.5 ) ) );
        $opts['error_message']     = sanitize_text_field( $post['error_message'] ?? '' );
        $opts['login_form']        = isset( $post['login_form'] ) ? 1 : 0;
        $opts['registration_form'] = isset( $post['registration_form'] ) ? 1 : 0;
        $opts['reset_pwd_form']    = isset( $post['reset_pwd_form'] ) ? 1 : 0;
        $opts['comments_form']     = isset( $post['comments_form'] ) ? 1 : 0;
        $opts['woo_login']         = isset( $post['woo_login'] ) ? 1 : 0;
        $opts['woo_register']      = isset( $post['woo_register'] ) ? 1 : 0;
        $opts['woo_lost_password'] = isset( $post['woo_lost_password'] ) ? 1 : 0;
        $opts['woo_checkout']      = isset( $post['woo_checkout'] ) ? 1 : 0;
        $opts['elementor_form']    = isset( $post['elementor_form'] ) ? 1 : 0;

        update_option( 'moo_recaptcha_options', $opts );
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>設定已儲存。</p></div>';
        });
    }
});

// ─── 設定頁面 HTML ─────────────────────────────────────────────────────────
function moo_recaptcha_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $o = moo_recaptcha_get_options();
    ?>
    <div class="wrap">
        <h1>Moo reCAPTCHA 設定</h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'moo_recaptcha_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th>Site Key（公開金鑰）</th>
                    <td><input type="text" name="site_key" value="<?php echo esc_attr($o['site_key']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Secret Key（私密金鑰）</th>
                    <td><input type="password" name="secret_key" value="<?php echo esc_attr($o['secret_key']); ?>" class="regular-text" autocomplete="new-password"></td>
                </tr>
                <tr>
                    <th>reCAPTCHA 版本</th>
                    <td>
                        <select name="version">
                            <option value="invisible" <?php selected($o['version'],'invisible'); ?>>v2 Invisible（隱形，建議）</option>
                            <option value="v2"        <?php selected($o['version'],'v2'); ?>>v2 Checkbox（核取方塊）</option>
                            <option value="v3"        <?php selected($o['version'],'v3'); ?>>v3（背景評分）</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>語言</th>
                    <td><input type="text" name="language" value="<?php echo esc_attr($o['language']); ?>" class="short-text" placeholder="zh-TW"></td>
                </tr>
                <tr>
                    <th>主題</th>
                    <td>
                        <select name="theme">
                            <option value="light" <?php selected($o['theme'],'light'); ?>>Light</option>
                            <option value="dark"  <?php selected($o['theme'],'dark'); ?>>Dark</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>v3 最低分數</th>
                    <td><input type="number" name="score_v3" value="<?php echo esc_attr($o['score_v3']); ?>" min="0" max="1" step="0.1"> <span class="description">0.0（機器人）～ 1.0（正常人類），預設 0.5</span></td>
                </tr>
                <tr>
                    <th>驗證失敗錯誤訊息</th>
                    <td><input type="text" name="error_message" value="<?php echo esc_attr($o['error_message']); ?>" class="regular-text"></td>
                </tr>
            </table>

            <h2>啟用的表單</h2>
            <table class="form-table">
                <tr><th>WordPress 表單</th><td>
                    <label><input type="checkbox" name="login_form" <?php checked($o['login_form']); ?>> 登入表單</label><br>
                    <label><input type="checkbox" name="registration_form" <?php checked($o['registration_form']); ?>> 註冊表單</label><br>
                    <label><input type="checkbox" name="reset_pwd_form" <?php checked($o['reset_pwd_form']); ?>> 忘記密碼表單</label><br>
                    <label><input type="checkbox" name="comments_form" <?php checked($o['comments_form']); ?>> 留言表單</label>
                </td></tr>
                <tr><th>WooCommerce 表單</th><td>
                    <label><input type="checkbox" name="woo_login" <?php checked($o['woo_login']); ?>> 登入</label><br>
                    <label><input type="checkbox" name="woo_register" <?php checked($o['woo_register']); ?>> 註冊</label><br>
                    <label><input type="checkbox" name="woo_lost_password" <?php checked($o['woo_lost_password']); ?>> 忘記密碼</label><br>
                    <label><input type="checkbox" name="woo_checkout" <?php checked($o['woo_checkout']); ?>> 結帳</label>
                </td></tr>
                <tr><th>其他整合</th><td>
                    <label><input type="checkbox" name="elementor_form" <?php checked($o['elementor_form']); ?>> Elementor Pro 表單</label>
                </td></tr>
            </table>

            <?php submit_button( '儲存設定', 'primary', 'moo_recaptcha_save' ); ?>
        </form>
    </div>
    <?php
}

// ─── 核心：載入 reCAPTCHA JS ───────────────────────────────────────────────
function moo_recaptcha_enqueue_script() {
    $o   = moo_recaptcha_get_options();
    if ( empty( $o['site_key'] ) ) return;

    $ver = $o['version'];
    $hl  = $o['language'];

    $hl_encoded  = urlencode( $hl );
    $key_encoded = urlencode( $o['site_key'] );

    if ( $ver === 'v3' ) {
        wp_enqueue_script(
            'google-recaptcha-v3',
            "https://www.google.com/recaptcha/api.js?render={$key_encoded}&hl={$hl_encoded}",
            [], null, true
        );
    } else {
        wp_enqueue_script(
            'google-recaptcha',
            "https://www.google.com/recaptcha/api.js?onload=mooRecaptchaInit&render=explicit&hl={$hl_encoded}",
            [], null, true
        );
    }

    // 傳遞設定給前端 JS
    wp_localize_script(
        ( $ver === 'v3' ) ? 'google-recaptcha-v3' : 'google-recaptcha',
        'mooRecaptchaConfig',
        [
            'siteKey' => $o['site_key'],
            'version' => $ver,
            'theme'   => $o['theme'],
        ]
    );
}

// ─── 核心：驗證 reCAPTCHA token ────────────────────────────────────────────
function moo_recaptcha_verify( $token = null ) {
    $o = moo_recaptcha_get_options();
    if ( empty( $o['secret_key'] ) ) {
        // Secret Key 未設定：在後台顯示警示，前台靜默放行
        if ( is_admin() && current_user_can( 'manage_options' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p><strong>Moo reCAPTCHA：</strong>Secret Key 尚未設定，所有表單驗證已停用。請至「設定 → Moo reCAPTCHA」填寫金鑰。</p></div>';
            });
        }
        return true;
    }

    if ( $token === null ) {
        $token = sanitize_text_field( $_POST['g-recaptcha-response'] ?? '' );
    }

    if ( empty( $token ) ) return false;

    $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret'   => $o['secret_key'],
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ],
        'timeout' => 10,
    ]);

    if ( is_wp_error( $response ) ) {
        error_log( 'Moo reCAPTCHA: Google API unreachable - ' . $response->get_error_message() );
        return true; // API 無法連線時放行，避免封鎖正常使用者
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['success'] ) ) return false;

    // v3 額外檢查分數
    if ( $o['version'] === 'v3' ) {
        $score = (float)( $body['score'] ?? 0 );
        return $score >= (float)$o['score_v3'];
    }

    return true;
}

// ─── 輸出 reCAPTCHA widget HTML ────────────────────────────────────────────
function moo_recaptcha_widget( $form_id = '' ) {
    $o = moo_recaptcha_get_options();
    if ( empty( $o['site_key'] ) ) return;

    $ver = $o['version'];

    if ( $ver === 'v3' ) {
        // v3：隱形 token，於表單提交時即時取得（避免 2 分鐘過期問題）
        echo '<input type="hidden" name="g-recaptcha-response" id="moo-recaptcha-token-' . esc_attr($form_id) . '">';
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var tokenEl = document.getElementById("moo-recaptcha-token-' . esc_js($form_id) . '");
            if (!tokenEl) return;
            var form = tokenEl.closest("form");
            if (!form) return;
            form.addEventListener("submit", function(e) {
                if (typeof grecaptcha === "undefined") return; // API 未載入時直接放行
                e.preventDefault();
                grecaptcha.ready(function() {
                    grecaptcha.execute(' . json_encode($o['site_key']) . ', {action: "submit"}).then(function(token) {
                        tokenEl.value = token;
                        form.submit();
                    });
                });
            });
        });
        </script>';
    } else {
        // v2 / invisible：用 div 容器，由 mooRecaptchaInit 初始化
        $size = ( $ver === 'invisible' ) ? 'invisible' : 'normal';
        echo '<div class="moo-recaptcha-widget"
                data-sitekey="' . esc_attr($o['site_key']) . '"
                data-size="' . esc_attr($size) . '"
                data-theme="' . esc_attr($o['theme']) . '"
                data-form-id="' . esc_attr($form_id) . '">
              </div>';
    }
}

// ─── 前端 JS（v2 / invisible 初始化 + 表單攔截）──────────────────────────
add_action( 'wp_footer', function() {
    $o = moo_recaptcha_get_options();
    if ( empty($o['site_key']) || $o['version'] === 'v3' ) return;
    ?>
    <script>
    var mooRecaptchaWidgets = {};
    function mooRecaptchaInit() {
        document.querySelectorAll('.moo-recaptcha-widget').forEach(function(el) {
            var formId  = el.getAttribute('data-form-id');
            var widgetId = grecaptcha.render(el, {
                sitekey  : el.getAttribute('data-sitekey'),
                size     : el.getAttribute('data-size'),
                theme    : el.getAttribute('data-theme'),
                callback : function(token) {
                    // invisible: callback 後提交表單
                    var form = el.closest('form');
                    if (form) form.submit();
                }
            });
            mooRecaptchaWidgets[formId] = widgetId;
        });

        // 攔截所有含 moo-recaptcha-widget 的表單
        document.querySelectorAll('form').forEach(function(form) {
            var widget = form.querySelector('.moo-recaptcha-widget');
            if (!widget) return;
            var size = widget.getAttribute('data-size');
            if (size !== 'invisible') return; // v2 checkbox 由使用者點擊
            form.addEventListener('submit', function(e) {
                var formId = widget.getAttribute('data-form-id');
                var widgetId = mooRecaptchaWidgets[formId];
                if (widgetId === undefined) { e.preventDefault(); return; } // API 未載入時阻止提交
                var token = grecaptcha.getResponse(widgetId);
                if (!token) {
                    e.preventDefault();
                    grecaptcha.execute(widgetId);
                }
            });
        });
    }
    </script>
    <?php
}, 20 );

// ══════════════════════════════════════════════════════════════════════════
// WordPress 核心表單
// ══════════════════════════════════════════════════════════════════════════

// ─── 登入表單 ──────────────────────────────────────────────────────────────
add_action( 'init', function() {
    $o = moo_recaptcha_get_options();
    if ( empty($o['site_key']) ) return;

    if ( $o['login_form'] ) {
        add_action( 'login_enqueue_scripts', 'moo_recaptcha_enqueue_script' );
        add_action( 'login_form', function() {
            moo_recaptcha_widget('wp_login');
        });
        add_filter( 'authenticate', function( $user, $username, $password ) {
            if ( isset($_POST['log']) ) { // 登入表單提交
                // 若密碼已驗證失敗，保留原本的錯誤訊息，不覆蓋
                if ( is_wp_error( $user ) ) return $user;
                if ( ! moo_recaptcha_verify() ) {
                    return new WP_Error( 'moo_recaptcha', moo_recaptcha_get_options()['error_message'] );
                }
            }
            return $user;
        }, 30, 3 );
    }

    // ─── 註冊表單 ────────────────────────────────────────────────────────
    if ( $o['registration_form'] ) {
        add_action( 'register_form', function() {
            moo_recaptcha_widget('wp_register');
        });
        add_filter( 'registration_errors', function( $errors, $sanitized_user_login, $user_email ) {
            if ( ! moo_recaptcha_verify() ) {
                $errors->add( 'moo_recaptcha', moo_recaptcha_get_options()['error_message'] );
            }
            return $errors;
        }, 10, 3 );
    }

    // ─── 忘記密碼表單 ────────────────────────────────────────────────────
    if ( $o['reset_pwd_form'] ) {
        add_action( 'lostpassword_form', function() {
            moo_recaptcha_widget('wp_lostpwd');
        });
        add_action( 'lostpassword_post', function( $errors ) {
            if ( ! moo_recaptcha_verify() ) {
                $errors->add( 'moo_recaptcha', moo_recaptcha_get_options()['error_message'] );
            }
        });
    }

    // ─── 留言表單 ────────────────────────────────────────────────────────
    if ( $o['comments_form'] ) {
        add_action( 'comment_form_after_fields', function() {
            moo_recaptcha_enqueue_script();
            moo_recaptcha_widget('wp_comment');
        });
        add_action( 'comment_form_logged_in_after', function() {
            moo_recaptcha_enqueue_script();
            moo_recaptcha_widget('wp_comment_logged_in');
        });
        add_action( 'pre_comment_on_post', function() {
            if ( ! moo_recaptcha_verify() ) {
                wp_die( moo_recaptcha_get_options()['error_message'], '', [ 'response' => 403 ] );
            }
        });
    }
});

// ──────────────────────────────────────────────────────────────────────────
// WooCommerce 表單
// ──────────────────────────────────────────────────────────────────────────
add_action( 'woocommerce_init', function() {
    $o = moo_recaptcha_get_options();
    if ( empty($o['site_key']) ) return;

    add_action( 'wp_enqueue_scripts', 'moo_recaptcha_enqueue_script' );

    // ─── WooCommerce 登入 ────────────────────────────────────────────────
    if ( $o['woo_login'] ) {
        add_action( 'woocommerce_login_form', function() {
            moo_recaptcha_widget('woo_login');
        });
        add_filter( 'woocommerce_process_login_errors', function( $validation_error, $username, $password ) {
            if ( ! moo_recaptcha_verify() ) {
                $validation_error->add( 'moo_recaptcha', moo_recaptcha_get_options()['error_message'] );
            }
            return $validation_error;
        }, 10, 3 );
    }

    // ─── WooCommerce 註冊 ────────────────────────────────────────────────
    if ( $o['woo_register'] ) {
        add_action( 'woocommerce_register_form', function() {
            moo_recaptcha_widget('woo_register');
        });
        add_filter( 'woocommerce_process_registration_errors', function( $validation_error, $username, $password, $email ) {
            if ( ! moo_recaptcha_verify() ) {
                $validation_error->add( 'moo_recaptcha', moo_recaptcha_get_options()['error_message'] );
            }
            return $validation_error;
        }, 10, 4 );
    }

    // ─── WooCommerce 忘記密碼 ────────────────────────────────────────────
    if ( $o['woo_lost_password'] ) {
        add_action( 'woocommerce_lostpassword_form', function() {
            moo_recaptcha_widget('woo_lostpwd');
        });
        // 只攔截「送出忘記密碼表單」的 POST，不影響 Email 連結點擊流程
        add_action( 'woocommerce_lostpassword_post', function() {
            if ( ! moo_recaptcha_verify() ) {
                wc_add_notice( moo_recaptcha_get_options()['error_message'], 'error' );
                // 回傳 WP_Error 讓 WooCommerce 中止流程，且只顯示我們的訊息
                add_filter( 'allow_password_reset', function() {
                    return new WP_Error( 'moo_recaptcha', '' );
                });
            }
        });
    }

    // ─── WooCommerce 結帳 ────────────────────────────────────────────────
    if ( $o['woo_checkout'] ) {
        add_action( 'woocommerce_checkout_after_terms_and_conditions', function() {
            moo_recaptcha_widget('woo_checkout');
        });
        add_action( 'woocommerce_after_checkout_validation', function( $data, $errors ) {
            if ( ! moo_recaptcha_verify() ) {
                $errors->add( 'moo_recaptcha', moo_recaptcha_get_options()['error_message'] );
            }
        }, 100, 2 );
    }
});

// ──────────────────────────────────────────────────────────────────────────
// Elementor Pro 表單
// ──────────────────────────────────────────────────────────────────────────
add_action( 'elementor_pro/init', function() {
    $o = moo_recaptcha_get_options();
    if ( empty($o['site_key']) || ! $o['elementor_form'] ) return;

    // 在 Elementor 表單提交前驗證
    add_action( 'elementor_pro/forms/validation', function( $record, $ajax_handler ) {
        if ( ! moo_recaptcha_verify() ) {
            $ajax_handler->add_error_message( moo_recaptcha_get_options()['error_message'] );
            $ajax_handler->is_success = false;
        }
    }, 10, 2 );

    add_action( 'elementor/frontend/before_enqueue_scripts', 'moo_recaptcha_enqueue_script' );
});
