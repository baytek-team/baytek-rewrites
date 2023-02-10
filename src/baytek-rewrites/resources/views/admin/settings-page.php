<?php

//Template for the admin page

use BaytekRewrites\Plugin;

?>

<div class="wrap">
	<h1><?php _e( 'Rewrite Settings', Plugin::TEXTDOMAIN ); ?></h1>

	<form method="post" action="options.php" id="rewrites-settings">

		<?php settings_fields( 'rewrites' ); ?>
    	<?php do_settings_sections( 'rewrites' ); ?>

		<h2><?php _e('Parent Pages', PLugin::TEXTDOMAIN); ?></h2>
		<table class="form-table">
			<?php foreach($post_types as $type) : ?>
				<?php $selected = isset($rewrites[$type]) ? $rewrites[$type] : ''; ?>
				<tr>
					<th scope="row">
						<label for="rewrite-post-parents_<?php echo $type; ?>"><?php echo $type; ?></label>
					</th>

					<td>
						<select id="rewrite-post-parents_<?php echo $type; ?>" name="rewrites-post-parents[<?php echo $type; ?>]"  class="add_select2">
							<option></option>

							<?php foreach ( $pages as $page ): ?>
								<?php
									if (isset($levels[$page->ID])) {
										$indent = $levels[$page->ID];
									}
									else if ($page->post_parent == 0) {
										$indent = 0;
									}
									else if (isset($levels[$page->post_parent])) {
										$indent = $levels[$page->post_parent] + 1;
									}

									$levels[$page->ID] = $indent;
								?>
								<option value="<?php echo $page->ID; ?>" <?php if ( $selected == $page->ID ) : ?> selected="selected" <?php endif; ?>><?php printf('%s%s [%s]', str_repeat('— ', $indent), $page->post_title, $page->post_name); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<?php submit_button(); ?>
	</form>

</div>
