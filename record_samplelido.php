<?php
// This handles LIDO records

require_once("Lido.php");

function fetch_record($db, $identifier) {
	try {
		$query = "select tapetti_kortisto.id, dokumentointitiedot, kyla, merkinnat, kuvaus, mitat, valmistusaika, aika_alku, aika_loppu, ajoitustapa, valmistuspaikka, lisatiedot, kuva,
		valmistaja.nimi AS valmistaja, 
		kohde.nimi AS kohde,
		tyyppi.nimi AS tyyppi,
		vari.nimi AS vari,
		materiaali.nimi AS materiaali,
		tekniikka.nimi AS tekniikka,
		tyyli.nimi AS tyyli,
		tapetti_kortisto.yleisvari AS yleisvari, tapetti_kortisto.kuvio AS kuvio
		from tapetti_kortisto 
		left join tapetti_arvot as valmistaja on valmistaja.id = tapetti_kortisto.valmistaja and valmistaja.kentta = 'valmistaja' 
		left join tapetti_arvot as kohde on kohde.id = tapetti_kortisto.kohde and kohde.kentta = 'kohde' 
		left join tapetti_arvot as tyyppi on tyyppi.id = tapetti_kortisto.tyyppi and tyyppi.kentta = 'tyyppi' 
		left join tapetti_arvot as vari on vari.id = tapetti_kortisto.vari and vari.kentta = 'vari' 
		left join tapetti_arvot as materiaali on materiaali.id = tapetti_kortisto.materiaali and materiaali.kentta = 'materiaali' 
		left join tapetti_arvot as tekniikka on tekniikka.id = tapetti_kortisto.tekniikka and tekniikka.kentta = 'tekniikka'
		left join tapetti_arvot as tyyli on tyyli.id = tapetti_kortisto.tyyli and tyyli.kentta = 'tyyli'
		left join tapetti_arvot as kunto on kunto.id = tapetti_kortisto.kunto and kunto.kentta = 'kunto'
		where tapetti_kortisto.id = '".$identifier."'";
		
			$res = exec_pdo_query($db, $query);
			$record = $res->fetch(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
				echo "$key returned no record.\n";
				echo $e->getMessage();
	}
	
	$monivalinta = array("yleisvari", "kuvio");
	while($field = array_shift($monivalinta)) {
		$query = "select tapetti_kortisto.id, tapetti_arvot.nimi
			from tapetti_kortisto 
			left join tapetti_arvot on tapetti_arvot.kentta = '" . $field . "' AND tapetti_arvot.id & tapetti_kortisto." . $field . "
			where tapetti_kortisto.id = '".$identifier."'";
		$res = exec_pdo_query($db, $query);
		$results = $res->fetchAll(PDO::FETCH_ASSOC);
		$record[$field] = array();
		foreach($results as $result)
			$record[$field][] = $result['nimi'];
	}
	
	return $record;
}

function create_metadata($outputObj, $cur_record, $identifier, $setspec, $db) {
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	$record = fetch_record($db, $identifier);
	$metadata_node = $outputObj->create_metadata($cur_record);
	$LIDO = new LIDO("fi");
	
	$LIDO->setLidoRecID($identifier, "NDL");
	$LIDO->setRecordID($identifier, "local");
	$LIDO->setRecordType("item", "en");
	$LIDO->setRecordSource("Tapettitietokanta", "http://tapetti.nba.fi");
	$LIDO->addWorkType("object");
	$LIDO->addTitle("Tapettifragmentti");

	if(!empty($record['merkinnat']))
		$LIDO->addInscription($record['merkinnat']);
	$LIDO->addRepository("Tapettitietokanta", "http://tapetti.nba.fi", $identifier);
	if(!empty($record['kuvaus']))
		$LIDO->addDescription($record['kuvaus'], 'fi', 'kuvaus');
	if(!empty($record['lisatiedot']))
		$LIDO->addDescription($record['lisatiedot'], 'fi', 'lisatiedot');
		
	if(!empty($record['mitat'])) {
		$measurements = $LIDO->addMeasurements($record['mitat']);
	}
	
	if(!empty($record['kuva'])) {
		$kuva_tiedosto = substr($record['kuva'], 6, count($record['kuva'])+6);
		$LIDO->addImage('http://tapetti.nba.fi/pic.php?t&#61;1&amp;p&#61;' . $kuva_tiedosto);
	}
	
	if(!empty($record['valmistaja']) || !empty($record['valmistuspaikka']) || !empty($record['valmistusaika']) || !empty($record['materiaali'])) {
		$valmistus = $LIDO->addEvent("valmistus");
		if(!empty($record['valmistaja']))
			$LIDO->addEventActor($valmistus, $record['valmistaja'], 'valmistaja');
		if(!empty($record['valmistusaika']))
			$LIDO->addEventDate($valmistus, $record['valmistusaika'], $record['aika_alku'], $record['aika_loppu']);
		if(!empty($record['valmistuspaikka']))
			$LIDO->addEventPlace($valmistus, $record['valmistuspaikka']);
		
		$materialsTech = array();
		if(!empty($record['materiaali']))
			$materialsTech[] = $record['materiaali'];
		if(!empty($record['tekniikka']))
			$materialsTech[] = $record['tekniikka'];
		if(!empty($record['vari']))
			$materialsTech[] = $record['vari'];
		
		if(!empty($materialsTech)) {
			$materialsTech = $LIDO->addEventMaterialsTech($valmistus, implode($materialsTech, ", "));
			if(!empty($record['materiaali']))
				$LIDO->addEventMaterialsTechTerm($materialsTech, $record['materiaali'], 'materiaali');
			if(!empty($record['tekniikka']))
				$LIDO->addEventMaterialsTechTerm($materialsTech, $record['tekniikka'], 'tekniikka');
			if(!empty($record['vari']))
				$LIDO->addEventMaterialsTechTerm($materialsTech, $record['vari'], 'tekniikka');
		}		
	}
	if(!empty($record['kohde'])) {
		$kaytto = $LIDO->addEvent("käyttö");
		if(!empty($record['valmistuspaikka']))
			$LIDO->addEventPlace($kaytto, $record['kohde']);
	}
	
	// Tässä vaiheessa LIDO-tulos on rakennettu ja lisätään se OAI-PMH-vastaukseen
	$doc = $LIDO->getDoc();
	// Elementti täytyy ensin lisätä toiseen DOM-dokumenttiin
	$documentElement = $metadata_node->ownerDocument->importNode($doc->documentElement, true);
	// Ja tärkein lopuksi: lisätään metadata vastaukseen
	$metadata_node->appendChild($documentElement);
}