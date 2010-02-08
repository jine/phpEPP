<?php

include 'config.php';
include 'EPPXML.class.php';
include 'EPPSocket.class.php';

// Connect to the EPP Server
$eppsocket = new EPPSocket();
$eppsocket->Connect('ssl://epptest2.iis.se');

// Create basic XML structure.
$xml = new EPPXML();

/****************************************/
$xml->Login(); // Add login XML

echo "Logging in...\n";
echo $eppsocket->sendRequest($xml->getXML());
/****************************************/


/****************************************/
$xml->Check('domain', 'jine.se'); // Add check-domain XML

echo "Checking jine.se...\n";
echo $eppsocket->sendRequest($xml->getXML());
/****************************************/

// Close connection!
echo "Closing connection...\n";
echo $eppsocket->Disconnect();
