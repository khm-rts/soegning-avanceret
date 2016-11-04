<?php
$db_host	= "localhost";			// Hostnavn på database
$db_user	= "root";				// Brugernavn til database
$db_pass	= "";					// Adgangskode til database
$db_name	= "soegning_avanceret";	// Databasenavn

// Opretter forbindelse til databasen
$link = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Hvis $link indeholder bool-værdi: false, er mysqli_connect() fejlet
if (!$link) {
	// Stop videre indlæsning med die og udskriv forbindelsesfejl
	die( 'Forbindelsesfejl: '. mysqli_connect_error() );
}

mysqli_set_charset($link, "utf8"); // Sætter tegnsætning til utf8
mysqli_query($link, "SET lc_time_names = 'da_DK'"); // Sætter navne på måneder og dage til dansk