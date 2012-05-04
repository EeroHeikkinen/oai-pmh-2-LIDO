<?php
/**
 * \file
 * \brief classes related to generating RIF-CS XML response file for ANDS.
 *
 *
 * Generate RIF-CS set records of Activity, Collection, Party.
 * - They are closely bounded to ANDS requirements, need to know the database for getting information.
 */

require_once('xml_creater.php');

/**
 * \brief For creating RIF-CS metadata to meet the requirement of ANDS.
 *
 * Class ANDS_RIFCS provides all essential functionalities for creating ANDS RIF-CS records.
 * The protected member functions are the backbone functions which can be used for creating any ANDS RIF-CS records.
 *
 */

class LIDO {
 /**
 	* \var $oai_pmh 
 	* Type: ANDS_Response_XML. Assigned by constructor. \see __construct
  */
	protected $oai_pmh;
	/** \var $working_node
	* Type: DOMElement. Assigned by constructor. \see __construct	
	*/
	protected $working_node;
	
	protected $lido;
	protected $descriptiveMetadata;
	protected $objectClassificationWrap;
	protected $objectWorkTypeWrap;
	protected $objectIdentificationWrap;
	protected $administrativeMetadata;
	protected $recordWrap;
	protected $language;

	/**
	 * Constructor
	 *
	 * \param $ands_response_doc ANDS_Response_XML. A XML Doc acts as the parent node.
	 * \param $metadata_node DOMElement. The meta node which all subsequent nodes will be added to.
	 */
  function __construct($language) {
		$this->doc = new DOMDocument("1.0","UTF-8");
		$this->language = $language;
		$lidoWrap = $this->addChild($this->doc,'lido:lidoWrap');
		$lidoWrap->setAttribute('xmlns:lido',"http://www.lido-schema.org");
		$lidoWrap->setAttribute('xmlns:xsi',"http://www.w3.org/2001/XMLSchema-instance");
		$lidoWrap->setAttribute('xsi:schemaLocation','http://www.lido-schema.org http://www.lido-schema.org/schema/v1.0/lido-v1.0.xsd');
	
		$this->lido = $this->addChild($lidoWrap, "lido:lido");
		
		$this->descriptiveMetadata = $this->addChild($this->lido, "lido:descriptiveMetadata");
		$this->descriptiveMetadata->setAttribute("xml:lang", $this->language);
		$this->objectClassificationWrap = $this->addChild($this->descriptiveMetadata, "lido:objectClassificationWrap");
		$this->objectWorkTypeWrap = $this->addChild($this->objectClassificationWrap, "lido:objectWorkTypeWrap");
		$this->objectIdentificationWrap = $this->addChild($this->descriptiveMetadata, "lido:objectIdentificationWrap");
		$this->titleWrap = $this->addChild($this->objectIdentificationWrap, "lido:titleWrap");
		$this->administrativeMetadata = $this->addChild($this->lido, "lido:administrativeMetadata");
		$this->administrativeMetadata->setAttribute("xml:lang", $this->language);
		$this->recordWrap = $this->addChild($this->administrativeMetadata, "lido:recordWrap");
  }
  
  function setLidoRecID($value, $type) {
    // Can't use usual worker function because it uses appendChild and we need to make sure the order is correct
	$added_node = $this->doc->createElement("lido:lidoRecID",$value);
	$added_node->setAttribute('lido:type', $type);
	$added_node = $this->lido->insertBefore($added_node, $this->descriptiveMetadata);
	return $added_node;
  }
  
  function setRecordID($value, $type) {
	$added_node = $this->addChild($this->recordWrap, "lido:recordID", $value);
	$added_node->setAttribute('lido:type', $type);
  }
  
  function setRecordType($value, $lang = null) {
	if(empty($lang))
		$lang = $this->language;
	$recordType = $this->addChild($this->recordWrap, "lido:recordType");
	$term = $this->addChild($recordType, "lido:term", $value);
	$term->setAttribute('xml:lang', $lang);
  }
  
  function setRecordSource($name, $url) {
	$recordSource = $this->addChild($this->recordWrap, "lido:recordSource");
	if(!empty($name)) {
		$legalBodyName = $this->addChild($recordSource, "lido:legalBodyName");
		$this->addChild($legalBodyName, "lido:appellationValue", $name);
	}
	if(!empty($url)) {
		$this->addChild($recordSource, "lido:legalBodyWeblink", $url);
	}
  }
  
  function addWorkType($value) {
	$objectWorkType = $this->addChild($this->objectWorkTypeWrap, "lido:objectWorkType");
	$this->addChild($objectWorkType, "lido:term", $value);
  }
  
  function addTitle($value) {
    $titleSet = $this->addChild($this->titleWrap, "lido:titleSet");
	$this->addChild($titleSet, "lido:appellationValue", $value);
  }
  
  function addDescription($value, $language = null, $type = null) {
    if(empty($value))
		return;
	if(empty($language))
		$language = $this->language;
		
	$list = $this->doc->getElementsByTagName("lido:objectDescriptionWrap");
	if($list->length > 0)
		$descriptionWrap = $list->item(0);			
	else
		$descriptionWrap = $this->addChild($this->objectIdentificationWrap, "lido:objectDescriptionWrap");
    
	$objectDescriptionSet = $this->addChild($descriptionWrap, "lido:objectDescriptionSet");
	if(!empty($type))
		$objectDescriptionSet->setAttribute("lido:type", $type);
	$this->addChild($objectDescriptionSet, "lido:descriptiveNoteValue", $value);
  }


  function addMeasurements($value) {	
	$list = $this->doc->getElementsByTagName("lido:objectMeasurementsWrap");
	if($list->length > 0)
		$objectMeasurementsWrap = $list->item(0);			
	else
		$objectMeasurementsWrap = $this->addChild($this->objectIdentificationWrap, "lido:objectMeasurementsWrap");
    
	$objectMeasurementsSet = $this->addChild($objectMeasurementsWrap, "lido:objectMeasurementsSet");
	if(!empty($value)) {
		$this->addChild($objectMeasurementsSet, "lido:displayObjectMeasurements", $value);
	}
  }
  
  function addEvent($value) {	
	$list = $this->doc->getElementsByTagName("lido:eventWrap");
	if($list->length > 0)
		$eventWrap = $list->item(0);			
	else
		$eventWrap = $this->addChild($this->descriptiveMetadata, "lido:eventWrap");
    
	$eventSet = $this->addChild($eventWrap, "lido:eventSet");
	$event = $this->addChild($eventSet, "lido:event");
	if(!empty($value)) {
		$eventType = $this->addChild($event, "lido:eventType");
		$this->addChild($eventType, "lido:term", $value);
	}
	return $event;
  }
  
  function addEventActor($event, $actor, $role) {
	$eventActor = $this->addChild($event, "lido:eventActor");
	$actorInRole = $this->addChild($eventActor, "lido:actorInRole");
	// element suffix because of name clash with function param
	$actorElement = $this->addChild($actorInRole, "lido:actor");
	$nameActorSet = $this->addChild($actorElement, "lido:nameActorSet");
	$this->addChild($nameActorSet, "lido:appellationValue", $actor);
	
	$roleActor = $this->addChild($actorInRole, "lido:roleActor");
	$this->addChild($roleActor, "lido:term", $role);
  }
  
  function addEventPlace($event, $place) {
	$eventPlace = $this->addChild($event, "lido:eventPlace");
	$this->addChild($eventPlace, "lido:displayPlace", $place);
  }
  
  function addEventDate($event, $display = null, $earliest, $latest) {
	$eventDate = $this->addChild($event, "lido:eventDate");
	if(!empty($display)) 
		$this->addChild($eventDate, "lido:displayDate", $display);
	if(!empty($earliest) && !empty($latest)) {
		$date = $this->addChild($eventDate, "lido:date");
		$this->addChild($date, "lido:earliestDate", $earliest);
		$this->addChild($date, "lido:latestDate", $latest);
	}
  }
  
  function getDoc() {
	return $this->doc;
  }
	
  
  function addEventMaterialsTech($event, $display) {
	$eventMaterialsTech = $this->addChild($event, "lido:eventMaterialsTech");
	if(!empty($display))
		$this->addChild($eventMaterialsTech, "lido:displayMaterialsTech", $display);
	return $eventMaterialsTech;
  }
	
  function addEventMaterialsTechTerm($eventMaterialsTech, $term, $type = null) {
    $list = $eventMaterialsTech->getElementsByTagName("lido:materialsTech");
	if($list->length > 0)
		$materialsTech = $list->item(0);			
	else 
		$materialsTech = $this->addChild($eventMaterialsTech, "lido:materialsTech");
		
	$termMaterialsTech = $this->addChild($materialsTech, "lido:termMaterialsTech");
	if(!empty($type))
		$termMaterialsTech->setAttribute("lido:type", $type);
	$this->addChild($termMaterialsTech, "lido:term", $term);
  }
  
  function addInscription($value) {
    if(empty($value))
		return;
		
	$inscriptionsWrapList = $this->doc->getElementsByTagName("lido:inscriptionsWrap");
	if($inscriptionsWrapList->length > 0)
		$inscriptionsWrap = $repositoryWrapList->item(0);			
	else
		$inscriptionsWrap = $this->addChild($this->objectIdentificationWrap, "lido:inscriptionsWrap");
    
	$inscriptions = $this->addChild($inscriptionsWrap, "lido:inscriptions");
	$inscriptionDescription = $this->addChild($inscriptions, "lido:inscriptionDescription");
	$this->addChild($inscriptionDescription, "lido:descriptiveNoteValue", $value);
  }
  
  function addImage($value) {
	$resourceWrapList = $this->doc->getElementsByTagName("lido:resourceWrap");
	if($resourceWrapList->length > 0)
		$resourceWrap = $resourceWrapList->item(0);			
	else 
		$resourceWrap = $this->addChild($this->administrativeMetadata, "lido:resourceWrap");
    
	$resourceSet = $this->addChild($resourceWrap, "lido:resourceSet");
	$resourceRepresentation = $this->addChild($resourceSet, "lido:resourceRepresentation");
	$this->addChild($resourceRepresentation, "lido:linkResource", $value);
  }
  
  function addRepository($legalBodyName = null, $legalBodyWeblink = null, $workID = null) {
	$repositoryWrapList = $this->doc->getElementsByTagName("lido:repositoryWrap");
	if($repositoryWrapList->length > 0)
		$repositoryWrap = $repositoryWrapList->item(0);			
	else 
		$repositoryWrap = $this->addChild($this->objectIdentificationWrap, "lido:repositoryWrap");
    
	$repositorySet = $this->addChild($repositoryWrap, "lido:repositorySet");
	$repositoryName = $this->addChild($repositorySet, "lido:repositoryName");
	
	if(!empty($legalBodyName)) {
		// DOM suffix just because the variables overlap
		$legalBodyNameDOM = $this->addChild($repositoryName, "lido:legalBodyName");
		$this->addChild($legalBodyNameDOM, "lido:appellationValue", $legalBodyName);
	}
	if(!empty($legalBodyWeblink)) {
		$this->addChild($repositoryName, "lido:legalBodyWeblink", $legalBodyWeblink);
	}
	
	if(!empty($workID)) {
		$this->addChild($repositorySet, "lido:workID", $workID);
	}
  } 
  
	protected function addChild($mom_node,$name, $value='') {
		$added_node = $this->doc->createElement($name,$value);
		$added_node = $mom_node->appendChild($added_node);
		return $added_node;
    }
}
