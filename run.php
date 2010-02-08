<?php

include 'config.php';
include 'EPP.class.php';


// Padding variable for debug.. :)
$_ = "===============================\n";



// Connect to the EPP Server
$epp = new EPP();



/****************************************/
echo $_."Logging in...\n".$_;
$epp->Login(); // Add login XML
echo $epp->Process();
/****************************************/


/****************************************/
echo $_."Checking jine.se...\n".$_;
$epp->Check('domain', 'jine.se'); // Add check-domain XML
echo $epp->Process();
/****************************************/


// Close connection!
echo $_."Closing connection...\n".$_;
echo $epp->Disconnect();
