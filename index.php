<?php

/*
 * Mapbox ID and access_token needed
 */

$mapboxId = 'INSERT_ID_HERE';
$mapboxAccessToken = 'INSERT_ACCESSTOKEN_HERE';



if(isset($_GET['file']) && $_GET['file'] != "") {
	$track = $_GET['file'];
}
else {
	$track = "sample.gpx";
}

if (! file_exists("tracks/$track")) {
	$track = "sample.gpx";	
}


// oembed FTW
if (isset($_GET['action']) && $_GET['action'] == "oembed") {
	if (!isset($_GET['url']) || $_GET['url'] == "") {
		echo "Missing parameter: url";
		exit;
	}

    // get the file name from the url
    parse_str(parse_url($_GET['url'], PHP_URL_QUERY), $query);
    $track = $query["file"];
    
    // Retrieve max width and height
    $maxwidth = isset($_GET['maxwidth']) ? intval($_GET['maxwidth']) : 0;
    if($maxwidth == 0) {
        $maxwidth = 620;
    }
    $maxheight = isset($_GET['maxheight']) ? intval($_GET['maxheight']) : 0;
    if($maxheight == 0) {
        $maxheight = 349;
    }

	$response = array("author_name" => "Jan Vonde",
			  "author_url" => "http://blog.pregos.info",
			  "cache_age" => "86400",
			  "html" => "<iframe width='$maxwidth' height='$maxheight' src='" . str_replace('index.php', 'embed.php', curPageURL()) . "?file=$track' class='pregoTrack' frameborder='0'></iframe>",
			  "provider_name" => "pregos tracks",
			  "provider_url" => "http://github.com/janvonde/pregos-tracks",
			  "title" => "$track",
			  "type" => "rich",
			  "version" =>"1.0");

	header('Content-Type: application/json');
	echo json_encode($response);
	exit;
}


// initial zoom
$xml = simplexml_load_file("tracks/$track");
$lat = $xml->trk->trkseg->trkpt[count($xml->trk->trkseg->trkpt)-1][lat];
$lon = $xml->trk->trkseg->trkpt[count($xml->trk->trkseg->trkpt)-1][lon];


// height graph
$allEles = array();
$i = 1;
foreach($xml->trk->trkseg->trkpt as $trkpt) {
	$allEles[] = array($i,$trkpt->ele->__toString());
	$i++;
}


// speed graph
$hideSpeed = 1;
$allSpeed = array();
$j = 1;
if (isset($xml->trk->trkseg->trkpt->extensions)) {
	foreach($xml->trk->trkseg->trkpt as $trkpt) {
		$namespaces = $trkpt->getNamespaces(true);
		if (array_key_exists('gpx10', $namespaces)) {
			$hideSpeed = 0;
			$gpx10 = $trkpt->extensions->children($namespaces['gpx10']);
			// * 3.6 as gpx10:speed is in m/s and not km/h
			$speed = (string) $gpx10->speed * 3.6;
			$allSpeed[] = array($j,$speed);
			$j++;
		}
	}
}



// determine page url for oembed
function curPageURL() {
	$isHTTPS = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on");
	$port = (isset($_SERVER["SERVER_PORT"]) && ((!$isHTTPS && $_SERVER["SERVER_PORT"] != "80") || ($isHTTPS && $_SERVER["SERVER_PORT"] != "443")));
	$port = ($port) ? ':'.$_SERVER["SERVER_PORT"] : '';
	$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
	$url = ($isHTTPS ? 'https://' : 'http://').$_SERVER["SERVER_NAME"].$port.$uri_parts[0];
	return $url;
}

?>
<!DOCTYPE html>
<html>

<head>
	<title>pregos tracks</title>
	<meta charset="utf-8" />
	<meta http-equiv="refresh" content="600">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />

	<link rel="alternate" type="application/json+oembed" href="<?php echo curPageURL(); ?>?action=oembed&url=<?php echo urlencode(curPageURL()) . "&file=" . $track; ?>" title="pregos tracks - <?php echo $track; ?>" />

	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css" />
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/css/bootstrap.min.css" />
	<link href='//fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>

	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/js/bootstrap.min.js"></script>
	<script src="js/slidx.js"></script>
	<script src="js/gpx.js"></script>


	<style>
		body {
			padding: 0;
			margin: 0;
			font-family: 'Open Sans', sans-serif;
		}

		html, body, #map {
			height: 100%;
		}

		a, a:hover {
			color: #FFFFFF;
		}

		.graph-container {
			box-sizing: border-box;
			width: 312px;
			height: 162px;
			padding: 5px;
			margin: 15px auto 30px auto;
			border: 1px solid #ddd;
			background: #fff;
			background: linear-gradient(#f6f6f6 0, #fff 50px);
			box-shadow: 0 3px 10px rgba(0,0,0,0.15);
		}

		.graph-placeholder {
			width: 100%;
			height: 100%;
			font-size: 14px;
			line-height: 1.2em;
		}

		.modal {
			color: #FFFFFF;
		}

		.modal-content {
			border-radius: 1px;
			background-color: #34495e;
		}

		.close {
			color: #FFFFFF;
		}

		.btn {
			border-radius: 3px;
		}

		.statstable {
			display: table;
		}

		.statstable div {
			display: table-row;
		}

		.statstable div div:first-child {
			display: table-cell;
			text-align: right;
			padding-right: 7px;
		}

		.statstable div div:last-child {
			display: table-cell;
			font-weight: bold;
		}

		#buttonsforstuff {
			margin-bottom: 20px;
		}

		#buttonsforstuff button:last-child {
			margin-left: 10px;
		}

		#ajaxloaderonupload {
			float: right;
			border: 3px solid white;
			border-radius: 3px;
			background-color:white;
		}

		#slidx-menu { 
			background-color: #34495E; 
			color: #FFFFFF;
		}

		#slidx-menu .content {
			padding-left:10px;

		}

		#slidx-button {
			padding: 10px 30px;
			border-radius: 3px;
			margin: 20px 20px 0px 0px;
			font-weight: bold;
		}

		#fileSelectForm {
			margin-right: 6px;
		}
	</style>
</head>


<body id="gpx">



<!-- SIDEBAR -->
<a id="slidx-button" class="btn btn-default btn-lg">Info</a>

<div id="slidx-menu">
	<div class="content">
	<h2><span class="trackName"><?php echo $track; ?></span></h2>

		<div class="statstable">
			<div>
				<div>Distanz:</div>
				<div><span class="distance"></span> m</div>
			</div>
			<div>
				<div>Start:</div>
				<div><span class="start"></span> Uhr</div>
			</div>
			<div>
				<div>Ende:</div>
				<div><span class="end"></span> Uhr</div>
			</div>
			<div>
				<div>Dauer:</div>
				<div><span class="duration"></span> h</div>
			</div>
			<div>
				<div>Geschwindigkeit:</div>
				<div><span class="speed"></span> km/h</div>
			</div>
			<div>
				<div>Download:</div>
				<div><a href="tracks/<?php echo $track; ?>" target="_blank">GPX</a></div>
			</div>
		</div>

		<h3> Höhenprofil </h3>
		<div class="graph-container">
			<div id="graph-height" class="graph-placeholder"></div>
		</div>
<?php
if ($hideSpeed == 0) {
?>

		<h3> Geschwindigkeit </h3>
		<div class="graph-container">
			<div id="graph-speed" class="graph-placeholder"></div>
		</div>
<?php
}
?>

		<h3> Datei </h3>
		<div id="fileSelectForm">
			<form action="index.php" method="GET" class="form-inline">
				<select name="file" onchange="this.form.submit()" class="form-control">
<?php
$path = "tracks/";
$handle=opendir($path);

while($file = readdir($handle)) {
	if (substr($file,0,1) != ".") {
		$afile[]=$file;
	}
}
closedir($handle); 

rsort($afile);

$anz=count($afile);
for($i=0;$i<$anz;$i++) {
	echo "					<option ";
	if ($track == $afile[$i]) { echo "selected "; }
	echo "value='" . $afile[$i] . "'>" . $afile[$i] . "</option> \n";
}  
?>
				</select>
			</form>
		</div>

		<br />

		<div id="buttonsforstuff">
			<button id="uploadbutton" type="button" class="btn btn-default" data-toggle="modal" data-target="#myModal-upload">
				Upload
			</button>

			<button type="button" class="btn btn-default" data-toggle="modal" data-target="#myModal">
				&Uuml;ber
			</button>
		</div>
	</div>
</div>
<!-- END SIDEBAR -->



<div id="map"></div>



<!-- INFO MODAL -->
<div class="modal" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">&Uuml;ber</h4>
			</div>
			<div class="modal-body">
				<a href="http://www.wtfpl.net/" target="_blank"><img src="img/wtfpl.png" alt="WTFPL" class="pull-right"/></a>
				<p>Idee und Umsetzung 2015 von <a href="https://blog.pregos.info/" target="_blank">Jan Vonde</a></p>

				<p>Verwendete Projekte / Seiten</p>
				<ul>
					<li><a href="http://leafletjs.com/" target="_blank">Leaflet</a></li>
					<li><a href="https://github.com/mpetazzoni/leaflet-gpx" target="_blank">GPX plugin for Leaflet</a></li>
					<li><a href="http://momentjs.com/" target="_blank">Moment.js</a></li>
					<li><a href="http://www.flotcharts.org/" target="_blank">Flot</a></li>
					<li><a href="http://getbootstrap.com/" target="_blank">Bootstrap</a></li>
					<li><a href="http://www.mapbox.com/" target="_blank">Mapbox</a></li>
					<li><a href="http://www.oembed.com/" target="_blank">oEmbed</a></li>
					<li><a href="http://www.jqueryscript.net/menu/Super-Simple-jQuery-Sidebar-Sliding-Menu-Plugin-Slidx.html" target="_blank">Slidx</a></li>
					<li><a href="https://www.iconfinder.com/icons/211081/gps_landmark_location_map_marker_navigation_pin_icon" target="_blank">GPS Icon / Two Tone Design Set</a></li>
					<li><a href="http://wp.misterunknown.de/blog/2013/11/fileupload-per-ajax.html" target="_blank">Fileupload per AJAX</a></li>
					<li><a href="http://ajaxload.info/" target="_blank">Ajaxload.info</a></li>
					<li><a href="http://webcheatsheet.com/php/get_current_page_url.php" target="_blank">Get current page URL in php</a></li>
				</ul>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Schlie&szlig;en</button>
			</div>
		</div>
	  </div>
</div>
<!-- END INFO MODAL -->



<!-- UPLOAD MODAL -->
<div class="modal" id="myModal-upload" tabindex="-1" role="dialog" aria-labelledby="myModalLabel-upload" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel-upload">Upload</h4>
			</div>
			<div class="modal-body">
				<div id='ajaxloaderonupload' style='display: none;'>
					<img src='img/ajax-loader.gif' alt="loading..."/>
				</div>
				<input type="file" id="uploadFile">
				<div id="responses"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal" id="closeUpload">Schlie&szlig;en</button>
			</div>
		</div>
	</div>
</div>
<!-- END UPLOAD MODAL -->



<!-- FLOT GRAPHS-->
<script type="text/javascript">
	$(function() {
		var data = <?php echo json_encode($allEles, JSON_NUMERIC_CHECK); ?>;
		$.plot("#graph-height", [ data ]);

<?php
if ($hideSpeed == 0) {
	echo "		var speed = " . json_encode($allSpeed, JSON_NUMERIC_CHECK) . ";";
	echo "		$.plot(\"#graph-speed\", [ speed ]);";
}
?>
	});
</script>
<!-- END FLOT GRAPHS-->



<!-- TRACK MAP -->
<script type="text/javascript">
	function display_gpx(elt) {
		if (!elt) return;
	
		var map = L.map('map', {
			center: [<?php echo $lat; ?>, <?php echo $lon; ?>],
			zoom: 15,
			attributionControl: false
			});
	
		var gpx = 'tracks/<?php echo $track; ?>';


		function _c(c) { return elt.getElementsByClassName(c)[0]; }


		L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={access_token}', {
			maxZoom: 18,
			id: '<?php echo $mapboxId; ?>',
			access_token: '<?php echo $mapboxAccessToken; ?>'
	
		}).addTo(map);
	
		new L.GPX(gpx, {
			async: true,
			marker_options: {
				startIconUrl: 'img/pin-icon-start.png',
				endIconUrl: 'img/pin-icon-end.png',
				shadowUrl: 'img/pin-shadow.png'
			}
		}).on('loaded', function(e) {
			map.fitBounds(e.target.getBounds());

			var gpx = e.target;
			_c('distance').textContent = Math.round(gpx.get_distance());
			_c('duration').textContent = moment.utc(gpx.get_total_time()).format('HH:mm:ss');
			_c('speed').textContent = gpx.get_moving_speed().toFixed(2);
			if (gpx.get_name()) {
				_c('trackName').textContent = gpx.get_name();
			}
			_c('start').textContent = moment(gpx.get_start_time()).format('HH:mm:ss');
			_c('end').textContent = moment(gpx.get_end_time()).format('HH:mm:ss');
		}).addTo(map);
	}
	
	display_gpx(document.getElementById('gpx'));
</script>
<!-- END TRACK MAP -->



<!-- UPLOAD TRACKS -->
<script type="text/javascript">
	$('body').on('change', '#uploadFile', function() {
		$('#ajaxloaderonupload').show();
		var data = new FormData();
		data.append('file', this.files[0]);
		$.ajax({
			url: 'upload.php',
			data: data,      
			type: 'POST',   
			processData: false,
			contentType: false,
			dataType: "json",
			success: function(response) { 
				$('#ajaxloaderonupload').hide();
				$('#uploadFile').hide();
				if (response.status == "OK") {
					$('#closeUpload').addClass('reload');
				}
				else {
					$('#closeUpload').addClass('noreload');
				}
				$("#responses").append(response.message);
			},
		});
	})
</script>
<!-- END UPLOAD TRACKS -->



<!-- RESET UPLOAD MODAL-->
<script type="text/javascript">
	$('#uploadbutton').click(function(){
	      $('#uploadFile').show();
	      $("#responses").empty();
	});
</script>



<!-- RELOAD PAGE AFTER UPLOAD -->
<script type="text/javascript">
	$('#closeUpload').click(function() {
		if ($('#closeUpload').hasClass('reload')) {
			location.reload();
		}
	});
</script>



</body>
</html>

<?php
unset ($allSpeed);
unset ($allEles);
unset ($track);
unset ($xml);
?>
