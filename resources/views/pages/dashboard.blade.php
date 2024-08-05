@extends('layouts/contentLayoutMaster')

@section('title', trans('locale.Dashboard'))

@section('vendor-style')
	{{-- vendor css files --}}
	<link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/datatables.min.css')) }}">
@endsection

@section('page-style')
	<style>
		.custom-select {
			height: auto;
		}
	</style>
@endsection

@section('content')

	@foreach($notReadNotifications as $notReadNotification)
		<div class="alert alert-primary alert-dismissible fade show" role="alert">
			<p class="mb-0">
				{{ $notReadNotification->text }}
			</p>
			<button type="button" onclick="readNotification({{ $notReadNotification->id }});" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
	</div>
	@endforeach
	<div class="row">
		<!-- Usage Statistics -->
		<div class="col-lg-6 col-md-12 col-sm-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">@lang('locale.UsageStatistics')</h4>
				</div>
				<div class="card-content">
					<div class="card-body">
						<div class="height-400" id="bar-chart">
					  	</div>
						  
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">@lang('locale.UsageStatistics')</h4>
				</div>
				<div class="card-content">
					<div class="card-body">
						<div class="height-400" id="heatmapChart">
					  	</div>
						  
					</div>
				</div>
			</div>
		</div>
		<!--/ Usage Statistics -->

		
	</div>

	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">@lang('locale.QRCodeAccessHits')</h4>
				</div>
				<div class="card-content">
					<div class="card-body">
						<div class="table-responsive">
							<table id="campaignTable" class="table table-striped">
								<thead>
									<tr>
										<th>@lang('locale.id')</th>
										<th>@lang('locale.campaign.field.name')</th>
										<th>@lang('locale.Browser')</th>
										<th>@lang('locale.CreatedAt')</th>
									</tr>
								</thead>
								<tbody>
									@foreach($campaignHits as $campaignHit)
									<tr>
										<td>{{ $campaignHit->id }}</td>
										<td>{{ $campaignHit->campaign->campaign_name }}</td>
										<td>{{ $campaignHit->browser }}</td>
										<td>{{ $campaignHit->created_at }}</td>
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('vendor-script')
	<!-- vendor files -->
	<script src="{{ asset(mix('vendors/js/charts/chart.min.js')) }}"></script>
	<script src="//maps.googleapis.com/maps/api/js?key=AIzaSyBgjNW0WA93qphgZW-joXVR6VC3IiYFjfo"></script>
	<script src="{{ asset(mix('vendors/js/charts/gmaps.min.js')) }}"></script>
	<script src="{{ asset(mix('vendors/js/tables/datatable/datatables.min.js')) }}"></script>
	<script src="{{ asset(mix('vendors/js/tables/datatable/datatables.bootstrap4.min.js')) }}"></script>
	<script src="{{ asset('vendors/js/charts/plotly.min.js') }}"></script>
@endsection

@section('page-script')
	<script>
		var primary = '#ce0058';
		var grid_line_color = '#dae1e7';
		var success = '#28C76F';
		var danger = '#EA5455';
		var warning = '#FF9F43';
		var label_color = '#1E1E1E';
		var campaignNames = {!! $campaignNames !!};
		var campaignHitCounts = {!! $campaignHitCounts !!};
		var scanningCampaign = {!! $scanningCampaign !!};
		var initLat = 40.730610;
		var InitLng = -73.935242;
		var initZoom = 3;
		var initType = 'today';
		
		$(document).ready(function() {
			campaignTable = $('#campaignTable').DataTable();

			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				}
			});
		});
		
	
// chart1
function fetchDataAndUpdateChart() {
    // Make an AJAX request to your server endpoint
    $.ajax({
        url: '/chart-data',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Update the chart with the new data
            updateChart(data);
        },
        error: function(xhr, status, error) {
            console.error('Error fetching data:', error);
        }
    });
	$.ajax({
        url: '/second-chart-data',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Update the chart with the new data
            updateChart2(data);
        },
        error: function(xhr, status, error) {
            console.error('Error fetching data:', error);
        }
    });
}

function updateChart2(data) {
	Plotly.restyle('bar-chart', 'y', [data.y]);
}

function updateChart(data) {
    Plotly.restyle('heatmapChart', 'y', [data.y]);
}
var layout = {
    title: "{{ trans('locale.ScannedCountPerHour') }}",
    xaxis: { title: "{{ trans('locale.Hour') }}", tickvals: Array.from({ length: 24 }, (_, i) => i), ticktext: Array.from({ length: 24 }, (_, i) => i) }, // Set ticks for 24 hours
    yaxis: { title: "{{ trans('locale.Count') }}" }
};
Plotly.newPlot('heatmapChart', [{
    x: Array.from({ length: 24 }, (_, i) => i),
    y: Array(24).fill(0),
    type: 'bar',
    marker: {
        color: '#ce0058'
    }
}], layout);

// Call fetchDataAndUpdateChart once to render the chart immediately
fetchDataAndUpdateChart();

setInterval(fetchDataAndUpdateChart, 10000); 
// end chart1

// begin chart2
var barChartData = [{
    x: campaignNames, 
    y: campaignHitCounts,
    type: 'bar',
    marker: {
        color: '#ce0058'
    }
}];
var layout = {
    title: "{{ trans('locale.ScannedCount') }}",
    xaxis: {
        title: "{{ trans('locale.CampaignNames') }}"
    },
    yaxis: {
        title: "{{ trans('locale.Count') }}",
        tickformat: ',d'
    }
};
Plotly.newPlot('bar-chart', barChartData, layout);
// end chart2

		function readNotification(notificationId) {
			$.ajax({
				url: "{{ url('/notifications/read') }}",
				type: "POST",
				data: {
					notification_id: notificationId
				},
				dataType: "text",
				success : function (data, status, jqXhr) {
					if (jqXhr.status === 200) {
						console.log('success');
					}
				}
			});
		}
	</script>
@endsection