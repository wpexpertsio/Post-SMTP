jQuery( document ).ready( function() {

    jQuery.fn.suggestPro = function() {

        jQuery( postmanPro ).each( function( index, value ){
        
            var allRows = jQuery( '.ps-socket-wizad-row' );
            var totalRows = allRows.length - 1;
            var lastRow = jQuery( allRows[totalRows] );
            var lastRowLength = lastRow.find( 'label' );
    
            //Write in existing row
            if( lastRowLength.length < 3 ) {
                jQuery( lastRow ).append( 
                    `<a href="${value.url}" style="box-shadow: none;" target="_blank">
                        <label style="text-align:center">
                            <div class="ps-single-socket-outer ps-sib">
                                <img src="${value.pro}" class="ps-sib-recommended">
                                <img src="${value.logo}" class="ps-wizard-socket-logo" width="165px">
                            </div>
                            <img draggable="false" role="img" class="emoji" alt="🔒" src="https://s.w.org/images/core/emoji/14.0.0/svg/1f512.svg">${value.extenstion}
                        </label>
                    </a>`
                );
            }
            //New row
            else {
                jQuery( lastRow ).after(
                    `<div class='ps-socket-wizad-row'>
                        <a href="${value.url}" style="box-shadow: none;" target="_blank">
                            <label style="text-align:center">
                                <div class="ps-single-socket-outer ps-sib">
                                    <img src="${value.pro}" class="ps-sib-recommended">
                                    <img src="${value.logo}" class="ps-wizard-socket-logo" width="165px">
                                </div>
                                <img draggable="false" role="img" class="emoji" alt="🔒" src="https://s.w.org/images/core/emoji/14.0.0/svg/1f512.svg">${value.extenstion}
                            </label>
                        </a>
                    </div>`
                );
            }
    
        } );

    }

} )