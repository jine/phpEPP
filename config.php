<?php

// EPP Server settings
define('EPP_HOST',          'sslv3://epptest2.iis.se');
define('EPP_PORT',          700);
define('EPP_TIMEOUT',       1);

// EPP Cert information
define('EPP_CERT',          '/tmp/cacert.pem');
define('EPP_CERT_PASS',     'epptestIIS123!');

// EPP Auth information
define('EPP_USER',          'jinese2');
define('EPP_PWD',           '');

// EPP Log settings
define('EPP_LOG',            true);
define('EPP_LOG_FILE',       'log.bin');


// Do we want indented XML output? (Useful for debugging)
define('FORMAT_OUTPUT',      true);

define('XMLNS_EPP',         'urn:ietf:params:xml:ns:epp-1.0');
define('XSCHEMA_EPP',       'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd');
define('XMLNS_XSCHEMA',     'http://www.w3.org/2001/XMLSchema-instance');

define('XSCHEMA_DOMAIN',    'urn:ietf:params:xml:ns:domain-1.0');
define('XSCHEMA_CONTACT',   'urn:ietf:params:xml:ns:contact-1.0');
define('XSCHEMA_HOST',      'urn:ietf:params:xml:ns:host-1.0');

define('XSCHEMA_EXTDNSSEC', 'urn:ietf:params:xml:ns:secDNS-1.0');
define('XSCHEMA_EXTIIS',    'urn:se:iis:xml:epp:iis-1.1');
define('XSCHEMA_EXTIISO',   'urn:se:iis:xml:epp:iis-1.0');
