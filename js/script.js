var loadingEnabled = false;
var tmpWatt = new Array();
// Live chart function


function requestLiveData() {
	var selected_meter = $('select[name=selected_meter]').val();
    $.ajax({
        url: 'ajax.php?a=live',
        dataType: 'json',
        success: function(json) {

			var interval = $('#settingsOverlay').data('liveinterval');
			
				var shiftMax = 60000 / interval;
		
			
			for(var i=0;i<json.length;i++)
			{
				
				if(chart.series.length < json.length)
				{	
					var addseries = {
						id: i,
			            name: json[i].name,
			            marker: {
			            	radius: 0
			            },		            
			            data: []
		            }
					chart.addSeries(addseries);
					$('#wattCounter').append('<span>'+json[i].name+': <span class=wattCounter_'+i+'></span></span>');
					tmpWatt[i] = 0;
				}
				
	            var series = chart.series[i],
	                shift = series.data.length > shiftMax; // shift if the series is longer than shiftMax					
	
	            // add the point
	            var x = (new Date()).getTime();
	            var y = json[i]["pwr"];
	            //console.log(point);
	            chart.series[i].addPoint([x, y], false, shift);
	            
	            
	            
	            // up/down indicator
	            if(tmpWatt[i] < parseInt(json[i]["pwr"])){
	            	updown = "countUp";
	            }
	            else if(tmpWatt[i] == parseInt(json[i]["pwr"])){
	            	updown = "";
	            }
	            else
	            {
	            	updown = "countDown";
	            }
	            tmpWatt[i] = parseInt(json[i]["pwr"]);
	            
	            // update counter
	            $('.wattCounter_'+i).html("<span class='"+updown+"'>"+json[i]["pwr"]+" Watt</span>");               
	        }
	        
	        chart.redraw();
            
         
            
            // call it again after one second
            setTimeout(requestLiveData, interval);    
        },
        cache: false
    });
}
// Calculate costs/kwh function
function calculate(target, date){

			
		$.ajax({
			url: 'ajax.php?a=calculate_'+target+'&date='+date,
			dataType: 'json',
			success: function( jsonData ) {
				for(var i=0;i<jsonData.length;i++)
				{
					// KWH and costs counter
					if($('input[name=dualcount]:checked').val() == 1)
					{
						//$('#sidebar .'+target+' .kwhCounter').html("<span>H: "+jsonData["kwh"]+" kWh<br>L: "+jsonData["kwhLow"]+" kWh</span>");
						//$('#cpkwhCounter').html("<span>H: € "+jsonData["price"]+" <br>L: € "+jsonData["priceLow"]+"</span>");
						$('#sidebar .'+target+' .kwhCounter').append('<span><span class="nameheader">'+jsonData[i]['name']+':</span> <br>Laag: <span class="priceLow">€ '+jsonData[i]['priceLow']+'</span><span class="kwhLow">'+jsonData[i]['kwhLow']+' kWh</span><br>Hoog: <span class="price">€ '+jsonData[i]['price']+'</span><span class="kwh">'+jsonData[i]['kwh']+' kWh</span><br>Totaal: <span class="totalPrice">€ '+jsonData[i]['totalPrice']+'</span><span class="totalKwh">'+jsonData[i]['totalKwh']+' kWh</span></span>');
					}
					else
					{
						$('#sidebar .'+target+' .kwhCounter').append('<span><span class="nameheader">'+jsonData[i]['name']+':</span> <br><span class="price">€ '+jsonData[i]['price']+'</span><span class="kwh">'+jsonData[i]['kwh']+' kWh</span></span>');
					}	
					$('.sidebarLoading').remove();
				}
			},
			cache: false
		});	
}				
		
// Create chart function
function createChart(target, date){

			// Generate loading screen
			if(loadingEnabled)
			{
				historychart.showLoading();
			}
			else
			{
				loadingEnabled = true;
			}	
			$('#sidebar .'+target+' .kwhCounter').html("<span class='sidebarLoading'>Loading…</span>");
			
			var selected_meter = $('select[name=selected_meter]').val();						
							
			$.ajax({
				url: 'ajax.php?a='+target+'&date='+date,
				dataType: 'json',
				success: function( jsonData ) {

					// If invalid data give feedback
					if(jsonData["ok"] == 0)
					{
						$('#message').text(jsonData["msg"]);
						$('#overlay').fadeIn();
					}
					
						if(target == 'week')
						{
							var type = 'areaspline';
							var serieName = 'Watt';				
							var rangeSelector = true;
							var navScroll = true;
							var pointInterval = 60 * 1000;
							var tickInterval = null;
							var plotLinesX = [{
								value: jsonData[0].start + (24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: jsonData[0].start + (2 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: jsonData[0].start + (3 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: jsonData[0].start + (4 *24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: jsonData[0].start + (5 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							},{
								value: jsonData[0].start + (6 * 24 * 60 * 60 * 1000),
								width: 1, 
								color: '#c0c0c0'
							}];		
							var plotLinesY = [{
								value: 180,
								dashStyle : 'dash',
								width: 2, 
								color: 'green',
								label : {text : 'Sluip verbruik', align: 'right'}
							},{
								value: 500,
								dashStyle : 'dash',
								width: 2, 
								color: 'orange',
								label : {text : 'Normaal verbruik', align: 'right'}
							},{
								value: 3000,
								dashStyle : 'dash',
								width: 2, 
								color: 'red',
								label : {text : 'Hoog verbruik', align: 'right'}
							}];									
							var buttons = [{
											type: 'hour',
											count: 1,
											text: '1u'
										}, {
											type: 'hour',
											count: 12,
											text: '12u'
										}, {
											type: 'day',
											count: 1,
											text: 'dag'
										}, {
											type: 'week',
											count: 1,
											text: 'week'
										}];
						}
						else if(target == 'day')
						{
							var type = 'areaspline';
							var serieName = 'Watt';		
							var rangeSelector = true;
							var navScroll = true;
							var pointInterval = 60 * 1000;
							var tickInterval = null;
							var plotLinesY = [{
								value: 180,
								dashStyle : 'dash',
								width: 2, 
								color: 'green',
								label : {text : 'Sluip verbruik', align: 'right'}
							},{
								value: 500,
								dashStyle : 'dash',
								width: 2, 
								color: 'orange',
								label : {text : 'Normaal verbruik', align: 'right'}
							},{
								value: 3000,
								dashStyle : 'dash',
								width: 2, 
								color: 'red',
								label : {text : 'Hoog verbruik', align: 'right'}
							}];								
							var buttons = [{
											type: 'hour',
											count: 1,
											text: '1u'
										}, {
											type: 'hour',
											count: 6,
											text: '6u'
										}, {
											type: 'hour',
											count: 12,
											text: '12u'											
										}, {
											type: 'day',
											count: 1,
											text: 'dag'
										}];
						}
						else if(target == 'month')
						{
							var type = 'column';
							var serieName = 'kWh';
							var rangeSelector = false;
							var navScroll = false;
							var pointInterval = 24 * 60 * 60 * 1000;
							var tickInterval = 24 * 60 * 60 * 1000;
							var plotLines = null;
							var buttons = [];
						}			 		
						else if(target == 'year')
						{
							var type = 'column';
							var serieName = 'kWh';
							var rangeSelector = false;
							var navScroll = false;
							var pointInterval = 24 * 60 * 60 * 1000;
							var tickInterval = 24 * 60 * 60 * 1000;
							var plotLines = null;
							var buttons = [];
						}						
					
						// Create the chart
						historychart = new Highcharts.StockChart({
							chart : {
								renderTo : 'history',
								type: type,
								margin: [50, 20, 70, 50],	
								zoomType: 'x'
							},			
							credits: {
								enabled: false
							},	
							legend: {
								enabled: true
							},	
							title: {
								text: null
							},							
							yAxis:{
								showFirstLabel: false,
								title: {
									text: '',
									margin: 40
								},
								plotLines: plotLinesY
							},
							xAxis: {
								type: 'datetime',
								tickInterval: tickInterval,
								plotLines: plotLinesX,
								tickColor: 'green',
								tickLength: 10,								
								tickWidth: 3,
								tickPosition: 'inside'
							},	
							rangeSelector:{
								buttons: buttons,
								enabled: rangeSelector
							},							
							navigator:{
								enabled: navScroll
							},									
							scrollbar:{
								enabled: navScroll
							},
							series: []
						});						
					
					// Loop through meters, add to chart
					for(var i=0;i<jsonData.length;i++)
					{
						historychart.yAxis[0].setTitle({
				            text: jsonData[i].unit
				        });
																		
						var addseries = {
							id: i,
				            name: jsonData[i].name,
				            turboThreshold: 5000,
				            pointStart: jsonData[i].start,
				            pointInterval: pointInterval,
							tooltip: {
								pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y} '+jsonData[i].unit+'</b><br/>',
								valueDecimals: 0
							},
				            data: jsonData[i].val
			            }
						historychart.addSeries(addseries);						
						
						
					}
					calculate(target, date);
						
																
				},
    			cache: false
			});						
}		
	
$(document).ready(function() {

	// Dialogs (alerts)
	$('#closeDialog').click(function(){
		$('#overlay').hide();
		$('#dialog').removeClass();
		$('#dialog').addClass('default');
	});
	
	// Update notification
	if($('#update_notification').length > 0)
	{
		$('#update_notification').delay(2000).slideDown('slow').delay(5000).slideUp('slow');
	}
		
	// Settings
	$('#showSettings').click(function(){
		$('#settingsOverlay').slideDown();
	});
	$('#hideSettings').click(function(){
		$('#settingsOverlay').slideUp(function(){
			var dualcnt = $('input[name=dualcount]:checked').val();
			if(dualcnt != $('#settingsOverlay').data('dualcount'))
			{
				$('input[name=dualcount]').not(':checked').attr('checked', true);
				if($('#settingsOverlay').data('dualcount') == 1)
				{
					$('.cpkwhlow').show();
				}
				else
				{
					$('.cpkwhlow').hide();
				}
			}		
		});		
	});
	
	// Change settings tab
	$('#settingsMenu .btn li a').click(function(){
		var tab = $(this).data('settingstab');
		$('.settingsTab').hide();
		$('#'+tab).show();
		
		$('#settingsMenu .btn li').each(function(){
			$(this).removeClass('selected');
		});
		$(this).parent().addClass('selected');

		//console.log(chart);
	});
	
	$('input[name=dualcount]').change(function(){
		var dualcnt = $('input[name=dualcount]:checked').val();
		if(dualcnt == 1)
		{
			$('.cpkwhlow').show();
		}
		else
		{
			$('.cpkwhlow').hide();
		}
	});
	
	$('.runUpdate').click(function(){
		$('#overlay').fadeIn();	
		$('#message').text('Updating...');
		$.ajax({
			url: 'ajax.php?a=getUpdate',
			success: function( data ) {			
				$('#message').text(data["msg"]);		
			}
		});			
		return false;
	});		
	
	$('.viewChangelog').click(function(){
		$('#overlay').fadeIn();	
		$('#message').text('Loading...');
		$.ajax({
			url: 'ajax.php?a=getChangelog',
			success: function( data ) {			
				$('#dialog').removeClass('default');				
				$('#dialog').addClass('size500x250');
				$('#message').html(data);		
			}
		});			
		return false;
	});	
	
	// Delete meter
	$('.delMeter').click(function(){
		var meter = $(this).data('meter');	
		$.ajax({
			url: 'ajax.php?a=delMeter',
			type: 'POST',
			dataType: 'json',
			data: { meter: meter },
			success: function( data ) {							

				$('#message').text(data["msg"]);
				$('#overlay').fadeIn();			
			}
		});			
		return false;			
	});
	
	// Add meter
	$('.addMeter').click(function(){
		var rows = $('.meter_row input[name=meter_key]').length;
		if(rows == 0){
			$('#settingsMeters tr:first').after('<tr class="meter_row"><td><input type="hidden" name="meter[0][id]" value=""/><input type="hidden" name="meter_key" value="0"/><input type="text" name="meter[0][name]" value=""/></td><td><input type="text" name="meter[0][address]" value=""/></td><td><input type="text" name="meter[0][password]" value=""/></td><td><a class="smallBtn delBtn">Verwijder</a></td></tr>');
		}
		else
		{
			var key = parseInt($('.meter_row:last input[name=meter_key]').val())+1;
			$('.meter_row:last').after('<tr class="meter_row"><td><input type="hidden" name="meter['+key+'][id]" value=""/><input type="hidden" name="meter_key" value="'+key+'"/><input type="text" name="meter['+key+'][name]" value=""/></td><td><input type="text" name="meter['+key+'][address]" value=""/></td><td><input type="text" name="meter['+key+'][password]" value=""/></td><td><a class="smallBtn delBtn">Verwijder</a></td></tr>');
		}
		return false;
	});
		
	$('#saveSettings').click(function(){
		$.ajax({
			url: 'ajax.php?a=saveSettings',
			type: 'POST',
			dataType: 'json',
			data: $('#settingsOverlay form').serialize(),
			success: function( data ) {

				$('#settingsOverlay').slideUp('fast', function(){
					$('#settingsOverlay input[type=password]').val('');
				});
				
				if($('#settingsOverlay').data('dualcount') != $('input[name=dualcount]:checked').val())
				{
					$('#settingsOverlay').data('dualcount', $('input[name=dualcount]:checked').val());	
					var chart = $('#history').data('chart');
					calculate(chart, $('#datepicker').val());					
				}
				$('#settingsOverlay').data('liveinterval', $('select[name=liveinterval]').val());								

				$('#message').text(data["msg"]);
				$('#overlay').fadeIn();			
			}
		});			
		return false;
	});	
	
	// Show chart
	$('.showChart').click(function(){
		var chart = $(this).data('chart');
		$('.chart').hide();
		$('.'+chart).show();
		
		$('#menu .btn li').each(function(){
			$(this).removeClass('selected');
		});
		$(this).parent().addClass('selected');
		$('#history').data('chart', chart);
		
		if(chart != 'live')
		{
			createChart(chart, $('#datepicker').val());		
		}
		//console.log(chart);
	});
	
	
	//Highcharts options
	Highcharts.setOptions({
		global: {
			useUTC: false
		},	
		lang: {
			decimalPoint: ',',
			months: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
			shortMonths: ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
			weekdays: ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag']
		}			
	});
	
	// Live chart
    chart = new Highcharts.Chart({
        chart: {
            renderTo: 'live',
            defaultSeriesType: 'areaspline',
			margin: [50, 20, 70, 50],
            events: {
                load: requestLiveData
            }
        },         
		credits: {
			enabled: false
		},
		legend: {
			enabled: true,
			margin: 40
		},		      
        title: {
            text: null
        },
        xAxis: {
            type: 'datetime',
            tickPixelInterval: 150,
            minRange: 60 * 1000
        },
        yAxis: {
			showFirstLabel: false,
            minPadding: 0.2,
            maxPadding: 0.2,
            title: {
                text: 'Watt',
                margin: 10
            },
			plotLines : [{
				value : 170,
				color : 'green',
				dashStyle : 'dash',
				width : 2,
				label : { text : 'Sluip verbruik', align: 'right'}
			}, {
				value : 500,
				color : 'orange',
				dashStyle : 'dash',
				width : 2,
				label : { text : 'Normaal verbruik', align: 'right'}
			}, {
				value : 3000,
				color : 'red',
				dashStyle : 'dash',
				width : 2,
				label : { text : 'Hoog verbruik', align: 'right'}
				}]
        },
        series: [],
		exporting: {
			enabled: false
		}		
    });  
	
		
	// Datepicker
	$('#datepicker').datepicker({
		inline: true,
		dateFormat: 'yy-mm-dd',
		maxDate: new Date(),
		showOn: 'focus',
		//changeMonth: true,
		//changeYear: true,	
		firstDay: 1,	
		monthNames: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
        monthNamesShort: ['jan', 'feb', 'maa', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
        dayNames: ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'],
        dayNamesShort: ['zon', 'maa', 'din', 'woe', 'don', 'vri', 'zat'],
        dayNamesMin: ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'],
		onSelect: function(date, inst){
			var target = $('#history').data('chart');			
			createChart(target, date);
		}		
	});
			
	      
});