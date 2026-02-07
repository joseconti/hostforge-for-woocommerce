<ul class="subscription_notes">
	<?php
	foreach ( $notes as $note ) {
		include 'html-admin-subscription-note.php' ;
	}
	?>
</ul>
<div class="add_subscription_note">
	<h4><?php esc_html_e( 'Add note', 'sumosubscriptions' ) ; ?></h4>
	<p><textarea type="text" name="add_subscription_note" id="add_subscription_note" class="input-text" cols="20" rows="5"></textarea></p>
	<p><a href="#" class="sumo_add_note button" data-id="<?php echo esc_attr( $post->ID ) ; ?>"><?php esc_html_e( 'Add', 'sumosubscriptions' ) ; ?></a></p>
</div>
