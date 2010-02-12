<?php

ini_set("display_errors",1);

include 'config.php';
include 'EPP.class.php';


// Padding variable for debug.. :)
$_ = "===============================\n";



// Connect to the EPP Server
$epp = new EPP();
#$epp = new EPP(false); // Dont connect to epp server


/****************************************/
#echo $_."Logging in...\n".$_;
$epp->Login(); // Add login XML
$epp->Process();
/****************************************/

// Echos the result code and message
#echo $epp->getResultCode();
#echo "\n";
#echo $epp->getResultMsg();

/****************************************/
#echo $_."Renewing jinetest2.se with 12 months...\n".$_;
#$epp->Renew('jinetest2.se', '2011-08-08', 12);
#echo $epp->Process();
/****************************************/


/****************************************/
#echo $_."Showing messages...\n".$_;
#$epp->Poll();
#echo $epp->Process();
/****************************************/


/****************************************/
#echo $_."Remove message...\n".$_;
#$epp->Poll('ack', 12632);
#echo $epp->Process();
/****************************************/

/****************************************/
#echo $_."Show info 'bout jinetest2.se...\n".$_;
#$epp->Info('domain', 'jinetest2.se');
#echo $epp->Process();
/****************************************/

/****************************************/
echo $_."Show info 'bout contact jimjin2467-00001...\n".$_;
$epp->Info('contact', 'jimjin2467-00001');
echo $epp->Process();
/****************************************/

#echo $_."Running custom XML...\n".$_;
/*$custom = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><epp xmlns="urn:ietf:params:xml:ns:epp-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">  <command>    <update>      <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd"><domain:name>jinetest2.se</domain:name><domain:add>  <domain:contact type="tech">jimjin2467-00001</domain:contact></domain:add>  </domain:update>    </update>  </command></epp>';
$epp->setXML($custom);*/
#echo $epp->getXML(false);

#echo $epp->Process();

/****************************************/
#echo $_."Transfering jinetest2.se to me...\n".$_;
#$epp->Transfer('xn--sngubbe-b1a.se', 'EmilV123-☃');
#echo $epp->Process();
/****************************************/

#$epp->Update('domain',2);
#echo $epp->getXML();

/****************************************/
#echo $_."Checking jinetest.se...\n".$_;
#$epp->Check('domain', 'jinetest.se');
#echo $epp->Process();
/****************************************/

#'xn--sngubbe-b1a.se', 'EmilV123-☃'
/****************************************/
echo $_."Updating jinetest2...\n".$_;
/*
$update = array(
	'chg' => array(
		'pw' => 'EmilV123-☃',
		#'registrant' => 'iostream',
	),
	'rem' => array(
		'ns' => array('ns1.iis.se'),
	),
	'add' => array(
		'ns' => array('ns1.iis.se', 'ns1.jine.se'),
	),
);

$update = array(
	'rem' => array(
		'hosts' => array(
			#array('v6', 'enipv6'),
			#array('v4', '172.172.171.21'),
		),
	),
	'add' => array(
		'hosts' => array(
			array('v4', '81.123.123.1'),
		),
	),
);

$epp->Update('host', 'ns1.jine.se', $update);*/



$update = array(
	'chg' => array(
		'name' => 'Jim Nelin',
		'vatno' => '',
		#'org' => 'HAX AB',
		#'street' => array('Härbrevägen 37'),
		#'city' => 'Skogås',
		#'pc' => '14234',
		#'cc' => 'BR',
		#'fax' => '',
		#'email' => 'hax@jne.se',
		#'voice' => '+46.737586061',
	),
);
$epp->Update('contact', 'jimjin2467-00001', $update);

echo $epp->getXML(false);
echo $epp->Process();
/****************************************/


/****************************************/
#echo $_."Checking jinetest2.se...\n".$_;
#$epp->Info('contact', 'jimjin2467-00001'); // Add check-domain XML
#echo $epp->Process();
/****************************************/


/****************************************/
#echo $_."Creating jinetest2.se...\n".$_;
#$domain = array(
#    'name' => 'jinetest2.se',
#    'period' => 18, // Months
#    'ns' => array('ns1.jine.se'),
#    'registrant' => 'jimnel0115-00001',
#    'pw' => '.Hax0r+1zzZ!.',
#);
#$epp->Create("domain", $domain);
#echo $epp->Process();
/****************************************/


// Close connection!
#echo $_."Closing connection...\n".$_;
$epp->Disconnect();
