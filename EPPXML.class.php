<?php
/**
 * Class to generate XML data to be used with EPP
 *
 * @package   EPPXML
 * @author    Jim Nelin <jim@jine.se>
 * @copyright Copyright (c) 2010, Jim Nelin
 * @see       readme.txt
 */
class EPPXML {

    /**
    * Variable used for internal storage of objects and data
    * @var array
    */
    private $vars = array();
    
    /**
    * Constuctor for the EPPXML object.
    *
    * Creates the DOMDocument object to be used later on
    * Also adds the default <epp/> tree and sets default attributes for the root element.
    *
    * @return void
    */
    public function __construct() {
        // Initialize the DOM-tree
        $this->document = new DOMDocument('1.0', 'UTF-8');
        
        // Set DOM modes and output format
        $this->dovc->standalone = false;
        $this->document->formatOutput = FORMAT_OUTPUT;

        // Create $this->epp and fill it with default attributes
        $this->epp = $this->document->appendChild($this->document->createElement('epp'));
        $this->epp->appendChild($this->setAttribute('xmlns', XMLNS_EPP));
        $this->epp->appendChild($this->setAttribute('xmlns:xsi', XMLNS_XSCHEMA));
        $this->epp->appendChild($this->setAttribute('xsi:schemaLocation', XSCHEMA_EPP));

        // Append <epp/> to the document
        $this->document->appendChild($this->epp);
        
    }
    
    /**
    * Creates EPP request for <login/>
    * Uses EPP_USER and EPP_PWD constants for login
    *
    * @return void
    */
    public function Login() {
        
        // As this is a command, add the element.
        $this->_command();
        
        $login = $this->command->appendChild($this->document->createElement('login'));
        $login->appendChild($this->document->createElement('clID', EPP_USER));
        $login->appendChild($this->document->createElement('pw', EPP_PWD));

        $options = $login->appendChild($this->document->createElement('options'));
        $options->appendChild($this->document->createElement('version', '1.0'));
        $options->appendChild($this->document->createElement('lang', 'en'));

        $svcs = $login->appendChild($this->document->createElement('svcs'));
        $svcs->appendChild($this->document->createElement('objURI', XSCHEMA_DOMAIN));
        $svcs->appendChild($this->document->createElement('objURI', XSCHEMA_CONTACT));
        $svcs->appendChild($this->document->createElement('objURI', XSCHEMA_HOST));

        $svcx = $svcs->appendChild($this->document->createElement('svcExtension'));
        $svcx->appendChild($this->document->createElement('extURI', XSCHEMA_EXTDNSSEC));
        $svcx->appendChild($this->document->createElement('extURI', XSCHEMA_EXTIIS));
        
        // Add transactionId to this frame
        $this->_transaction();
    }
    
    
    /**
    * Creates EPP request for <logout/>
    *
    * @return void
    */
    public function Logout() {
        // As this is a command, add the element.
        $this->_command();
        
        $this->command->appendChild($this->document->createElement('logout'));
        
        // Add transactionId to this frame
        $this->_transaction();
    }
    
    
    /**
    * Creates EPP request for <poll/>
    *
    * @param string $op req|ack
    * @param int $msgID Message id to ack
    * @return void
    */
    public function Poll($op = 'req', $msgID = null) {
        // As this is a command, add the element.
        $this->_command();
        
        $poll = $this->command->appendChild($this->document->createElement('poll'));
        $poll->appendChild($this->setAttribute('op', $op));
        
        if($msgID) {
            $poll->appendChild($this->setAttribute($this->document, 'msgID', $msgID));
        }
        
        // Add transactionId to this frame
        $this->_transaction();
    }
    
    /**
    * Function for handeling all <check/> requests over EPP
    * Supports check's against contacts, hosts and domains.
    * 
    * @param string $type domain|contact|host
    * @param mixed $value Either a array with up to 5 objects, or a string with one.
    * @return void
    */
    public function Check($type, $value) {
        // As this is a command, add the element.
        $this->_command();
        
        if($type == 'domain') {
            $xschema = XSCHEMA_DOMAIN;
            $checkelement = 'domain:name';
        } elseif($type == 'contact') {
            $xschema = XSCHEMA_CONTACT;
            $checkelement = 'contact:id';
        } elseif($type == 'host')  {
            $xschema = XSCHEMA_HOST;
            $checkelement = 'host:name';
        }
        
        $check = $this->command->appendChild($this->document->createElement('check'));
        $typecheck = $check->appendChild($this->document->createElementNS($xschema, $type.':check')); 
        $typecheck->appendChild($this->setAttribute('xsi:schemaLocation', $xschema.' '.$type.'-1.0.xsd'));
       
        if(is_array($value)) {
            foreach($value as $val) {
                $typecheck->appendChild($this->document->createElement($checkelement, $val));
            }
        } else {
            $typecheck->appendChild($this->document->createElement($checkelement, $value));
        }
                
        // Add transactionId to this frame
        $this->_transaction();
    }

    /**
    * Function for handeling all <info/> requests over EPP
    * Supports info requests against contacts, hosts and domains.
    * 
    * @param string $type domain|contact|host
    * @param string $value String in matching format as $type
    * @return void
    */
    public function Info($type, $value) {
        // As this is a command, add the element.
        $this->_command();
        
        if($type == 'domain') {
            $xschema = XSCHEMA_DOMAIN;
            $infoelement = 'domain:name';
        } elseif($type == 'contact') {
            $xschema = XSCHEMA_CONTACT;
            $infoelement = 'contact:id';
        } elseif($type == 'host')  {
            $xschema = XSCHEMA_HOST;
            $infoelement = 'host:name';
        }
        
        $info = $this->command->appendChild($this->document->createElement('info'));
        $typeinfo = $info->appendChild($this->document->createElementNS($xschema, $type.':info')); 
        $typeinfo->appendChild($this->setAttribute('xsi:schemaLocation', $xschema.' '.$type.'-1.0.xsd'));
       
        $typeinfo->appendChild($this->document->createElement($infoelement, $value));
                
        // Add transactionId to this frame
        $this->_transaction();
    }
    

    /**
    * Function for handeling all <delete/> requests over EPP
    * It's only possible to delete contacts and hosts.
    * 
    * @param string $type contact|host
    * @param string $value String in matching format as $type
    * @return void
    */
    public function Delete($type, $value) {
        // As this is a command, add the element.
        $this->_command();
        
        if($type == 'contact') {
            $xschema = XSCHEMA_CONTACT;
            $delelement = 'contact:id';
        } elseif($type == 'host')  {
            $xschema = XSCHEMA_HOST;
            $delelement = 'host:name';
        } elseif($type == 'domain')  {
            throw new Exception('Delete command not supported for type "domain"');
        }
        
        $delete = $this->command->appendChild($this->document->createElement('delete'));
        $typedel = $delete->appendChild($this->document->createElementNS($xschema, $type.':delete')); 
        $typedel->appendChild($this->setAttribute('xsi:schemaLocation', $xschema.' '.$type.'-1.0.xsd'));
       
        $typedel->appendChild($this->document->createElement($delelement, $value));
        
        // Add transactionId to this frame
        $this->_transaction();
    }
    
    /**
    * Function for handeling all <create/> requests over EPP
    * Used for creating of domains, contacts and hosts
    * 
    * @param string $type domain|contact|host
    * @param array $array Array with appropriate keys
    * @return void
    */
    public function Create($type, $array) {
        // As this is a command, add the element.
        $this->_command();
        
        if($type == 'domain') { $xschema = XSCHEMA_DOMAIN; }
        elseif($type == 'contact') { $xschema = XSCHEMA_CONTACT; }
        elseif($type == 'host')  { $xschema = XSCHEMA_HOST; }
        
        $create = $this->command->appendChild($this->document->createElement('create'));
        $typecreate = $create->appendChild($this->document->createElementNS($xschema, $type.':create')); 
        $typecreate->appendChild($this->setAttribute('xsi:schemaLocation', $xschema.' '.$type.'-1.0.xsd'));
        
        
        if($type == 'domain') { 
        
            $typecreate->appendChild($this->document->createElementNS($xschema, 'name', $array['name']));
            $period = $typecreate->appendChild($this->document->createElementNS($xschema, 'period', $array['period']));
            $period->appendChild($this->setAttribute('unit', 'm'));
            
                $ns = $typecreate->appendChild($this->document->createElementNS($xschema, 'ns'));
                foreach($array['ns'] as $hostobj) {
                    $ns->appendChild($this->document->createElementNS($xschema, 'hostObj', $hostobj));
                }
                
            $typecreate->appendChild($this->document->createElementNS($xschema, 'registrant', $array['registrant']));
            
            $authinfo = $typecreate->appendChild($this->document->createElementNS($xschema, 'authInfo'));
            $authinfo->appendChild($this->document->createElementNS($xschema, 'pw', $array['pw']));
            
        } elseif($type == 'contact') {
        
            $typecreate->appendChild($this->document->createElementNS($xschema, 'id', $array['id']));
            $loc = $typecreate->appendChild($this->document->createElementNS($xschema, 'postalInfo'));
            $loc->appendChild($this->setAttribute('type', 'loc'));
            
            $loc->appendChild($this->document->createElementNS($xschema, 'name', $array['name']));
            if(!empty($array['org'])) {
                $loc->appendChild($this->document->createElementNS($xschema, 'org', $array['org']));
            }
            
            $addr = $loc->appendChild($this->document->createElementNS($xschema, 'addr'));
            foreach($array['street'] as $street) {
                $addr->appendChild($this->document->createElementNS($xschema, 'street', $street));
            }
            
            $addr->appendChild($this->document->createElementNS($xschema, 'city', $array['city']));
            $addr->appendChild($this->document->createElementNS($xschema, 'pc', $array['pc']));
            $addr->appendChild($this->document->createElementNS($xschema, 'cc', $array['cc']));
            
            $voice = $typecreate->appendChild($this->document->createElementNS($xschema, 'voice', $array['voice']));
            $voice->appendChild($this->setAttribute('x', ''));
            
            if(!empty($array['fax'])) {
                $fax = $typecreate->appendChild($this->document->createElementNS($xschema, 'fax', $array['fax']));
                $fax->appendChild($this->setAttribute('x', ''));
            }
            
            $typecreate->appendChild($this->document->createElementNS($xschema, 'email', $array['email']));
            
            $disclose = $typecreate->appendChild($this->document->createElementNS($xschema, 'disclose'));
            $disclose->appendChild($this->setAttribute('flag', '0')); // Flag=0, default = hide all.
            
            if(empty($array['org'])) {
                $disclosename = $disclose->appendChild($this->document->createElementNS($xschema, 'name'));
                $disclosename->appendChild($this->setAttribute('type', 'loc'));
            } else {
                $disclosename = $disclose->appendChild($this->document->createElementNS($xschema, 'org'));
                $disclosename->appendChild($this->setAttribute('type', 'loc'));
            }
            
            $extension = $this->command->appendChild($this->document->createElement('extension'));
            $iiscreate = $extension->appendChild($this->document->createElementNS(XSCHEMA_EXTIIS, 'iis:create'));
            $iiscreate->appendChild($this->setAttribute('xsi:schemaLocation', XSCHEMA_EXTIIS.' iis-1.0.xsd'));
            $iiscreate->appendChild($this->document->createElementNS(XSCHEMA_EXTIIS, 'orgno', $array['orgno']));
            
            if(!empty($array['vatno'])) {
                $iiscreate->appendChild($this->document->createElementNS(XSCHEMA_EXTIIS, 'vatno', $array['vatno']));
            }
            
        } elseif($type == 'host') {
        
            $typecreate->appendChild($this->document->createElementNS($xschema, 'name', $array['name']));
            
            foreach($array['addr'] as $addr) {
                    $record = $typecreate->appendChild($this->document->createElementNS($xschema, 'addr', $addr[1]));
                    $record->appendChild($this->setAttribute('ip', $addr[0]));
            }
            
        }
        // Add transactionId to this frame
        $this->_transaction();
    }
    
    /**
    * Basic <hello/> over EPP
    * 
    * @return void
    */
    public function Hello() {
        $this->epp->appendChild($this->document->createElement('hello'));
    }
    
    
    /**
    * GetXML function for getting generated XML
    * When requested, the _clean functions is runned to restart EPPXML.
    *
    * @return void
    */
    public function getXML() {
        $xml = $this->document->saveXML();
        $this->_clean();
        return $xml;
    }
    
    /**
    * Private section starts
    * Functions below are marked as private
    * And are used internally in this class.
    * 
    * @see readme.txt
    */
    
    /**
    * Adds a <command/> element to <epp/> inside of the document.
    * Used in all functions thats generates commands.
    *
    * @return void
    */
    private function _command() {
        // Create <command/>
        $this->command = $this->epp->appendChild($this->document->createElement('command'));
    }
    
    
    /**
    * Re-initialize the class, using __construct.
    * 
    * @return void
    */
    private function _clean() {
        $this->__construct();
    }
    
    /**
    * Adds a <clTRID/> element the document.
    * Used in all functions thats generates commands.
    * Required by the RFC.
    *
    * @return void
    */
    private function _transaction() {
        // Add transactionid's to all generated EPP frames with commands
        $tranId = "phpEPP-" . time() . "-" . getmypid();
        $this->command->appendChild($this->document->createElement('clTRID', $tranId));
    }
    
    private function setAttribute($name, $value) {
        $attribute = $this->document->createAttribute($name);
        $attribute->nodeValue = $value;
        return $attribute;
    }
    
    private function __set($var, $value) {
        $this->vars[$var] = $value;
    }
    
    private function __get($var) {
        if(isset($this->vars[$var])) {
            return $this->vars[$var];
        }
    }    
    private function __isset($var) { 
        return (bool)$this->vars[$var]; 
    }
    
    private function __unset($var) { 
        unset($this->vars[$var]);
    }
    
    private function __toString() {
        return $this->getXML();
    }

}
