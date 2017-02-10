$( document ).ready( function() {

    /**
     * click on torrent details and display in popup
     */
    $( ".downloaddetails" ).click( function() {

        var specs = "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=430,height=225";
        window.open( $( this ).prop( "href" ), "_blank", specs );

        return false;
    } );

    /**
     * start download/leech torrent file
     */
    $( ".startTorrent" ).click( function() {

        var specs = "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=700,height=530";
        window.open( $( this ).prop( "href" ), "_blank", specs );

        return false;
    } );

    /**
     * delete torrent file
     */
    $( ".deleteTorrent" ).click( function() {

        var caption = confirmDeleteTorrent + $( this ).data( "torrent" );
        return confirm( caption );
    } );

    /**
     * display torrent upload form
     * and hide all visible forms
    */
    $( ".display_upload_form" ).click( function() {
 
        $( "#upload_file" ).trigger( "click" );
    } );

    /**
     * Select torrent file for upload
    */
    $( "#upload_file" ).on( "change", function() {

        $( "#form_file" ).submit(); 
    } );

    /**
     * display torrent url form
     * and hide all visible forms
    */
    $( ".display_url_form" ).click( function() {

        var visible = $( this ).hasClass( "btn-primary" );

        hideAllForms();

        if ( visible ) {
            $( "#form_url" ).slideToggle();
            $( ".display_url_form" ).toggleClass( "btn-primary btn-success" );
        }
    } );

    /**
     * display torrent search form
     * and hide all visible forms
    */
    $( ".display_search_form" ).click( function() {

        var visible = $( this ).hasClass( "btn-primary" );

        hideAllForms();

        if ( visible ) {
            $( "#form_search" ).slideToggle();
            $( ".display_search_form" ).toggleClass( "btn-primary btn-success" );
        }
    } );

    /**
     * hide all visible forms
    */
    $( ".close_form" ).click( hideAllForms );

    /**
     * hide all visible forms
    */
    function hideAllForms() {

        $( "#form_url" ).hide( "fast" );
        $( "#form_search" ).hide( "fast" );

        if ( $( ".display_url_form" ).hasClass( "btn-success" ) ) {
            $( ".display_url_form" ).toggleClass( "btn-primary btn-success" );
        }

        if ( $( ".display_search_form" ).hasClass( "btn-success" ) ) {
            $( ".display_search_form" ).toggleClass( "btn-primary btn-success" );
        }
    }

} );