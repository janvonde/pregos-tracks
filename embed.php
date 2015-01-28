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


// initial zoom
$xml = simplexml_load_file("tracks/$track");
$lat = $xml->trk->trkseg->trkpt[count($xml->trk->trkseg->trkpt)-1][lat];
$lon = $xml->trk->trkseg->trkpt[count($xml->trk->trkseg->trkpt)-1][lon];



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
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css" />

	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.js"></script>
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

	</style>
</head>


<body id="gpx">


<div id="map"></div>

<!-- TRACK MAP -->
<script type="text/javascript">
	function display_gpx(elt) {
		if (!elt) return;
	
		var map = L.map('map', {
			center: [<?php echo $lat; ?>, <?php echo $lon; ?>],
			zoom: 15,
			});
	
		var gpx = 'tracks/<?php echo $track; ?>';


		L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={access_token}', {
			maxZoom: 18,
			id: '<?php echo $mapboxId; ?>',
			access_token: '<?php echo $mapboxAccessToken; ?>',
			attribution: 'Watch track in <a href="<?php echo str_replace('embed.php', 'index.php', curPageURL()) . "?file=" . $track; ?>" target="_blank">pregos tracks</a>'
	
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
		}).addTo(map);
	}
	
	display_gpx(document.getElementById('gpx'));
</script>
<!-- END TRACK MAP -->


</body>
</html>
