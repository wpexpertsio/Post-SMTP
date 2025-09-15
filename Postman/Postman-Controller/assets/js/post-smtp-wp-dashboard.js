window.chartInstances = window.chartInstances || {};
jQuery(document).ready(function($) {
	
	var el = { 
		canvas                       : jQuery('#post-smtp-dash-widget-chart'), 
		settingsBtn                  : jQuery('#post-smtp-dash-widget-settings-button'),
		dismissBtn                   : jQuery('.post-smtp-dash-widget-dismiss-chart-upgrade'),
		summaryReportEmailBlock      : jQuery('.post-smtp-dash-widget-summary-report-email-block'),
		summaryReportEmailDismissBtn : jQuery('.post-smtp-dash-widget-summary-report-email-dismiss'),
		summaryReportEmailEnableInput: jQuery('#post-smtp-dash-widget-summary-report-email-enable'),
		emailAlertsDismissBtn        : jQuery('#post-smtp-dash-widget-dismiss-email-alert-block')
	};
    // Initialize Chart
    var ctx = el.canvas[0].getContext('2d');
	
    var transactionChart = new Chart(ctx, {
        type: 'line',
        data: {
			labels: [],
			datasets: [
				{
					label: '',
					data: [],
					backgroundColor: 'rgba(34, 113, 177, 0.15)',
					borderColor: 'rgba(34, 113, 177, 1)',
					borderWidth: 2,
					pointRadius: 4,
					pointBorderWidth: 1,
					pointBackgroundColor: 'rgba(255, 255, 255, 1)'
				}
			]
		},
        options: { 
			// responsive: true,
			maintainAspectRatio: false, // Maintain aspect ratio
            scales: {
				y: {
					beginAtZero: true,
					ticks: {
						stepSize: 1,
					},
				},
				x: {
					grid: {
						display: true
					}
				}
            },
			elements: {
				line: {
					tension: 0.4,
				},
			},
			animation: {
				duration: 1000, // Animation duration in milliseconds
				easing: 'easeInOutQuart', // Easing function to make it smooth
			},
			hover: {
				animationDuration: 0,
			},
			legend: {
				display: true,
			},
			tooltip: {
				mode: 'index',
				intersect: false
			},
			responsiveAnimationDuration: 0,
        },
    });
	window.chartInstances['myChart'] = transactionChart;
	updateWithDummyData(transactionChart);
	
	el.settingsBtn.on( 'click', function( e ) {
		$( this ).toggleClass( 'open' );
		$( this ).siblings( '.post-smtp-dash-widget-settings-menu' ).fadeToggle( 200 );
	} );

	el.dismissBtn.on( 'click', function( event ) {
		event.preventDefault();

		// saveWidgetMeta( 'hide_graph', 1 );
		$( this ).closest( '.post-smtp-dash-widget-chart-block-container' ).remove();
		// $( '#post-smtp-dash-widget-upgrade-footer' ).show();
	} );

	// Hide summary report email block on dismiss icon click.
	el.summaryReportEmailDismissBtn.on( 'click', function( event ) {
		event.preventDefault();

		saveWidgetMeta( 'hide_summary_report_email_block', 1 );
		el.summaryReportEmailBlock.slideUp();
	} );

	// Enable summary report email on checkbox enable.
	el.summaryReportEmailEnableInput.on( 'change', function( event ) {
		event.preventDefault();

		var $self = $( this ),
			$loader = $self.next( 'i' );

		$self.hide();
		$loader.show();

		var data = {
			_wpnonce: post_smtp_dashboard_widget.nonce,
			action  : 'post_smtp_' + post_smtp_dashboard_widget.slug + '_enable_summary_report_email'
		};

		$.post( post_smtp_dashboard_widget.ajax_url, data )
			.done( function() {
				el.summaryReportEmailBlock.find( '.post-smtp-dash-widget-summary-report-email-block-setting' )
					.addClass( 'hidden' );
				el.summaryReportEmailBlock.find( '.post-smtp-dash-widget-summary-report-email-block-applied' )
					.removeClass( 'hidden' );
			} )
			.fail( function() {
				$self.show();
				$loader.hide();
			} );
	} );

	// Hide email alerts banner on dismiss icon click.
	el.emailAlertsDismissBtn.on( 'click', function( event ) {
		event.preventDefault();

		$( '#post-smtp-dash-widget-email-alerts-education' ).remove();
		saveWidgetMeta( 'hide_email_alerts_banner', 1 );
	} );

	// chart.init();
	
});

function updateWithDummyData(chart) {
    var end = moment().startOf('day'),
        days = 7,  // Number of days to go back
        data = [55, 45, 34, 45, 32, 55, 65],  // The dummy data points
        date,
        i;

    // Clear the previous data in the chart
    chart.data.labels = [];
    chart.data.datasets[0].data = [];

    // Loop to create dummy data for each day in the range
    for (i = 0; i < days; i++) {
        // Clone the 'end' date to avoid modifying the original 'end' date
        date = end.clone().subtract(i, 'days');

        // Push formatted date to labels (e.g., 'Apr 27', 'Apr 26', etc.)
        chart.data.labels.push(date.format('MMM DD'));  // Format the date as string (e.g., 'Apr 27')

        // Push the data for this day to the dataset
        chart.data.datasets[0].data.push({
            t: date.valueOf(),  // Convert the Moment object to Unix timestamp (milliseconds)
            y: data[i],         // Corresponding y value
        });
    }

    console.log('chart data updated: ', chart.data);  // Log the chart data for debugging

    // Update the chart with the new data
    chart.update();
	removeOverlay( jQuery('#post-smtp-dash-widget-chart') );
}

function removeOverlay( $el ) {  
	$el.siblings( '.post-smtp-dash-widget-overlay' ).hide();
}

function showOverlay( $el ) {  
	$el.siblings( '.post-smtp-dash-widget-overlay' ).show();
}
function saveWidgetMeta( meta, value ) {

	var data = {
		_wpnonce: post_smtp_dashboard_widget.nonce,
		action  : 'post_smtp_' + post_smtp_dashboard_widget.slug + '_save_widget_meta',
		meta    : meta,
		value   : value,
	};

	jQuery.post( post_smtp_dashboard_widget.ajax_url, data ); 
	
	jQuery( '.post-smtp-dash-widget-settings-menu' ).fadeToggle( 200 );
}
