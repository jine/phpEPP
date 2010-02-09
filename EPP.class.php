<?php
/**
 * Class to generate XML data to be used with EPP
 *
 * @package   phpEPP
 * @author    Jim Nelin <jim@jine.se>
 * @copyright Copyright (c) 2010, Jim Nelin
 * @see       COMMANDS.txt
 */
class EPP {

    /**
    * Variable used for internal storage of objects and data
    * @var array
    */
    private $vars = array();
    
		
	// Proctect the magic clone function!
	// This to make it impossible to clone the class and use $vars.
	protected function __clone() {
			/* Placeholder */
	}
		
    /**
    * Constuctor for the EPP object.
	* Automaticly connects to the specified EPP server
	*
    * And creates the DOMDocument object to be used later on
    * Also adds the default <epp/> tree and sets default attributes for the root element.
    *
    * @return void
    */
    public function __construct($connect = true) {
		
		// Connects to the EPP server if $connect == true.
		if($connect && !$this->socket) {
			$this->Connect(EPP_HOST, EPP_PORT, EPP_TIMEOUT);
		}
		
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
	* This method establishes the connection to the server. If the connection was
	* established, then this method will call getFrame() and return the EPP <greeting>
	* frame which is sent by the server upon connection.
	*
	* @param string the hostname and protocol (tls://example.com)
	* @param integer the TCP port
	* @param integer the timeout in seconds
	* @return on success a string containing the server <greeting>
	*/
	function Connect($host, $port = 700, $timeout = 1) {
			
			$context = stream_context_create(array(
				'tls'=>array(
					'allow_self_signed' => 'TRUE',
					'passphrase' => EPP_CERT_PASS,
					'local_cert' => EPP_CERT,
				)
			));
			
			if (!$this->socket = stream_socket_client($host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context)) {
					die("Failed to connect:" . $errstr);
			} else {
					stream_set_timeout($this->socket, $timeout);
					return $this->getFrame();
			}
			
	}
	
	/**
	* Get an EPP frame from the server.
	* This retrieves a frame from the server. Since the connection is blocking, this
	* method will wait until one becomes available. 
	* containing the XML from the server
	* @return on success a string containing the frame
	*/
	function getFrame() {
		if (@feof($this->socket)) die('Couldn\'t get frame - closed by remote server');
		
		// Read the 4 first bytes (reply length)
		$hdr = fread($this->socket, 4);
		
		if (empty($hdr) && feof($this->socket)) {
			
			die('Couldn\'t get HDR - connection closed by remote server');
				
		} elseif (empty($hdr)) {
			
			die('Error reading from server - connection closed.');
			
		} else {
			
			$unpacked = unpack('N', $hdr);
			$length = $unpacked[1];
			if ($length < 5) {
					die(sprintf('Got a bad frame header length of %d bytes from server', $length));
			} else {
					return fread($this->socket, ($length - 4));
			}
			
		}
	}

	/**
	* Send the current XML frame to the server.
	* @return boolean the result of the fwrite() operation
	*/
	function sendFrame() {
		if($this->socket) {
			$xml = $this->getXML(); // Get the current XML frame
			return fwrite($this->socket, pack('N', (strlen($xml)+4)).$xml);
		}
		
		return false;
	}
	
	/**
	* a wrapper around sendFrame() and getFrame()
	* @return string the frame returned by the server
	*/
	function Process() {
		if($this->sendFrame()) {
			return $this->getFrame();
		}
		
		return false;
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
            $poll->appendChild($this->setAttribute('msgID', $msgID));
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
    * Function for handeling  <transfer/> requests over EPP
    * Supports transfers of domains to this registrar.
    * 
    * @param string $name affected domainname
    * @param string $password affected domain password
    * @return void
    */
    public function Transfer($name, $password) {
        // As this is a command, add the element.
        $this->_command();
                
        $transfer = $this->command->appendChild($this->document->createElement('transfer'));
		
		/**
		* Only op=”request” is supported. All transfers are rejected or executed immediately, 
		* therefore “query”, “approve”, “reject” are not supported.
		*/
		$transfer->appendChild($this->setAttribute('op', 'request'));
		
        $domaintransfer = $transfer->appendChild($this->document->createElementNS(XSCHEMA_DOMAIN, 'domain:transfer')); 
        $domaintransfer->appendChild($this->setAttribute('xsi:schemaLocation', XSCHEMA_DOMAIN.' domain-1.0.xsd'));
       
		$domaintransfer->appendChild($this->document->createElementNS(XSCHEMA_DOMAIN, 'name', $name));
		
		$authinfo = $domaintransfer->appendChild($this->document->createElementNS(XSCHEMA_DOMAIN, 'authInfo'));
		$authinfo->appendChild($this->document->createElementNS(XSCHEMA_DOMAIN, 'pw', $password));
		
        // Add transactionId to this frame
        $this->_transaction();
    }
        
    /**
    * Function for handeling renews of domains (only)
    * Renews a domain 12-120 months. 
	* Renews are possible until one day before <iis:delDate/>
    * 
    * @param string $name affected domainname
    * @param date $curexpiredate Current expire date
    * @param integer $period Period (in months) to renew domain.
    * @return void
    */
    public function Renew($name, $curexpdate, $period) {
        // As this is a command, add the element.
        $this->_command();
                
        $renew = $this->command->appendChild($this->document->createElement('renew'));
		
        $domainrenew = $renew->appendChild($this->document->createElementNS(XSCHEMA_DOMAIN, 'domain:renew')); 
        $domainrenew->appendChild($this->setAttribute('xsi:schemaLocation', XSCHEMA_DOMAIN.' domain-1.0.xsd'));
       
		$domainrenew->appendChild($this->document->createElementNS(XSCHEMA_DOMAIN, 'name', $name));
		$domainrenew->appendChild($this->document->createElementNS(XSCHEMA_DOMAIN, 'curExpDate', $curexpdate));
		$period = $domainrenew->appendChild($this->document->createElementNS(XSCHEMA_DOMAIN, 'period', $period));
		$period->appendChild($this->setAttribute('unit', 'm'));
		
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
	* Close the connection.
	* This method closes the connection to the server. Note that the
	* EPP specification indicates that clients should send a <logout>
	* command before ending the session.
	* @return boolean the result of the fclose() operation
	*/
	function Disconnect() {
		return @fclose($this->socket);
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
		
		// Fix for making microtime floats more accurate.
		ini_set('precision', 16);
		
        // Add transactionid's to all generated EPP frames with commands
        $tranId = "phpEPP-" . microtime(1) . "-" . getmypid();
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
	
	/**
	* phpEPP Destructor, just to do some garbage cleaning.
	* Removes $vars and closes EPP connection. 
	*/
    public function __destruct() {
		$this->Disconnect();
		unset($this->vars);
	}

}
