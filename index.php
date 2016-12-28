<?php
date_default_timezone_set('America/New_York');
$now = time() ;
$feed = file_get_contents("http://realtime.ripta.com:81/api/vehiclepositions?format=json") ;
$trips_feed = "https://transitfeeds.com/p/rhode-island-public-transit-authority/363/latest/download/trips.txt" ;
$stops_feed = "https://transitfeeds.com/p/rhode-island-public-transit-authority/363/latest/download/stops.txt" ; 
$runs = json_decode($feed, true) ;
$time = $runs['header']['timestamp'] ;
$routecheck = $_REQUEST['routecheck'] ; // this is the RIPTA route number, e.g. "66"
$tripcheck = $_REQUEST['tripcheck'] ; // this is the particular scheduled trip on a route
$refreshcheck = $_REQUEST['refreshcheck'] ; // should I refresh this page every 30 seconds?
$single = $_REQUEST['single'] ; // view only a single run, with map
$view_all = $_REQUEST['view_all'] ; // view all buses near you
$about = $_REQUEST['about'] ; // view the about page
$myurl = $_SERVER['PHP_SELF'] ;
$refresh = '' ;
$line_break = '' ;
$route_ids = array() ;
$stops = array() ;
$trips = array() ;
$headers = array() ;

if($single == "yes" && $refreshcheck != "no"){
    $refresh = "<meta http-equiv=\"refresh\" content=\"30\">" ;
}

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Unofficial RIPTA Bus Tracker</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php echo $refresh ; ?>
		<style>
			body {
				font-size : 16px ;
				margin : 0px ; 
				font-family : Helvetica Neue ; 
			}
			.radio {
				border : 0px solid red ; 
				width : 40px ;
				margin-right : 20px ; 
				font-family : "Courier New", Courier, monospace ;
				font-size : 8vw ; 
			}
			#map {
			  height : 100% ; 
              min-height: 500px;
              width: 100%;
			}	
			
			div.listing {
				width : 250px ; 
				float : left ; 
				margin : 20px ; 
				border : 1px solid #505050 ; 
				font-size : 1.2em ;
				box-shadow : 2px 2px 5px #999 ; 
				border-radius : 9px ; 
			}
			.route {
				padding : 10px ; 
				background-color : #505050 ;
				color : #fff ; 
				border-radius : 8px 8px 0px 0px ; 
				font-size : 1.4em ; 				
			}
			.route a {
				color : #fff ; 
				text-decoration : none ; 
			}
			.origin, .box_content {
				padding : 10px ; 
			}			
			.view_link {
				padding : 0px 10px ; 
			}			
			.view_link a {
				padding : 10px ;
				border-radius : 8px ; 
				display : block ; 
				color : #fff ;
				text-decoration : none ; 
				text-align : center ;
				box-shadow : 2px 2px 4px #999 ; 
				transition: background-color 0.5s ease;
				background-color: green;
			}
			.view_link a:hover {
			  background-color: gold;
			  color : #000 ;
			}
			
			.view_link a:active {
				padding : 12px ; 
			}
			#content {
				padding : 20px ; 
			}
			

			#navbar {
				width : 100% ;
				height : 30px ;
				background-color : lightblue ;
				padding : 0px 10px ; 
			}
			#navbar ul {
				margin : 0px ;
				padding : 5px 0px 0px 0px ; 
				text-align : center ;
			}
			#navbar ul li {
				display : inline ; 
			}
			#navbar ul li a {
				color : navy ;
				text-decoration : none ;
				margin-right : 6% ;  
				font-size : .85em ;
			}
			@media (max-width: 500px) {
			  #navbar {
			    font-size : 3vw ;
			  }
			  #navbar ul {
			  	padding-top : .6em ;
			  }
			}
			.route_number a {
				display : block ;
				float : left ;
				background-color : #505050 ;
				color : #fff ;
				text-decoration : none ;
				font-size : 1.8em ; 
				padding : 15px ; 
				border-radius : 8px ; 
				margin : 10px ; 
				width : 6vw ; 
				min-width : 50px ; 
				text-align : center ; 
			}
			.single_full {
				border : 1px solid ; 
				border-radius : 8px ; 
				margin : 0px 20px ; 
			}
			
			.single_full .single_route_header {
				background-color : #505050 ;
				padding : 5px 10px ;
				color : #fff ;
				font-size : 1.4em ;
				font-weight : bold ;
				border-radius : 5px 5px 0px 0px ;
			}
			
			.single_full .single_content {
				padding : 20px ;
			}
			
			.single_full .single_text {
				margin-bottom : 0px ; 
			}
			
			a.refresh_message {
				display : block ;
				float : right ; 
				padding : 10px ; 
				background-color : red ;
				border-radius : 8px ;
				color : #fff ;
				text-align : center ;
				margin-left : 25px ; 
				margin-right : 10px ; 
				text-decoration : none ; 
			}
			#timer { 
				font-weight : bold ; 
			}
			
/* 
Pure CSS Pie Timer by Hugo Giraudel https://css-tricks.com/css-pie-timer/
 */
 
 .wrapper {
   position: relative;
   background: white;
 }
 
 .wrapper, .wrapper * {
   -moz-box-sizing: border-box;
   -webkit-box-sizing: border-box;
   box-sizing: border-box;
 }
 
 .wrapper {
   width: 20px;
   height: 20px;
 }
 
 .wrapper .pie {
   width: 50%;
   height: 100%;
   transform-origin: 100% 50%;
   position: absolute;
   background: #000;
 }
 
 .wrapper .spinner {
   border-radius: 100% 0 0 100% / 50% 0 0 50%;
   z-index: 200;
   border-right: none;
   animation: rota 35s linear infinite;
 }
 
 .wrapper:hover .spinner,
 .wrapper:hover .filler,
 .wrapper:hover .mask {
   animation-play-state: running;
 }
 
 .wrapper .filler {
   border-radius: 0 100% 100% 0 / 0 50% 50% 0;
   left: 50%;
   opacity: 0;
   z-index: 100;
   animation: opa 35s steps(1, end) infinite reverse;
   border-left: none;
 }
 
 .wrapper .mask {
   width: 50%;
   height: 100%;
   position: absolute;
   background: inherit;
   opacity: 1;
   z-index: 300;
   animation: opa 35s steps(1, end) infinite;
 }
 
 @keyframes rota {
   0% {
     transform: rotate(0deg);
   }
   100% {
     transform: rotate(360deg);
   }
 }
 @keyframes opa {
   0% {
     opacity: 1;
   }
   50%, 100% {
     opacity: 0;
   }
 }
/* End Pure CSS pie timer */
			
		</style>
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
		
		  ga('create', 'UA-17909458-4', 'auto');
		  ga('send', 'pageview');
		
		</script>
	</head>
<body>
<div id="navbar">
	<ul>
		<li><a href="https://kerri.is/coding/ripta/index.php">Choose a route</a></li>
		<li><a href="https://kerri.is/coding/ripta/index.php?view_all=yes">Show all buses near me</a></li>
		<li><a href="https://kerri.is/coding/ripta/index.php?about=yes">About & Feedback</a></li>
	</ul>
</div>


<div id="content">
<?php
// show all the routes in action
if($routecheck == '' && $single == '' && $view_all !='yes' && $about !='yes') {
	foreach($runs['entity'] as $chunk){
		$route_id = $chunk['vehicle']['trip']['route_id'] ;
		$all_routes = array_push($route_ids, $route_id) ;
	}
	$route_ids = array_unique($route_ids) ; 
	sort($route_ids) ; 
	$display_count = 1 ;
	foreach($route_ids as $route_id) {

	    $route_id_display = sprintf("% 2d", $route_id) ;
	    
	    if($route_id == '11'){
	        $route_id_display = 'RL' ;
	    }else{
	        $route_id_display = $route_id ;
	    }

		$routes .= "<div class=\"route_number\"><a href=\"?routecheck=" . $route_id . "\">" . $route_id_display . "</a></div>" ;

	} 
	echo "<h1>Unofficial RIPTA Bus Tracker</h1><h2>Choose a route</h2>" ;
	echo $routes ;
} 

// ---------------------------------------------------------------------------------------
// show all current runs of one route
elseif($single == '' && $routecheck !='') {
	if (($handle = fopen($stops_feed, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
			$stops[$data[0]] = $data[2] ;		
		}
		fclose($handle) ;
	}	
	if (($handle = fopen($trips_feed, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {	
			$trips[$data[2]] = $data[4] ;
			$headers[$data[2]] = $data[3] ;		
		}
		fclose($handle) ;
	}
	foreach($runs['entity'] as $chunk){
		$bus = $chunk['vehicle'] ;
		$start_time = strtotime($bus['trip']['start_time']) ;
// 		$start_time = date("h:i a", $start_time) ;
		if($start_time < date("U")) {
			$outbound_is_was = "<span style=\"color : orange ; \">Was scheduled to leave KP at " ;
			$inbound_is_was = "<span style=\"color : orange ; \">Was scheduled first stop at " ;
		} else {
			$outbound_is_was = "<span style=\"color : green ; \">Is scheduled to leave KP at " ;
			$inbound_is_was = "<span style=\"color : green ; \">Scheduled first stop is at " ;
		}

		$start_time = date("g:i a", $start_time) ;
		
		if($bus['trip']['route_id'] == $routecheck) {		
			$trip_id = $bus['trip']['trip_id'] ;			
			if($trips[$trip_id] == 0) {
				$inout = "Inbound &larr;" ;
				$origin = " <div class=\"origin\">(" . $inbound_is_was . $start_time . "</span>)</div>" ;
			} elseif ($trips[$trip_id] == 1) {
				$inout = "Outbound &rarr;" ;
				$origin = " <div class=\"origin\">(" . $outbound_is_was . $start_time . "</span>)</div>" ;
			}	
				
			$display_block .= "<div class=\"listing\">
				<div class=\"route\"><a href=\"index.php?single=yes&tripcheck=" . $bus['trip']['trip_id'] . "\"><b>" . $bus['trip']['route_id'] . " " . $inout . "</b></a>" . "</div><div class=\"inout\">" . $origin . "</div>" ;
				
			$display_block .= "<div class=\"view_link\"><a href=\"index.php?single=yes&tripcheck=" . $bus['trip']['trip_id'] . "\">View this trip</a></div>" ;
			
			$display_block .= "<div class=\"box_content\">TO " . $headers[$trip_id] . "<br />" ;						
			
			if (date('Y-m-d') == date('Y-m-d', $bus['timestamp'])) {
				$display_block .= "Last update: " . date('g:i a', $bus['timestamp']) . " today<br /> ";
			} else {
				$display_block .= "Last update: " . date('g:i a, l, j M Y', $bus['timestamp']) . "<br /> ";
			}
			
			$display_block .= "Bus number: " . str_pad($bus['vehicle']['label'], 4, '0', STR_PAD_LEFT) . "<br />" ;			
// 			if ($bus['current_status'] == '0')
// 			{
// 				$display_block .= "Leaving now: " ;
// 			}
// 			elseif ($bus['current_status'] == '1')
// 			{
// 				$display_block .= "Currently stopped at: " ;
// 			}
// 			else
// 			{
// 				$display_block .= "Next stop: " ;
// 			}						
// 			$display_block .= $stops[$bus['stop_id']] . "<br />" ;
			$speed = round($bus['position']['speed']) ;
			$display_block .= "Current speed: $speed mph <br />" ;		
			$mapURL = "https://www.google.com/maps/place/" . $bus['position']['latitude'] . ',' . $bus['position']['longitude'] ;
			$display_block .= '</div></div>';
		}
	}	
	$lastUpdate = date('g:i a, l, j M Y', $time);
	$display_block .= "<div style=\"clear : both ; \"><i>Data last updated: " . $lastUpdate . "</i><br /></div>" ;
	echo $display_block ;
} 

// ---------------------------------------------------------------------------------------
// show this one bus
elseif($single != ''){
	if (($handle = fopen($stops_feed, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
			$stops[$data[0]] = $data[2] ;		
		}
		fclose($handle) ;
	}	
	if (($handle = fopen($trips_feed, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {	
			$trips[$data[2]] = $data[4] ;
			$headers[$data[2]] = $data[3] ;		
		}
		fclose($handle) ;
	}	
	foreach($runs['entity'] as $chunk){
		$bus = $chunk['vehicle'] ;
		if($bus['trip']['trip_id'] == $tripcheck) {		
			$trip_id = $bus['trip']['trip_id'] ;
			$latitude = $bus['position']['latitude'] ;
			$longitude = $bus['position']['longitude'] ;		
			
			if($trips[$trip_id] == 0) {
				$inout = "Inbound" ;
				$bus_icon = "https://kerri.is/coding/ripta/indigo_bus.svg" ;
			}elseif($trips[$trip_id] == 1) {
				$inout = "Outbound" ;
				$bus_icon = "https://kerri.is/coding/ripta/green_bus.svg" ;
			}
			
			$show_map_script = "
			<div id=\"map\"></div>
			<script>
			  function initMap() {
			    var coordinates = {
			    	lat: $latitude, 
			    	lng: $longitude
			    };
			    var map = new google.maps.Map(document.getElementById('map'), {
					zoom: 16,
					center: coordinates
			    });
			    var marker = new google.maps.Marker({
			      position: coordinates,
			      map: map,
			      icon: '" . $bus_icon . "'
			    });
			    var trafficLayer = new google.maps.TrafficLayer();
			            trafficLayer.setMap(map);
			  }
			</script>
			<script async defer src=\"https://maps.googleapis.com/maps/api/js?key=AIzaSyBZuazf752MqPpWsIpXCnw7JSu1yNfg3lg&callback=initMap\">
			</script>" ;			
			
			$display_block .= "<div class=\"single_full\"><div class=\"single_route_header\">Route: " . $bus['trip']['route_id'] . " " . $inout . "</div><div class=\"single_content\">" ;						

			if($single == "yes" && $refreshcheck != "no"){
				$refresh_message = "<div style=\"float : right ; margin-top : 15px ; \">
					<!--  
					Pure CSS Pie Timer by Hugo Giraudel https://css-tricks.com/css-pie-timer/
					-->
					<div class=\"wrapper\">
					  <div class=\"spinner pie\"></div>
					  <div class=\"filler pie\"></div>
					  <div class=\"mask\"></div>
					</div>
					</div>
					<a class=\"refresh_message\" style=\"background-color : red ; \" href=\"index.php?single=yes&tripcheck=" . $trip_id . "&refreshcheck=no\">Stop 30-second<br />autorefresh</a> 	" ;
			}else{
				$refresh_message = "<a class=\"refresh_message\" style=\"background-color : green ; \" href=\"index.php?single=yes&tripcheck=" . $trip_id . "\">Automatically update this page<br />every 30 seconds</a><br />" ;
			}

			$data_age = $now - $bus['timestamp']  ;

			$display_block .= "<script>
			var timerVar = setInterval(countTimer, 1000);
			var totalSeconds = $data_age ;
			function countTimer() {
			++totalSeconds;
			document.getElementById(\"timer\").innerHTML = totalSeconds;
			}
			</script>" ;

			$display_block .= "<div class=\"single_text\">" .$refresh_message . "This bus location was last updated <span id=\"timer\"></span> seconds ago.<br />" ; 		

			$display_block .=  "<b>Bus number:</b> " . str_pad($bus['vehicle']['label'], 4, '0', STR_PAD_LEFT) . "<br />" ;	

// Next stop not working properly anymore					
// 			if ($bus['current_status'] == '0')
// 			{
// 				$display_block .=  "Leaving: " ;
// 			}
// 			elseif ($bus['current_status'] == '1')
// 			{
// 				$display_block .=  "Currently stopped at: " ;
// 			}
// 			else
// 			{
// 				$display_block .=  "Next stop: " ;
// 			}					
// 				
// 			$display_block .=  $stops[$bus['stop_id']] . "<br />" ;

			$speed = round($bus['position']['speed']) ;

			$display_block .=  "<b>Current speed:</b> $speed mph </div>" ;
			

			
            $display_block .=  "<br />" . $show_map_script . "</div></div>";	
			
			echo $display_block ; 
		}
	}	
}

// ---------------------------------------------------------------------------------------
// view all buses near me 
if($view_all == "yes"){
if (($handle = fopen($trips_feed, "r")) !== FALSE) {
	while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {	
		$trips[$data[2]] = $data[4] ;
		$headers[$data[2]] = $data[3] ;		
	}
	fclose($handle) ;
}
        $javascript_vars = "<script type='text/javascript'>
             function initMap() {
             	var pos, map;
				function post_init() {
					var infowindow = new google.maps.InfoWindow({});
					var marker, i;
					for (i = 0; i < locations.length; i++) {
						marker = new google.maps.Marker({
							position: new google.maps.LatLng(locations[i][1], locations[i][2]),
							map: map,
							icon: locations[i][3]
						});
						google.maps.event.addListener(marker, 'click', (function (marker, i) {
							return function () {
								infowindow.setContent(locations[i][0]);
								infowindow.open(map, marker);
							}
						})(marker, i));
					}
					var trafficLayer = new google.maps.TrafficLayer();
					        trafficLayer.setMap(map);
				}
				// error checking in wrong place -- if browser has functionality, will not fail over -- need to fail over if you don't get values, instead
				if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(function(position) {
						pos = {
							lat: position.coords.latitude,
							lng: position.coords.longitude
						}
						map = new google.maps.Map(document.getElementById('map'), {
							zoom: 13,
							center: new google.maps.LatLng(pos.lat, pos.lng)
						});
						post_init();
					});
				} else {
					pos = {
						lat: '41.827882',
						lng: '-71.400897'
					};
					map = new google.maps.Map(document.getElementById('map'), {
						zoom: 13,
						center: new google.maps.LatLng(pos.lat, pos.lng)
					});
					post_init();
				}			
			}
		";
        $javascript_locations = "var locations = [" ;
        $count = 0 ;

    foreach($runs['entity'] as $chunk){
		$bus = $chunk['vehicle'] ;
		$route_id = $chunk['vehicle']['trip']['route_id'] ;
		if($route_id == '11'){
		    $route_id = 'R-Line' ;
		}
        $trip_id = $bus['trip']['trip_id'] ;
//         $destination = "<br />Towards " . $headers[$trip_id]  ;
// $destination = "Trip ID is " . $trip_id . addslashes($headers[trip_id]) ;
		if($trips[$trip_id] == 0) {
			$inout = "Inbound" ;
			$bus_icon = "https://kerri.is/coding/ripta/indigo_bus.svg" ;
		} elseif ($trips[$trip_id] == 1) {
			$inout = "Outbound" ;
			$bus_icon = "https://kerri.is/coding/ripta/green_bus.svg" ;
		}	
        $latitude = $bus['position']['latitude'] ;
        $longitude = $bus['position']['longitude'] ;	
        
        $javascript_vars .= "
                var k" . $count . " = {
                info: '<a style=\"text-decoration : none ; color : blue; font-weight : bold ; font-size : 1.2em ; \" href=\"https://kerri.is/coding/ripta/index.php?single=yes&tripcheck=" . $trip_id . "\"> ". $route_id . " " . $inout . "</a><br />Towards " . addslashes($headers[$trip_id])  . " ',
                lat: " . $latitude . ",
                long: " . $longitude . ",
                icon: '" . $bus_icon . "'
            };
            " ;

        $javascript_locations .= "
        [k" . $count . ".info, " . "k" . $count . ".lat, " . "k" . $count . ".long, " . "k" . $count . ".icon" . "],
        " ;
        ++$count ;
    }
    
    $javascript_locations .= "]";
    
    $javascript_full =  $javascript_vars . $javascript_locations . "    
" ;

echo $javascript_full . "</script>

<script async defer src=\"https://maps.googleapis.com/maps/api/js?key=AIzaSyBZuazf752MqPpWsIpXCnw7JSu1yNfg3lg&callback=initMap\"></script>

<div id=\"map\"></div>" ;
}

// ---------------------------------------------------------------------------------------
// about
if($about == "yes"){
	echo "<div id=\"about\">
	<p>This RIPTA bus tracker is not an official publication of RIPTA. It was made by Kerri Hicks (feat. Rich Siegel & Seth Dillingham). <a href=\"https://kerri.is/\">She likes making neat things</a>. So do <a href=\"http://www.barebones.com\">Rich</a> and <a href=\"http://www.truerwords.net/\">Seth</a>.</p>
	
	<p>You can <a href=\"https://github.com/kerri-hicks/RIPTA_bus_tracker\">check out the code on GitHub</a> and run your own RIPTA bus tracker, if you want. Also, feel free to fork the project and do your thing.</p></div>

	<p>I'd love some <a href=\"https://docs.google.com/forms/d/1p7BqEbE-t-fvTE0fzPLqeNXEOsiTQveQeRuzbe5oeAM/\">feedback</a> if you'd be willing to share.</p>
	" ;
}

/* 
Google API key (restricted to kerri.is)
AIzaSyBZuazf752MqPpWsIpXCnw7JSu1yNfg3lg

data source for the stops:
https://transitfeeds.com/p/rhode-island-public-transit-authority/363/20160822/file/stops.txt

Route metadata is available here:
https://transitfeeds.com/p/rhode-island-public-transit-authority/363/20160822/file/routes.txt
*/
?>
</div>
</body>
</html>
