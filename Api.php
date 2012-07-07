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
 * define MessagePackRpcClient Path
 */
define('MSGPACK_PATH', dirname(__FILE__));

/**
 * @see Client
 */
require_once MSGPACK_PATH . '/Client.php';

/**
 * @see Exception
 */
require_once MSGPACK_PATH . '/Exception/AbstractException.php';

/**
 * Api class of MessagePackRpcClient
 * 
 * @category   Novutec
 * @package    MessagePackRpcClient
 * @copyright  Copyright (c) 2007 - 2012 Novutec Inc. (http://www.novutec.com)
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
class Api extends Client
{

    /**
     * Should the exceptions be thrown or caugth and trapped in the response?
     *
     * @var boolean
     * @access protected
     */
    protected $throwExceptions = false;

    /**
	 * Call parent constructer
	 * 
	 * @param  string $socket
	 * @param  integer $length
	 * @param  integer $timeout
	 * @param  boolean $debug
	 * @param  string $destination
	 * @return boolean
	 */
    public function __construct($socket = 'tcp://localhost:8000', $length = 1024, $timeout = 30, $debug = false, 
            $destination = '/log/msgpack-rpc-client.log')
    {
        parent::__construct($socket, $length, $timeout, $debug, $destination);
    }

    /**
	 * Calls parent call() method to send data to the socket and to receive the response
	 * 
	 * @param  string $method
	 * @param  array $params
	 * @return mixed
	 */
    public function __call($method, $params)
    {
        try {
            return parent::call($method, $params);
        } catch (\Novutec\MessagePackRpcClient\AbstractException $e) {
            if ($this->throwExceptions) {
                throw $e;
            }
            
            return array('code' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

    /**
     * Set the throwExceptions flag
     *
     * Set whether exceptions encounted in the dispatch loop should be thrown
     * or caught and trapped in the response object.
     *
     * Default behaviour is to trap them in the response object; call this
     * method to have them thrown.
     *
     * @param  boolean $throwExceptions
     * @return void
     */
    public function throwExceptions($throwExceptions = false)
    {
        $this->throwExceptions = filter_var($throwExceptions, FILTER_VALIDATE_BOOLEAN);
    }
}