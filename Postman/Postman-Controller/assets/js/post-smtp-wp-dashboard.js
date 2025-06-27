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
            maintainAspectRatio: false,
            scales: {
                x: {
                    // Remove type: 'time' - use default category scale
                    ticks: {
                        padding: 10,
                        minRotation: 25,
                        maxRotation: 25
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        maxTicksLimit: 6,
                        padding: 20,
                        callback: function (value) {
                            return Math.floor(value) === value ? value : null;
                        }
                    }
                }
            },
            elements: {
                line: {
                    tension: 0
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    displayColors: false
                }
            }
        }
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
    var days = 7,
        data = [55, 45, 34, 45, 32, 55, 65],
        labels = [];

    // Create simple date labels without time objects
    var today = new Date();
    for (var i = days - 1; i >= 0; i--) {
        var date = new Date(today);
        date.setDate(date.getDate() - i);
        var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
            "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        labels.push(monthNames[date.getMonth()] + " " + 
            String(date.getDate()).padStart(2, '0'));
    }

    // Simple data structure for category scale
    chart.data.labels = labels;
    chart.data.datasets[0].data = data; // Just simple array, not objects

    console.log('chart data updated: ', chart.data);

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