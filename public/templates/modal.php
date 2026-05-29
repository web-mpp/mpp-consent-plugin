<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

$settings   = WPCS_Settings::get();
$categories = $settings['categories'];
$consent    = WPCS_ConsentManager::get_instance();
$granted    = $consent->get_categories();

$policy_page_id = (int) $settings['cookie_policy_page_id'];
$policy_url     = $policy_page_id > 0 ? get_permalink( $policy_page_id ) : '';

$modal_title  = WPCS_Settings::get_locale_string( 'modal_title',  __( 'Cookie Preferences',                    'wp-cookie-shield' ) );
$modal_intro  = WPCS_Settings::get_locale_string( 'modal_intro',  __( 'Manage your cookie preferences below:', 'wp-cookie-shield' ) );
$modal_accept = WPCS_Settings::get_locale_string( 'modal_accept', __( 'Accept All',                            'wp-cookie-shield' ) );
$modal_close  = WPCS_Settings::get_locale_string( 'modal_close',  __( 'Close',                                 'wp-cookie-shield' ) );
$modal_save   = WPCS_Settings::get_locale_string( 'modal_save',   __( 'Save and Close',                        'wp-cookie-shield' ) );

ob_start();
?>
<div id="wpcs-modal-overlay" class="wpcs-overlay" aria-hidden="true">
	<div id="wpcs-modal"
	     class="wpcs-modal"
	     role="dialog"
	     aria-modal="true"
	     aria-labelledby="wpcs-modal-title"
	     tabindex="-1">

		<button type="button" id="wpcs-modal-close" class="wpcs-modal__close" aria-label="<?php esc_attr_e( 'Close cookie preferences', 'wp-cookie-shield' ); ?>">
			&#10005;
		</button>

		<h2 id="wpcs-modal-title" class="wpcs-modal__title"><?php echo esc_html( $modal_title ); ?></h2>
		<p class="wpcs-modal__intro"><?php echo esc_html( $modal_intro ); ?></p>

		<div class="wpcs-accordion">
			<?php foreach ( $categories as $key => $cat ) :
				$is_locked  = ! empty( $cat['locked'] );
				$is_granted = ! empty( $granted[ $key ] );
			?>
			<div class="wpcs-accordion__item" data-category="<?php echo esc_attr( $key ); ?>">
				<button type="button"
				        class="wpcs-accordion__header"
				        aria-expanded="false"
				        aria-controls="wpcs-cat-<?php echo esc_attr( $key ); ?>">
					<span class="wpcs-accordion__label"><?php echo esc_html( $cat['label'] ); ?></span>

					<?php if ( $is_locked ) : ?>
						<span class="wpcs-toggle wpcs-toggle--locked"
						      aria-disabled="true"
						      title="<?php esc_attr_e( 'Essential cookies cannot be disabled', 'wp-cookie-shield' ); ?>">
							<span class="wpcs-toggle__knob"></span>
						</span>
					<?php else : ?>
						<span class="wpcs-toggle <?php echo $is_granted ? 'wpcs-toggle--on' : ''; ?>"
						      role="switch"
						      aria-checked="<?php echo $is_granted ? 'true' : 'false'; ?>"
						      tabindex="0"
						      data-category="<?php echo esc_attr( $key ); ?>">
							<span class="wpcs-toggle__knob"></span>
						</span>
					<?php endif; ?>
				</button>

				<div id="wpcs-cat-<?php echo esc_attr( $key ); ?>"
				     class="wpcs-accordion__body"
				     hidden>
					<p><?php echo esc_html( $cat['description'] ); ?></p>
					<div class="wpcs-cookie-list" data-category="<?php echo esc_attr( $key ); ?>">
						<!-- JS populates this from REST /categories -->
					</div>
				</div>
			</div>
			<?php endforeach; ?>

			<?php if ( $policy_url ) : ?>
			<div class="wpcs-accordion__item wpcs-accordion__item--link">
				<a href="<?php echo esc_url( $policy_url ); ?>" class="wpcs-accordion__header wpcs-policy-link" target="_blank" rel="noopener">
					<?php esc_html_e( 'Cookie Policy', 'wp-cookie-shield' ); ?> &#8594;
				</a>
			</div>
			<?php endif; ?>
		</div>

		<div class="wpcs-modal__footer">
			<button type="button" id="wpcs-modal-accept-all" class="wpcs-btn wpcs-btn--accept"><?php echo esc_html( $modal_accept ); ?></button>
			<button type="button" id="wpcs-modal-close-btn" class="wpcs-btn wpcs-btn--outline"><?php echo esc_html( $modal_close ); ?></button>
			<button type="button" id="wpcs-modal-save" class="wpcs-btn wpcs-btn--save"><?php echo esc_html( $modal_save ); ?></button>
		</div>
	</div>
</div>
<?php

$modal_html = ob_get_clean();
echo apply_filters( 'wpcs_modal_html', $modal_html );
