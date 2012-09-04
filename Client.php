<?php
/**
 * Novutec Domain Tools
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @category   Novutec
 * @package    MessagePackRpcClient
 * @copyright  Copyright (c) 2007 - 2012 Novutec Inc. (http://www.novutec.com)
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */

/**
 * @namespace Novutec\MessagePackRpcClient
 */
namespace Novutec\MessagePackRpcClient;

/**
 * Client of MessagePackRpcClient
 * 
 * @category   Novutec
 * @package    MessagePackRpcClient
 * @copyright  Copyright (c) 2007 - 2012 Novutec Inc. (http://www.novutec.com)
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @link       http://msgpack.org/
 * @link       https://github.com/msgpack/msgpack-rpc
 */
class Client
{

    /**
	 * Address to the socket to connect to
	 * 
	 * @var string
	 * @access private
	 */
    private $socket;

    /**
	 * Socket resource also referred to as an endpoint of communication
	 * 
	 * @var resource
	 * @access private
	 */
    private $sock;

    /**
	 * Initiate a connection to address using the socket resource,
	 * which must be a valid socket resource
	 * 
	 * @var boolean
	 * @access private
	 */
    private $conn;

    /**
	 * The maximum number of bytes read is specified by the length parameter.
	 *  
	 * @var integer
	 * @access private
	 */
    private $length;

    /**
	 * Activate log messages
	 * 
	 * @var boolean 
	 * @access private
	 */
    private $debug;

    /**
	 * Log message is appended to the file destination.
	 * 
	 * @var string
	 * @access private
	 */
    private $destination;

    /**
	 * Number of seconds until connect() method calls timeout.
	 * By default 30 seconds
	 * 
	 * @var integer
	 * @access private
	 */
    private $timeout;

    /**
	 * Set variables and calls connect() method
	 * 
	 * @param  string $socket
	 * @param  integer $length
	 * @param  integer $timeout
	 * @param  boolean $debug
	 * @param  string $destination
	 * @return boolean
	 */
    public function __construct($socket = 'tcp://localhost:9000', $length = 1024, $timeout = 30, $debug = false, 
            $destination = '/log/msgpack-rpc-client.log')
    {
        $this->socket = $socket;
        $this->length = $length;
        $this->timeout = $timeout;
        
        if ($debug) {
            $this->set_debug($debug, $destination);
        }
    }

    /**
	 * Set debug mode and log file destination
	 * 
	 * @param  boolean $debug
	 * @param  string $log_destination
	 * @return void
	 */
    public function set_debug($debug = false, $destination = '/log/msgpack-rpc-client.log')
    {
        $this->debug = $debug;
        $this->destination = $destination;
    }

    /**
	 * Creates and initiates a socket connection
	 * 
	 * @throws ConnectErrorException
	 * @return boolean
	 */
    public function connect()
    {
        if ($this->conn) {
            return true;
        }
        
        $errno = $errstr = null;
        $this->sock = @stream_socket_client($this->socket, $errno, $errstr, $this->timeout);
        
        if (! is_resource($this->sock)) {
            throw \Novutec\MessagePackRpcClient\AbstractException::factory('ConnectError', $errstr);
        }
        
        if ($this->debug) {
            $this->log('Connected to Remote Socket: ' . $this->socket, $this->sock);
        }
        
        $this->conn = true;
        return $this->conn;
    }

    /**
	 * Sends data to the socket and receives the response
	 * 
	 * @throws WriteErrorException
	 * @throws ReadErrorException
	 * @throws ResponseErrorException
	 * @throws TypeErrorException
	 * @throws ServerErrorException
	 * @param  string &$method
	 * @param  array &$params
	 * @param  integer &$msgid
	 * @return mixed
	 */
    public function call(&$method, &$params = array(), &$msgid = 0)
    {
        if (! $this->conn) {
            $this->connect();
        }
        
        $buffer = '';
        $buffer_length = 0;
        $data = array(0 => 0, 1 => $msgid, 2 => $method, 3 => $params);
        
        @stream_set_blocking($this->sock, 1);
        
        if ($this->debug) {
            $this->log('Set option to socket: blocking', $this->sock);
        }
        
        $packed_data = $this->pack($data);
        $send = @fwrite($this->sock, $packed_data);
        
        if ($this->debug) {
            $this->log('Write to socket: ' . json_encode($data), $send);
        }
        
        if ($send != strlen($packed_data)) {
            throw \Novutec\MessagePackRpcClient\AbstractException::factory('WriteError', 'Could not write to the socket. (tried/send): ' . '(' . $send . '/' . strlen($packed_data) . ')');
        }
        
        @stream_set_blocking($this->sock, 0);
        
        if ($this->debug) {
            $this->log('Set option to socket: non-blocking', $this->sock);
        }
        
        $read = $write = array($this->sock);
        $except = null;
        
        $unpacker = new \MessagePackUnpacker();
        
        do {
            if (stream_select($read, $write, $except, $this->timeout) === false) {
                break;
            }
            
            $recv = @fread($this->sock, $this->length);
            
            if ($this->debug && $recv != '') {
                $this->log('Read from socket - length: ' . strlen($recv), $recv);
            }
            
            $unpacker->feed($recv);
            
            if ($recv === false) {
                throw \Novutec\MessagePackRpcClient\AbstractException::factory('ReadError', 'Could not read from socket.');
            }
        } while (! $unpacker->execute());
        
        $data = $unpacker->data();
        $unpacker->reset();
        
        if ($this->debug) {
            $this->log('Unpack received data: ' . json_encode($data), $data);
        }
        
        if (sizeof($data) != 4) {
            throw \Novutec\MessagePackRpcClient\AbstractException::factory('ResponseError', 'Number of elements within received data is wrong.');
        }
        
        if ($data[0] != 1) {
            throw \Novutec\MessagePackRpcClient\AbstractException::factory('TypeError', 'Received data has the wrong type.');
        }
        
        if ($data[2] != '') {
            throw \Novutec\MessagePackRpcClient\AbstractException::factory('ServerError', 'Got an error from server.');
        }
        
        return $data[3];
    }

    /**
	 * Use MsgPack to serialize
	 * 
	 * @see    https://github.com/msgpack/msgpack-rpc
	 * @param  array &$data
	 * @return mixed
	 */
    private function pack(&$data)
    {
        return msgpack_pack($data);
    }

    /**
	 * Use MsgPack to unserialize
	 * 
	 * @see    https://github.com/msgpack/msgpack-rpc
	 * @param  string &$data
	 * @return array
	 */
    private function unpack(&$data)
    {
        return msgpack_unpack($data);
    }

    /**
	 * Uses PHP error_log() function to write messages to file destination
	 * 
	 * @param  string $msg
	 * @param  boolean $bool
	 * @return boolean
	 */
    private function log($msg, $bool = false)
    {
        return error_log('[' . date('Y-m-d H:i:s') . '] ' .
                 (! $bool ? 'ERROR: ' . socket_strerror(socket_last_error()) . ' - ' : '') . $msg .
                 "\n", 3, MSGPACK_PATH . $this->destination);
    }

    /**
	 * Closes a socket resource
	 * 
	 * @return void
	 */
    public function __destruct()
    {
        if ($this->conn) {
            fclose($this->sock);
        }
    }
}