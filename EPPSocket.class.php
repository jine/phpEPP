<?php
class EPPSocket {

        /**
        * @var resource the socket resource, once connected
        */
        var $socket;
		
        function __construct() {
                /* Placeholder */
        }
		
        // Proctect the magic clone function!
        protected function __clone() {
                /* Placeholder */
        }
		
        /**
        * Establishes a connect to the server
        * This method establishes the connection to the server. If the connection was
        * established, then this method will call getFrame() and return the EPP <greeting>
        * frame which is sent by the server upon connection.
        * @param string the hostname and protocol (tls://example.com)
        * @param integer the TCP port
        * @param integer the timeout in seconds
        * @return on success a string containing the server <greeting>
        */
        function Connect($host, $port=700, $timeout=1) {
			
                if (!$this->socket = stream_socket_client($host.':'.$port, $errno, $errstr, $timeout)) {
                        die("Failed to connect!");
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
        * Send an XML frame to the server.
        * This method sends an EPP frame to the server.
        * @param string the XML data to send
        * @return boolean the result of the fwrite() operation
        */
        function sendFrame($xml) {
			return fwrite($this->socket, pack('N', (strlen($xml)+4)).$xml);
        }
		
        /**
        * a wrapper around sendFrame() and getFrame()
        * @param string $xml the frame to send to the server
        * @return string the frame returned by the server
        */
        function sendRequest($xml) {
                $this->sendFrame($xml);
                return $this->getFrame();
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
		
}
