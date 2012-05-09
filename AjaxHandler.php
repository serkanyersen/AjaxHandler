<?
/**
 * MIT License
 * ===========
 *
 * Copyright (c) 2012 Serkan Yerşen <serkanyersen@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package     serkanyersen@gmail.com
 * @author      Serkan Yerşen <serkanyersen@gmail.com>
 * @copyright   2012 Serkan Yerşen.
 * @license     http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link        http://serkanyersen.github.com/AjaxHandler/
 * @version     0.5
 */

/**
 * Soft Exception will send error but use 200 as a status code instead of 400
 * Useful for JSONP requests
 */
class AjaxSoftException extends Exception {}

/**
 * Throw warning to send success with a warning message
 */
class AjaxWarning extends Exception {}

class AjaxHandler{

    private $responseContentType = /* "application/x-json"; # */ "text/javascript"; # for debugging
    private $timers = array();
    private $callback;
    private $body;
    private $requestType;

    protected $actionKey = 'action';

    /**
     * Initializer function
     * @constructor
     * @param array $request $_GET, $_POST, $_REQUEST or and associative array as a request parameters if empty $_REQUEST will be used
     */
    function __construct($request = false){

        # User did not providerequest parameters use default instead
        if($request === false){
            $request = $_REQUEST;
        }
         # Keep request time
        $this->timerStart("Request");

        # Check if an action was given
        if(!isset($request[$this->actionKey])){
            $this->error('No action was provided. Please check your request');
        }

        # if this is a JSONP request than use callback function
        $this->callback = isset($request["callback"])? $request["callback"] : false;
        $this->callback = isset($request["callbackName"])? $request["callbackName"] : $this->callback;

        # Set request
        $this->request = $request;


        # Request Type GET | PUT | POST | DELETE
        $this->requestType  = $_SERVER['REQUEST_METHOD'];

        # You can read the Request Body with this property
        $this->body         = file_get_contents('php://input');

        # Define as seconds (ie: 0.5, 2, 0.02)
        $this->lazy = 0;

        # If lazy was send in the request overwrite the hard coded one
        if(isset($this->request['lazy'])){
            $this->lazy = $this->request['lazy'];
        }

        # Set the action value
        $this->action = $request[$this->actionKey];

        # Define Error Handler
        set_error_handler(array($this, "errorHandler"));
    }

    /**
     * This will execute the ajax processes
     * @return
     */
    public function execute(){
        # run the action now
        $this->runAction();
        # If action does not end the process. End it with default
        $this->success("Operation Completed");
    }

    /**
     * Starts the timer for given title
     * @param object $title
     * @return
     */
    protected function timerStart($title){
        $this->timers[$title] = microtime(true);
    }

    /**
     * Brings back the result of time spending in seconds with floating point of milli seconds
     * Title must be exact same of the start functon
     * @param object $title
     * @return
     */
    protected function timerEnd($title){
        $end = microtime(true);
        return  sprintf("%01.3f", ($end - $this->timers[$title]));
    }

    /**
     * Safely brings data from request. No need to use isset
     * It also converts "true" "false" strings to boolean
     * @param object $key
     * @return
     */
    protected function get($key){
        if(!isset($this->request[$key])){
            return NULL;
        }

        $val = $this->request[$key];

        if(strtolower($val) == "true"){
            $val = true;
        }

        if(strtolower($val) == "false"){
            $val = false;
        }

        return $val;
    }

    /**
     * Get the Request Body
     * @return string Request Body if exists
     */
    protected function getBody(){
        return $this->body;
    }

    /**
     * Returns the type of the request
     * @return string GET | PUT | POST | DELETE
     */
    protected function getType(){
        return $this->requestType;
    }

    /**
     * Catches any error and responses with success:false
     * @param object $errno
     * @param object $message
     * @param object $filename
     * @param object $line
     */
    public function errorHandler($errno, $message, $filename, $line) {
        if (error_reporting() == 0) {
            return;
        }
        if ($errno & (E_ALL ^ E_NOTICE)) {
            $types = array(1 => 'error', 2 => 'warning', 4 => 'parse error', 8 => 'notice', 16 => 'core error', 32 => 'core warning', 64 => 'compile error', 128 => 'compile warning', 256 => 'user error', 512 => 'user warning', 1024 => 'user notice', 2048 => 'strict warning');
            $entry ="<div style='text-align:left;'><span><b>".@$types[$errno] ."</b></span>: $message <br><br>
            <span> <b>in</b> </span>: $filename <br>
            <span> <b>on line</b> </span>: $line </div>";

            error_log("Request Server Error:".$message."\nFile:".$filename."\nOn Line: ".$line);
            $this->error($entry, null, 500);
        }
    }

    /**
     * Runs the given method
     * @param object $action This optional you cal call a different action with the same parameters
     */
    private function runAction($action = false){
        # Support for manual actions
        if(!$action){
            $action = $this->action;
        }

        # Check if the action exists on the server
        if(!method_exists($this, $action)){
            return $this->error('No such action ('.$action.'). Please check your request');
        }

        try{ # try this action if it throws an error prompt it to user

            if($this->lazy){
                usleep($this->lazy * 1000000); # Speed Test for some properties
            }

            # Run the provided action
            return $this->$action();
        }catch (AjaxWarning $e){
            $this->warning = $e->getMessage();
            return $this->success("Operation Completed", array("warning" => $e->getMessage()));

        }catch(AjaxSoftException $e){
            return $this->error($err, null, 200);
        }catch(Exception $e){ # Catch if any exception was thrown
            return $this->error($err, null, 500);
        }
    }

    /**
     * Prompts a standard error response, all errors must prompt by this function
     * adds success:false automatically
     * @param object|string $message An error message, you can directly pass all parameters here
     * @param object $addHash[optional] contains the all error parameters will be sent as a response
     */
    public function error($message, $addHash = array(), $status = 400){

        if(is_array($message)){
            $status = $addHash; // If first argument is addhash then second is the status
            $addHash = $message;
        }else{
            $addHash["error"] = $message;
        }

        $addHash["success"] = false;
        $addHash["duration"] = $this->timerEnd("Request");

        # Prevent browsers to cache response
        @header("Cache-Control: no-cache, must-revalidate", true);  # HTTP/1.1
        @header("Expires: Sat, 26 Jul 1997 05:00:00 GMT", true);    # Date in the past
        @header("Content-Type: ".$this->responseContentType."; charset=utf-8", true, $status);

        if($this->callback){
            $response = $this->callback."(".json_encode($addHash).");";
        }else{
            $response = json_encode($addHash);
        }

        echo $response;
        exit;
    }

    /**
     * Prompts the request response by given hash
     * adds standard success:true message automatically
     * @param object|string $message Success message you can also pass the all parameters as an array here
     * @param object $addHash [optional] all other parameters to be sent to user as a response
     */
    public function success($message, $addHash = array(), $status = 200){
        if(is_array($message)){
            $status = $addHash; // If first argument is addhash then second is the status
            $addHash = $message;
        }else{
            $addHash["message"] = $message;
        }

        $addHash["success"] = true;
        $addHash["duration"] = $this->timerEnd("Request");

        # Prevent browsers to cache response
        @header("Cache-Control: no-cache, must-revalidate", true); # HTTP/1.1
        @header("Expires: Sat, 26 Jul 1997 05:00:00 GMT", true);   # Date in the past
        @header("Content-Type: ".$this->responseContentType."; charset=utf-8", true, $status);

        if($this->callback){
            $response = $this->callback."(".json_encode($addHash).");";
        }else{
            $response = json_encode($addHash);
        }

        echo $response;
        exit;
    }
}