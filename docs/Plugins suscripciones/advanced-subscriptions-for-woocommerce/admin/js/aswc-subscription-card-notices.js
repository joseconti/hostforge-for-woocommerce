/**
 * All of the code for notices on your admin-facing JavaScript source
 * should reside in this file.
 *
 * @package           woo-gift-cards-lite
 */
jQuery( document ).ready(
    function($){
        $( document ).on(
            'click',
            '#dismiss-banner',
            function(e){
                e.preventDefault();
                var data = {
                    action:'aswc_dismiss_notice_banner',
                    aswc_nonce:aswc_branner_notice.aswc_nonce
                };
                $.ajax(
                    {
                        url: aswc_branner_notice.ajaxurl,
                        type: "POST",
                        data: data,
                        success: function(response)
                        {
                            window.location.reload();
                        }
                    }
                );
            }
        );
    }
);
