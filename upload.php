<?php

$skip = 0;


if (! is_writeable(getcwd() . "/tracks/")) {
	$returnMessage = array("status" => "ERROR", "message" => "Das Verzeichnis ist nicht beschreibbar.");
	$skip = 1;
}

if ($skip == "0" && ! isset($_FILES['file'])) {
	$returnMessage = array("status" => "ERROR", "message" => "Es wurde keine Datei gesendet.");
	$skip = 1;
}

if ($skip == "0" && file_exists(getcwd() . "/tracks/" . $_FILES['file']['name'])) {
	$returnMessage = array("status" => "ERROR", "message" => "Eine Datei mit diesem Namen existiert bereits.");
	$skip = 1;
}

if ($skip == "0" && $_FILES['file']['type'] != 'application/octet-stream') {
	$returnMessage = array("status" => "ERROR", "message" => "Die hochgeladene Datei hat nicht den MIME Type 'application/octet-stream'");
	$skip = 1;
}

if ($skip == "0" && pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION) != 'gpx') {
	$returnMessage = array("status" => "ERROR", "message" => "Die hochgeladene Datei hat nicht die Dateiendung '.gpx'");
	$skip = 1;
}

if ($skip == "0") {
	move_uploaded_file($_FILES['file']['tmp_name'], getcwd() . "/tracks/" . $_FILES['file']['name']);
	$returnMessage = array("status" => "OK", "message" => "Datei erfolgreich hochgeladen");
}


header('Content-Type: application/json');
echo json_encode($returnMessage);

?>
