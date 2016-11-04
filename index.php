<?php
// Inkludér fil der etablerer forbindelse til databasen (i variablen $link)
require 'db_config.php';

// Definer variabler til standardværdier i formular, når der ikke er blevet søgt
$soeg		= $pris_fra = $pris_til = $kategori = '';
$producent	= [];

// Forespørgsel til at hente alle aktive produkter fra databasen gemmes i variablen $produkter_sql
$produkter_sql =
		"SELECT
			produkt_varenr, produkt_navn, produkt_beskrivelse, produkt_pris
		FROM
			produkter
		WHERE
			produkt_status = 1";

// Hvis soeg er defineret i URL parametre og værdien ikke er tom
if ( isset($_GET['soeg']) && !empty($_GET['soeg']) )
{
	// Gem værdien og brug mysqli_escape_string() til at sikre imod SQL injections, når variablen $soeg bruges i $produkter_sql
	$soeg = mysqli_escape_string($link, $_GET['soeg']);

	// Tillæg del af sql der filtrerer produkter efter søgning på navn, beskrivelse og varenr
	$produkter_sql .=
		"
		AND
			(produkt_varenr LIKE '%$soeg%'
		OR
			produkt_navn LIKE '%$soeg%'
		OR
			produkt_beskrivelse LIKE '%$soeg%')";
}

// Hvis pris-fra og pris-til er defineret i URL parametre og værdierne ikke er tomme (vi bruger != '' i stedet for !empty(), da man f.eks. skal kunne søge fra prisen 0, men værdien 0 betragtes som tom og så vil koden i nedenstående if, ikke blive kørt.
if ( isset($_GET['pris-fra'], $_GET['pris-til']) && $_GET['pris-fra'] != '' && $_GET['pris-til'] != '' )
{
	// Gem værdierne og brug intval til at sikre imod SQL injections, når variablerne $pris_fra og $pris_til bruges i $produkter_sql. BEMÆRK VARIABELNAVNE ER MED UNDERSCORE, MODSAT NAVNET I GET. MAN KAN NEMLIG IKKE BRUGE BINDESTREGER I VARIABELNAVNE.
	$pris_fra = intval($_GET['pris-fra']);
	$pris_til = intval($_GET['pris-til']);

	// Tillæg del af sql der filtrerer produkter efter indtastet interval
	$produkter_sql .=
		"
		AND
			produkt_pris BETWEEN $pris_fra AND $pris_til";
}

// Hvis kategori er defineret i URL parametre og værdien ikke er tom
if ( isset($_GET['kategori']) && !empty($_GET['kategori']) )
{
	// Gem værdien og brug intval() til at sikre imod SQL injections, når variablen $kategori bruges i $produkter_sql
	$kategori = intval($_GET['kategori']);

	// Tillæg del af sql der filtrerer produkter efter valgt kategori
	$produkter_sql .=
		"
		AND
			fk_kategori_id = $kategori";
}

// Hvis producent er defineret i URL parametre og værdien ikke er tom
if ( isset($_GET['producent']) && !empty($_GET['producent']) )
{
	// Brug funktionen array_map(), til at køre funktionen intval på hver værdi i array, så vi er sikker på hver værdi er et tal og dermed sikrer imod SQL injections.
	$producent = array_map('intval', $_GET['producent']);

	// Alternativ løsning, er at løbe igennem værdier med en foreach-løkke og brug intval på hver værdi i løkken og tilføje til et array, for på den måde at være sikker på det array kun indeholder tal. Løsningen er udkommenteret, da den er en smule tungere end array_map() og det er mere kode at skrive.
	// $producent = [];
	// foreach($_GET['producent'] as $id) $producent[] = intval($id);

	// Når vi skal tjekke om en kolonne matcher flere værdier, fra f.eks. et array som her, er den korteste og hurtigste måde at bruge IN (), som skal bruge værdier kommasepareret i parantesen. Derfor bruger vi funktionen implode(), til at lave vores array af tal, om til en kommasepareret streng, også kaldet et CSV (Comma Separated Values)
	$producent_csv = implode(',', $producent);

	// Tillæg del af sql der filtrerer produkter efter valgte producenter
	$produkter_sql .=
		"
		AND
			fk_producent_id IN ($producent_csv)";
}

// Tillæg del af sql der sorterer produkter efter pris
$produkter_sql .=
		"
		ORDER BY
			produkt_pris";
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
		  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Søgning - Avanceret</title>
</head>
<body>
<h1>Avanceret søgning på produkter</h1>
<!-- Formular til søgning med metoden get, så søgning defineres som URL parametre og der kan linkes til resultat -->
<form method="get">
	<!-- Vi bruger en tabel i dette eksempel for at slippe for at lave CSS -->
	<table cellpadding="5">
		<tr>
			<th align="left">
				<label for="soeg">Fritekst søgning</label>
			</th>

			<td>
				<!-- Felt til fritekst søgning -->
				<input type="search" name="soeg" id="soeg" placeholder="Navn, Beskrivelse, Varenr." value="<?php echo $soeg // Udskriv aktuel værdi på URL parameteret soeg ?>">
			</td>
		</tr>

		<tr>
			<th align="left">
				Pris
			</th>

			<td>
				<!-- Felter til filtrering af pris
					 BEMÆRK VI BRUGER BINDESTREGER TIL AT SEPARERE ORD, DA SØGEMASKINER BETRAGTER DET SOM MELLEMRUM OG DET GØR LINK MERE LÆSBART  -->
				<label>Fra:
					<input type="number" name="pris-fra" value="<?php echo $pris_fra // Udskriv aktuel værdi på URL parameteret pris-fra ?>">
				</label>

				<label>Til:
					<input type="number" name="pris-til" value="<?php echo $pris_til // Udskriv aktuel værdi på URL parameteret pris-til ?>">
				</label>
			</td>
		</tr>

		<tr>
			<th align="left">
				<label for="kategori">Kategori</label>
			</th>

			<td>
				<?php
				// Forespørgsel til at hente alle kategorier fra databasen gemmes i variablen $query
				$query =
						"SELECT
							kategori_id, kategori_navn
						FROM
							kategorier
						ORDER BY
							kategori_navn";
				// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
				$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__)
				?>

				<!-- Dropwdown-liste til valg af kategori -->
				<select name="kategori" id="kategori">
					<option value="">Alle</option>
					<?php
					// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row. Brug while til at løbe igennem alle rækker fra databasen
					while( $row = mysqli_fetch_assoc($result) )
					{
						// Tjek om den aktuelle værdi fra databasen, matcher værdien i URL parametret kategori. Hvis det er tilfældet, skal den aktuelle option have tilføjet attributten selected, som vi derfor gemmer i variablen $selected. Ellers er variablen tom, så kun søgte producent er valgt
						$selected = $row['kategori_id'] == $kategori ? 'selected' : '';

						// For hver producent i databasen, laves option med producentens id som value og navn som label
						?>
						<option value="<?php echo $row['kategori_id'] ?>" <?php echo $selected ?>>
							<?php echo $row['kategori_navn'] ?>
						</option>
						<?php
					}
					?>
				</select>
			</td>
		</tr>

		<tr>
			<th align="left">
				Producent
			</th>

			<td>
				<?php
				// Forespørgsel til at hente alle producenter fra databasen gemmes i variablen $query
				$query =
						"SELECT
							producent_id, producent_navn
						FROM
							producenter
						ORDER BY
							producent_navn";
				// Send forespørgsel til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
				$result = mysqli_query($link, $query) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

				// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row. Brug while til at løbe igennem alle rækker fra databasen
				while( $row = mysqli_fetch_assoc($result) )
				{
					// Brug in_array() til at se om den aktuelle værdi fra databasen, matcher en værdi i URL parametret producent. Hvis det er tilfældet, skal den aktuelle checkbox have tilføjet attributten checked, som vi derfor gemmer i variablen $checked. Ellers er variablen tom, så kun søgte producenter er afkrydset
					$checked = in_array($row['producent_id'], $producent) ? 'checked' : '';

					// For hver producent i databasen, laves checkbox med producentens id som value og navn som label
					?>
					<!-- Felt til valg af producent -->
					<label>
						<input type="checkbox" name="producent[]" value="<?php echo $row['producent_id'] ?>" <?php echo $checked ?>>
						<?php echo $row['producent_navn'] ?>
					</label>
					<?php
				}
				?>
			</td>
		</tr>

		<tr>
			<th></th>
			<td>
				<button type="submit">Søg</button>
				<!-- Knap med link til at rydde alle URL parametre, ved at linke til aktuel mappe på serveren. Der kunne også linkes direkte til index.php -->
				<button type="button" onclick="location.href = './'">Ryd</button>
			</td>
		</tr>
	</table>

</form>

<?php
// Udskriv forespørgslen af produkter for at se hvordan den ændrer sig afhængig af søgeparametre
echo "Forespørgsel af produkter: <pre><code>$produkter_sql</code></pre>";

// Send forespørgsel af produkter til databasen med mysqli_query(). Hvis der er fejl heri, stoppes videre indlæsning og fejlbesked vises
$result = mysqli_query($link, $produkter_sql) or die( mysqli_error($link) . '<pre>' . $query . '</pre>' . 'Fejl i forespørgsel på linje: ' . __LINE__ . ' i fil: ' . __FILE__);

// Hvis forespørgsel af produkter returnerer nogle produkter fra databasen, så vises de her (mysqli_num_rows() tæller antallet af rækker i forespørgslen
if ( mysqli_num_rows($result) > 0 )
{
	// Vis antallet af produkter der vises
	echo '<h2>Der blev fundet ' . mysqli_num_rows($result) . ' produkter';

	// mysqli_fetch_assoc() returner data fra forespørgslen som et assoc array og vi gemmer data i variablen $row. Brug while til at løbe igennem alle rækker med produkter fra databasen
	while( $row = mysqli_fetch_assoc($result) )
	{
		?>
		<hr>
		<h3><?php echo $row['produkt_navn'] ?></h3>
		Varenr. <?php echo $row['produkt_varenr'] ?>
		<br><?php echo substr($row['produkt_beskrivelse'], 0, 100) . '...' // Brug substr() til kun at vise de første 100 karakterer af produktets beskrivelse ?>
		<br><strong><?php echo number_format($row['produkt_pris'], 2, ',', '.') // Brug number_format() til at formatere prisen med 2 decimaler, komma til adskillelse af decimaler og punktum for hvert tusinde i beløb. F.eks. 123.456,78 ?> kr.</strong>
		<?php
	}
}
// Hvis ikke der blev fundet nogle produkter, vises denne besked
else
{
	echo '<hr><p>Der blev ikke fundet nogle produkter</p>';
}
?>
</body>
</html>