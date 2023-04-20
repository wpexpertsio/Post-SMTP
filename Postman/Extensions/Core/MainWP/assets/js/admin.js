//Filter the sites
jQuery(document).ready(function() {
	// Listen for changes in the search filter input field
	jQuery('#post-smtp-select-sites-filter').on('input', function() {
		var filterValue = jQuery(this).val().toLowerCase();

		// Loop through each site div and check if the title matches the search filter
		jQuery('.post-smtp-mainwp-site').each(function() {
			var title = jQuery(this).find('.title').text().toLowerCase();

			// Hide the site div if the title does not match the search filter
			if (title.indexOf(filterValue) === -1) {
				jQuery(this).hide();
			} else {
				jQuery(this).show();
			}
		});
	});
	
	//Render Child Sites on Log Page
	var _options = `<option value='-1'>${PSMainWP.allSites}</option>`;
	_options += `<option value='main_site'>${PSMainWP.mainSite}</option>`;
	jQuery.each( PSMainWP.childSites, function( key, value ) {
		
		_options += `<option value='${key}'>${value}</option>`;
		
	} )
	jQuery( '.ps-email-log-date-filter' ).after( `
		<div class="ps-mainwp-site-filter">
			<label>Site 
				<select class="ps-mainwp-site-selector">
					${_options}
				</select>
			</label>
		</div>
	` );
	
	//Lets do somthing on changing site
	jQuery( document ).on( 'change', '.ps-mainwp-site-selector', function() {
		
		debugger;
		
	} )
	
});

