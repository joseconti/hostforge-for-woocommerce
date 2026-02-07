<?php
/**
 * Subscription Styles.
 *
 * This template can be overridden by copying it to yourtheme/sumosubscriptions/subscription-styles.php.
 */
defined( 'ABSPATH' ) || exit ;

$custom_bgcolor = sumosubs_get_custom_bgcolor() ;
?>
#sumosubscription_log_information ul.subscription_notes li.success .note_content {background: #<?php echo esc_attr( $custom_bgcolor[ 'n_success' ] ) ; ?> none repeat scroll 0 0;}
#sumosubscription_log_information ul.subscription_notes li.pending .note_content{background: #<?php echo esc_attr( $custom_bgcolor[ 'n_pending' ] ) ; ?> none repeat scroll 0 0;}
#sumosubscription_log_information ul.subscription_notes li.processing .note_content{background: #<?php echo esc_attr( $custom_bgcolor[ 'n_processing' ] ) ; ?> none repeat scroll 0 0;}
#sumosubscription_log_information ul.subscription_notes li.failure .note_content{background: #<?php echo esc_attr( $custom_bgcolor[ 'n_failure' ] ) ; ?> none repeat scroll 0 0;}
#sumosubscription_log_information ul.subscription_notes li.failure .note_content::after {border-color: #<?php echo esc_attr( $custom_bgcolor[ 'n_failure' ] ) ; ?> transparent;}
#sumosubscription_log_information ul.subscription_notes li.success .note_content::after {border-color: #<?php echo esc_attr( $custom_bgcolor[ 'n_success' ] ) ; ?> transparent;}
#sumosubscription_log_information ul.subscription_notes li.pending .note_content::after {border-color: #<?php echo esc_attr( $custom_bgcolor[ 'n_pending' ] ) ; ?> transparent;}
#sumosubscription_log_information ul.subscription_notes li.processing .note_content::after {border-color: #<?php echo esc_attr( $custom_bgcolor[ 'n_processing' ] ) ; ?> transparent;}

mark.sumosubs-status{font: 13px arial, sans-serif;text-align: center;display:table-cell;border-radius: 15px;padding:4px 6px 4px 6px;}
mark.sumosubs-status.Pending{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_pending' ] ) ; ?>;color:white;}
mark.sumosubs-status.Trial{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_trial' ] ) ; ?>;color:white;}
mark.sumosubs-status.Pause{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_pause' ] ) ; ?>;color:white;}
mark.sumosubs-status.Failed{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_failed' ] ) ; ?>;color:white;}
mark.sumosubs-status.Active-Subscription{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_active' ] ) ; ?>;color:white;}
mark.sumosubs-status.Overdue{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_overdue' ] ) ; ?>;color:white;}
mark.sumosubs-status.Suspended{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_suspended' ] ) ; ?>;color:red;}
mark.sumosubs-status.Cancelled{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_cancelled' ] ) ; ?>;color:white;}
mark.sumosubs-status.Pending_Cancellation{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_pending_cancel' ] ) ; ?>;color:white;}
mark.sumosubs-status.Pending_Authorization{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_pending_authorization' ] ) ; ?>;color:white;}
mark.sumosubs-status.Expired{background-color:#<?php echo esc_attr( $custom_bgcolor[ '_expired' ] ) ; ?>;color:white;}


