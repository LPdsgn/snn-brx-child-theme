<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Local User Avatar Feature
 *
 * Allows users to upload a local profile picture from the WordPress Media Library
 * instead of relying on Gravatar. Based on ASENHA's Local User Avatar module.
 *
 * @since 1.0.0
 */

// ─── Helper: get user ID from mixed $id_or_email ───────────────────────────────

function snn_lua_get_user_id( $id_or_email ) {
    $user_id = false;

    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;

    } elseif ( is_string( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
        if ( $user instanceof WP_User ) {
            $user_id = (int) $user->ID;
        }

    } elseif ( is_object( $id_or_email ) ) {
        if ( isset( $id_or_email->ID ) && is_numeric( $id_or_email->ID ) ) {
            $user_id = (int) $id_or_email->ID;
        } elseif ( isset( $id_or_email->comment_author_email ) ) {
            $user = get_user_by( 'email', $id_or_email->comment_author_email );
            if ( $user instanceof WP_User ) {
                $user_id = (int) $user->ID;
            }
        }
    }

    return $user_id;
}

// ─── Check if feature is enabled ────────────────────────────────────────────────
// Setting stored in snn_other_settings (Dashboard Settings page)

function snn_is_local_avatar_enabled() {
    $options = get_option( 'snn_other_settings', array() );
    return ! empty( $options['enable_local_avatar'] );
}

// ─── Render profile picture fields on user profile page ─────────────────────────

function snn_lua_render_profile_fields( $user ) {
    if ( ! snn_is_local_avatar_enabled() ) {
        return;
    }

    $attachment_id = get_user_meta( $user->ID, 'snn_local_avatar_attachment_id', true );
    ?>
    <table class="form-table" role="presentation" style="display:none;" id="snn-local-user-avatar-wrap">
        <tbody>
            <tr id="snn-local-user-avatar">
                <th><?php esc_html_e( 'Immagine profilo', 'snn' ); ?></th>
                <td>
                    <?php echo get_avatar( $user->ID, 96, '', $user->display_name, array( 'class' => 'snn-avatar-preview' ) ); ?>
                    <p class="description <?php if ( ! empty( $attachment_id ) ) echo 'hidden'; ?>" id="snn-avatar-description">
                        <?php esc_html_e( "You're seeing the default profile picture.", 'snn' ); ?>
                    </p>
                    <div class="snn-avatar-btn-container">
                        <button type="button" class="button" id="snn-btn-media-add"><?php esc_html_e( 'Change', 'snn' ); ?></button>
                        <button type="button" class="button <?php if ( empty( $attachment_id ) ) echo 'hidden'; ?>" id="snn-btn-media-remove"><?php esc_html_e( 'Reset to Default', 'snn' ); ?></button>
                    </div>
                    <input type="hidden" name="snn_local_avatar_attachment_id" value="<?php echo esc_attr( $attachment_id ); ?>" />
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}
add_action( 'show_user_profile', 'snn_lua_render_profile_fields' );
add_action( 'edit_user_profile', 'snn_lua_render_profile_fields' );

// ─── Update user meta on profile save ───────────────────────────────────────────

function snn_lua_update_user_meta( $user_id ) {
    if ( ! snn_is_local_avatar_enabled() ) {
        return;
    }

    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    delete_user_meta( $user_id, 'snn_local_avatar_attachment_id' );

    if ( isset( $_POST['snn_local_avatar_attachment_id'] ) && is_numeric( $_POST['snn_local_avatar_attachment_id'] ) ) {
        add_user_meta( $user_id, 'snn_local_avatar_attachment_id', (int) $_POST['snn_local_avatar_attachment_id'] );
    }

    return true;
}
add_action( 'personal_options_update', 'snn_lua_update_user_meta' );
add_action( 'edit_user_profile_update', 'snn_lua_update_user_meta' );

// ─── Delete user meta when attachment is deleted ────────────────────────────────

function snn_lua_delete_user_meta( $post_id ) {
    if ( ! snn_is_local_avatar_enabled() ) {
        return;
    }

    global $wpdb;
    $wpdb->delete(
        $wpdb->usermeta,
        array(
            'meta_key'   => 'snn_local_avatar_attachment_id',
            'meta_value' => (int) $post_id,
        ),
        array( '%s', '%d' )
    );
}
add_action( 'delete_attachment', 'snn_lua_delete_user_meta' );

// ─── Override avatar HTML with local image ──────────────────────────────────────

function snn_lua_override_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
    if ( ! snn_is_local_avatar_enabled() ) {
        return $avatar;
    }

    $user_id = snn_lua_get_user_id( $id_or_email );
    if ( ! $user_id ) {
        return $avatar;
    }

    $attachment_id = get_user_meta( $user_id, 'snn_local_avatar_attachment_id', true );
    if ( empty( $attachment_id ) || ! is_numeric( $attachment_id ) ) {
        return $avatar;
    }

    $attachment_src = wp_get_attachment_image_src( $attachment_id, 'medium' );
    if ( $attachment_src !== false ) {
        $avatar = preg_replace( '/src=("|\').*?("|\')/', "src='" . esc_url( $attachment_src[0] ) . "'", $avatar );
    }

    $attachment_srcset = wp_get_attachment_image_srcset( $attachment_id );
    if ( $attachment_srcset !== false ) {
        $avatar = preg_replace( '/srcset=("|\').*?("|\')/', "srcset='" . esc_attr( $attachment_srcset ) . "'", $avatar );
    }

    return $avatar;
}
add_filter( 'get_avatar', 'snn_lua_override_avatar', 5, 5 );

// ─── Override avatar URL with local image URL ───────────────────────────────────

function snn_lua_override_avatar_url( $url, $id_or_email, $args ) {
    if ( ! snn_is_local_avatar_enabled() ) {
        return $url;
    }

    $user_id = snn_lua_get_user_id( $id_or_email );
    if ( ! $user_id ) {
        return $url;
    }

    $attachment_id = get_user_meta( $user_id, 'snn_local_avatar_attachment_id', true );
    if ( empty( $attachment_id ) || ! is_numeric( $attachment_id ) ) {
        return $url;
    }

    $attachment_src = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
    if ( is_array( $attachment_src ) && isset( $attachment_src[0] ) ) {
        return $attachment_src[0];
    }

    return $url;
}
add_filter( 'get_avatar_url', 'snn_lua_override_avatar_url', 10, 3 );

// ─── Enqueue Media Library assets on user profile pages ─────────────────────────

function snn_lua_enqueue_profile_assets( $hook ) {
    if ( ! snn_is_local_avatar_enabled() ) {
        return;
    }

    if ( ! in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) {
        return;
    }

    wp_enqueue_media();

    // CSS
    wp_enqueue_style(
        'snn-local-user-avatar',
        SNN_URL_ASSETS . 'css/snn-local-user-avatar.css',
        array(),
        filemtime( SNN_PATH_ASSETS . 'css/snn-local-user-avatar.css' )
    );

    // JS + localized data
    wp_enqueue_script(
        'snn-local-user-avatar',
        SNN_URL_ASSETS . 'js/snn-local-user-avatar.js',
        array( 'jquery', 'media-editor' ),
        filemtime( SNN_PATH_ASSETS . 'js/snn-local-user-avatar.js' ),
        true
    );

    $current_user = wp_get_current_user();
    $default_avatar_url    = get_avatar_url( $current_user->user_email, array( 'size' => 96, 'force_default' => true ) );
    $default_avatar_url_2x = get_avatar_url( $current_user->user_email, array( 'size' => 192, 'force_default' => true ) );

    wp_localize_script( 'snn-local-user-avatar', 'snn_lua_obj', array(
        'default_avatar_src'    => $default_avatar_url,
        'default_avatar_srcset' => $default_avatar_url_2x . ' 2x',
        'input_name'            => 'snn_local_avatar_attachment_id',
        'media_title'           => __( 'Select Profile Picture', 'snn' ),
        'button_text'           => __( 'Use as Profile Picture', 'snn' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'snn_lua_enqueue_profile_assets' );
