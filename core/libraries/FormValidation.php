<?php
    defined('ROOT_PATH') || exit('Access denied');
    /**
     * TNH Framework
     *
     * A simple PHP framework using HMVC architecture
     *
     * This content is released under the GNU GPL License (GPL)
     *
     * Copyright (C) 2017 Tony NGUEREZA
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version 3
     * of the License, or (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program; if not, write to the Free Software
     * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
     */


        class FormValidation extends BaseClass {
		 
        /**
         * The form validation status
         * @var boolean
         */
        protected $_success = false;

        /**
         * The list of errors messages
         * @var array
         */
        protected $_errorsMessages = array();
        
        // Array of rule sets, fieldName => PIPE seperated ruleString
        protected $_rules = array();
        
        // Array of errors, niceName => Error Message
        protected $_errors = array();
        
        // Array of post Key => Nice name labels
        protected $_labels = array();
        
        /**
         * The errors delimiters
         * @var array
         */
        protected $_allErrorsDelimiter = array('<div class="error">', '</div>');

        /**
         * The each error delimiter
         * @var array
         */
        protected $_eachErrorDelimiter = array('<p class="error">', '</p>');
        
        /**
         * Indicated if need force the validation to be failed
         * @var boolean
         */
        protected $_forceFail = false;

        /**
         * The list of the error messages overrides by the original
         * @var array
         */
        protected $_errorMsgOverrides = array();

        /**
         * The data to be validated, the default is to use $_POST
         * @var array
         */
        private $data = array();


        /**
         * The database instance
         * @var object
         */
        private $databaseInstance = null;

        /**
         * Set all errors and rule sets empty, and sets success to false.
         *
         * @return void
         */
        public function __construct() {
            parent::__construct();
           
            //Load form validation language message
            Loader::lang('form_validation');
            $obj = & get_instance();
            $this->_errorsMessages = array(
                        'required'         => $obj->lang->get('fv_required'),
                        'min_length'       => $obj->lang->get('fv_min_length'),
                        'max_length'       => $obj->lang->get('fv_max_length'),
                        'exact_length'     => $obj->lang->get('fv_exact_length'),
                        'less_than'        => $obj->lang->get('fv_less_than'),
                        'greater_than'     => $obj->lang->get('fv_greater_than'),
                        'matches'          => $obj->lang->get('fv_matches'),
                        'valid_email'      => $obj->lang->get('fv_valid_email'),
                        'not_equal'        => array(
                                                'post:key' => $obj->lang->get('fv_not_equal_post_key'),
                                                'string'   => $obj->lang->get('fv_not_equal_string')
                                            ),
                        'depends'          => $obj->lang->get('fv_depends'),
                        'is_unique'        => $obj->lang->get('fv_is_unique'),
                        'is_unique_update' => $obj->lang->get('fv_is_unique_update'),
                        'exists'           => $obj->lang->get('fv_exists'),
                        'regex'            => $obj->lang->get('fv_regex'),
                        'in_list'          => $obj->lang->get('fv_in_list'),
                        'numeric'          => $obj->lang->get('fv_numeric'),
                        'callback'         => $obj->lang->get('fv_callback'),
                    );
            $this->_resetValidation();
            if (is_array($obj->request->post(null))) {
                $this->setData($obj->request->post(null));
            }
        }

        /**
         * Set the database instance
         * @param object $database the database instance
         */
        public function setDatabase(Database $database) {
            $this->databaseInstance = $database;
            return $this;
        }

        /**
         * Get the database instance
         * @return object the database instance
         */
        public function getDatabase() {
            return $this->databaseInstance;
        }

        /**
         * Reset the form validation instance
         */
        protected function _resetValidation() {
            $this->_rules                = array();
            $this->_labels               = array();
            $this->_errorMsgOverrides = array();
            $this->_errors               = array();
            $this->_success              = false;
            $this->_forceFail            = false;
            $this->data                  = array();
        }

        /**
         * Set the form validation data
         * @param array $data the values to be validated
         *
         * @return FormValidation Current instance of object.
         */
        public function setData(array $data) {
            $this->logger->debug('Setting the form validation data, the values are: ' . stringfy_vars($data));
            $this->data = $data;
            return $this;
        }

        /**
         * Get the form validation data
         * @return array the form validation data to be validated
         */
        public function getData() {
            return $this->data;
        }

        /**
         * Get the validation function name to validate a rule
         *
         * @return string the function name
         */
        protected function _toCallCase($funcName, $prefix = '_validate') {
            $funcName = strtolower($funcName);
            $finalFuncName = $prefix;
            foreach (explode('_', $funcName) as $funcNamePart) {
                $finalFuncName .= strtoupper($funcNamePart[0]) . substr($funcNamePart, 1);
            }
            return $finalFuncName;
        }

        /**
         * Returns the boolean of the data status success. It goes by the simple
         *
         * @return boolean Whether or not the data validation has succeeded
         */
        public function isSuccess() {
            return $this->_success;
        }

        /**
         * Checks if the request method is POST or the Data to be validated is set
         *
         * @return boolean Whether or not the form has been submitted or the data is available for validation.
         */
        public function canDoValidation() {
            return get_instance()->request->method() === 'POST' || !empty($this->data);
        }

        /**
         * Runs _run once POST data has been submitted or data is set manually.
         *
         * @return boolean
         */
        public function run() {
            if ($this->canDoValidation()) {
                $this->logger->info('The data to validate are listed below: ' . stringfy_vars($this->getData()));
                $this->_run();
            }
            return $this->isSuccess();
        }

        /**
         * Validate the CSRF 
         * @return boolean
         */
        protected function validateCSRF() {
            if (get_instance()->request->method() == 'POST') {
                $this->logger->debug('Check if CSRF is enabled in configuration');
                //first check for CSRF
                if (get_config('csrf_enable', false) && !Security::validateCSRF()) {
                    show_error('Invalide data, Cross Site Request Forgery do his job, the data to validate is corrupted.');
                    return false;
                } else {
                    $this->logger->info('CSRF is not enabled in configuration or not set manully, no need to check it');
                }
            }
            return true;
        }
        
        /**
         * Takes and trims each data, if it has any rules, we parse the rule string and run
         * each rule against the data value. Sets _success to true if there are no errors
         * afterwards.
         */
        protected function _run() {
            //validate CSRF
            if (!$this->validateCSRF()) {
                return;
            }
            /////////////////////////////////////////////
            $this->_forceFail = false;

            foreach ($this->getData() as $inputName => $inputVal) {
                if (is_array($this->data[$inputName])) {
                    $this->data[$inputName] = array_map('trim', $this->data[$inputName]);
                } else {
                    $this->data[$inputName] = trim($this->data[$inputName]);
                }

                if (array_key_exists($inputName, $this->_rules)) {
                    foreach ($this->_parseRuleString($this->_rules[$inputName]) as $eachRule) {
                        $this->_validateRule($inputName, $this->data[$inputName], $eachRule);
                    }
                }
            }
            $this->_success = empty($this->_errors) && $this->_forceFail === false;
        }

        /**
         * Adds a rule to a form data validation field.
         *
         * @param string $inputField Name of the field or the data key to add a rule to
         * @param string $ruleSets PIPE seperated string of rules
         *
         * @return FormValidation Current instance of object.
         */
        public function setRule($inputField, $inputLabel, $ruleSets) {
            $this->_rules[$inputField] = $ruleSets;
            $this->_labels[$inputField] = $inputLabel;
            $this->logger->info('Set the field rule: name [' . $inputField . '], label [' . $inputLabel . '], rules [' . $ruleSets . ']');
            return $this;
        }

        /**
         * Takes an array of rules and uses setRule() to set them, accepts an array
         * of rule names rather than a pipe-delimited string as well.
         * @param array $ruleSets
         *
         * @return FormValidation Current instance of object.
         */
        public function setRules(array $ruleSets) {
            foreach ($ruleSets as $ruleSet) {
                $pipeDelimitedRules = null;
                if (is_array($ruleSet['rules'])) {
                    $pipeDelimitedRules = implode('|', $ruleSet['rules']);
                } else {
                    $pipeDelimitedRules = $ruleSet['rules'];
                }
                $this->setRule($ruleSet['name'], $ruleSet['label'], $pipeDelimitedRules);
            }
            return $this;
        }

        /**
         * This method creates the global errors delimiter, each argument occurs once, at the beginning, and
         * end of the errors block respectively.
         *
         * @param string $start Before block of errors gets displayed, HTML allowed.
         * @param string $end After the block of errors gets displayed, HTML allowed.
         *
         * @return FormValidation Current instance of object.
         */
        public function setErrorsDelimiter($start, $end) {
            $this->_allErrorsDelimiter[0] = $start;
            $this->_allErrorsDelimiter[1] = $end;
            return $this;
        }

        /**
         * This is the individual error delimiter, each argument occurs once before and after
         * each individual error listed.
         *
         * @param string $start Displayed before each error.
         * @param string $end Displayed after each error.
         * 
         * @return FormValidation Current instance of object.
         */
        public function setErrorDelimiter($start, $end) {
            $this->_eachErrorDelimiter[0] = $start;
            $this->_eachErrorDelimiter[1] = $end;
            return $this;
        }

        /**
         * Get the each errors delimiters
         *
         * @return array
         */
        public function getErrorDelimiter() {
            return $this->_eachErrorDelimiter;
        }

        /**
         * Get the all errors delimiters
         *
         * @return array
         */
        public function getErrorsDelimiter() {
            return $this->_allErrorsDelimiter;
        }

        /**
         * This sets a custom error message that can override the default error phrase provided
         * by FormValidation, it can be used in the format of setMessage('rule', 'error phrase')
         * which will globally change the error phrase of that rule, or in the format of:
         * setMessage('rule', 'fieldname', 'error phrase') - which will only change the error phrase for
         * that rule, applied on that field.
         *
         * @return boolean True on success, false on failure.
         */
        public function setMessage() {
            $numArgs = func_num_args();
            if ($numArgs == 2) {
               foreach ($this->post(null) as $key => $val) {
                    $this->_errorMsgOverrides[$key][func_get_arg(0)] = func_get_arg(1);
                }
                return true;
            } else if ($numArgs == 3) {
                $this->_errorMsgOverrides[func_get_arg(1)][func_get_arg(0)] = func_get_arg(2);
                 return true;
            }
            return false;
        }

        /**
         * Adds a custom error message in the errorSet array, that will
         * forcibly display it.
         *
         * @param string $inputName The form input name or data key
         * @param string $errorMessage Error to display
         *
         * @return formValidation Current instance of the object
         */
        public function setCustomError($inputName, $errorMessage) {
            $errorMessage = str_replace('%1', $this->_labels[$inputName], $errorMessage);
            $this->_errors[$inputName] = $errorMessage;
            return $this;
        }

        /**
         * Allows for an accesor to any/all post values, if a value of null is passed as the key, it
         * will recursively find all keys/values of the $_POST array or data array. It also automatically trims
         * all values.
         *
         * @param string $key Key of $this->data to be found, pass null for all Key => Val pairs.
         * @param boolean $trim Defaults to true, trims all $this->data values.
         * @return string|array Array of post or data values if null is passed as key, string if only one key is desired.
         */
        public function post($key = null, $trim = true) {
            $returnValue = null;
            if (is_null($key)) {
                $returnValue = array();
                foreach ($this->data  as $key => $val) {
                    $returnValue[$key] = $this->post($key, $trim);
                }
            } else if (array_key_exists($key, $this->data)) {
                $returnValue = $this->data[$key];
                if ($trim) {
                    if (is_array($this->data[$key])) {
                       $returnValue = array_map('trim', $this->data[$key]);
                    } else {
                        $returnValue = trim($this->data[$key]);
                    }
                }
            }
            return $returnValue;
        }

        /**
         * Gets all errors from errorSet and displays them, can be echo out from the
         * function or just returned.
         *
         * @param boolean $limit number of error to display or return
         * @param boolean $echo Whether or not the values are to be returned or displayed
         *
         * @return string|void Errors formatted for output
         */
        public function displayErrors($limit = null, $echo = true) {
            list($errorsStart, $errorsEnd) = $this->_allErrorsDelimiter;
            list($errorStart, $errorEnd) = $this->_eachErrorDelimiter;
            $errorOutput = $errorsStart;
            $i = 0;
            if (!empty($this->_errors)) {
                foreach ($this->_errors as $fieldName => $error) {
                    if ($i === $limit) { 
                        break; 
                    }
                    $errorOutput .= $errorStart;
                    $errorOutput .= $error;
                    $errorOutput .= $errorEnd;
                    $i++;
                }
            }
            $errorOutput .= $errorsEnd;
            if (!$echo) {
                return $errorOutput;
            }
            echo $errorOutput;
        }

        /**
         * Returns raw array of errors in no format instead of displaying them
         * formatted.
         *
         * @return array
         */
        public function returnErrors() {
            return $this->_errors;
        }

        /**
         * Breaks up a PIPE seperated string of rules, and puts them into an array.
         *
         * @param string $ruleString String to be parsed.
         *
         * @return array Array of each value in original string.
         */
        protected function _parseRuleString($ruleString) {
            $ruleSets = array();
            /*
            //////////////// hack for regex rule that can contain "|"
            */
            if (strpos($ruleString, 'regex') !== false) {
                $regexRule = array();
                $rule = '#regex\[\/(.*)\/([a-zA-Z0-9]?)\]#';
                preg_match($rule, $ruleString, $regexRule);
                $ruleStringTemp = preg_replace($rule, '', $ruleString);
                if (!empty($regexRule[0])) {
                    $ruleSets[] = $regexRule[0];
                }
                 $ruleStringRegex = explode('|', $ruleStringTemp);
                foreach ($ruleStringRegex as $rule) {
                    $rule = trim($rule);
                    if ($rule) {
                        $ruleSets[] = $rule;
                    }
                }
                 
            }
            /***********************************/
            else {
                if (strpos($ruleString, '|') !== FALSE) {
                    $ruleSets = explode('|', $ruleString);
                } else {
                    $ruleSets[] = $ruleString;
                }
            }
            return $ruleSets;
        }

        /**
         * Returns whether or not a field obtains the rule "required".
         *
         * @param string $fieldName Field to check if required.
         *
         * @return boolean Whether or not the field is required.
         */
        protected function _fieldIsRequired($fieldName) {
            $rules = $this->_parseRuleString($this->_rules[$fieldName]);
            return (in_array('required', $rules));
        }

        /**
         * Takes a data input name, it's value, and the rule it's being validated against (ex: max_length[16])
         * and adds an error to the errorSet if it fails validation of the rule.
         *
         * @param string $inputName Name or key of the validation data
         * @param string $inputVal Value of the validation data
         * @param string $ruleName Rule to be validated against, including args (exact_length[5])
         * @return void
         */
        protected function _validateRule($inputName, $inputVal, $ruleName) {
            if (is_array($inputVal)) {
                foreach ($inputVal as $value) {
                    $this->_validateRule($inputName, $value, $ruleName);
                }
                return;
            }
            $this->logger->debug('Rule validation of field [' . $inputName . '], value [' . $inputVal . '], rule [' . $ruleName . ']');
            // Array to store args
            $ruleArgs = array();

            preg_match('/\[(.*)\]/', $ruleName, $ruleArgs);

            // Get the rule arguments, realRule is just the base rule name
            // Like min_length instead of min_length[3]
            $ruleName = preg_replace('/\[(.*)\]/', '', $ruleName);
            
            if (method_exists($this, $this->_toCallCase($ruleName))) {
                $methodToCall = $this->_toCallCase($ruleName);
                call_user_func(array($this, $methodToCall), $inputName, $ruleName, $ruleArgs);
            }
            return;
        }

        /**
         * Set error for the given field or key
         *
         * @param string $inputName the input or key name
         * @param string $ruleName the rule name
         * @param array|string $replacements
         */
        protected function _setError($inputName, $ruleName, $replacements = array()) {
            $rulePhraseKeyParts = explode(',', $ruleName);
            $rulePhrase = null;
            foreach ($rulePhraseKeyParts as $rulePhraseKeyPart) {
                if (array_key_exists($rulePhraseKeyPart, $this->_errorsMessages)) {
                    $rulePhrase = $this->_errorsMessages[$rulePhraseKeyPart];
                } else {
                    $rulePhrase = $rulePhrase[$rulePhraseKeyPart];
                }
            }
            // Any overrides?
            if (array_key_exists($inputName, $this->_errorMsgOverrides) && array_key_exists($ruleName, $this->_errorMsgOverrides[$inputName])) {
                $rulePhrase = $this->_errorMsgOverrides[$inputName][$ruleName];
            }
            // Type cast to array in case it's a string
            $replacements = (array) $replacements;
            $replacementCount = count($replacements);
            for ($i = 1; $i <= $replacementCount; $i++) {
                $key = $i - 1;
                $rulePhrase = str_replace('%' . $i, $replacements[$key], $rulePhrase);
            }
            if (!array_key_exists($inputName, $this->_errors)) {
                $this->_errors[$inputName] = $rulePhrase;
            }
        }

        /**
         * Used to run a callback for the callback rule, as well as pass in a default
         * argument of the data value. For example the username field having a rule:
         * callback[userExists] will call the function userExists() with parameter data[username].
         *
         * @param type $inputArg
         * @param string $callbackFunc
         *
         * @return boolean
         */
        protected function _runCallback($inputArg, $callbackFunc) {
            return call_user_func($callbackFunc, $inputArg);
        }

        /**
         * Gets a specific label of a specific field input name.
         *
         * @param string $inputName
         *
         * @return string
         */
        protected function _getLabel($inputName) {
            return (array_key_exists($inputName, $this->_labels)) ? $this->_labels[$inputName] : $inputName;
        }
		
        /**
         * Peform validation for the rule "required"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("required")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateRequired($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if ($inputVal == '') {
                $this->_setError($inputName, $ruleName, $this->_getLabel($inputName));
            }
        }

        /**
         * Perform validation for the honey pot so means for the validation to be failed
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateHoneypot($inputName, $ruleName, array $ruleArgs) {
            if ($this->data[$inputName] != '') {
                $this->_forceFail = true;
            }
        }

        /**
         * Peform validation for the rule "callback"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("callback")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateCallback($inputName, $ruleName, array $ruleArgs) {
            if (function_exists($ruleArgs[1]) && array_key_exists($inputName, $this->data)) {
                $result = $this->_runCallback($this->data[$inputName], $ruleArgs[1]);
                if (!$result) {
                    $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName)));
                }
            }
        }

        /**
         * Peform validation for the rule "depends"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("depends")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateDepends($inputName, $ruleName, array $ruleArgs) {
            if (array_key_exists($ruleArgs[1], $this->_errors)) {
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName), $this->_getLabel($ruleArgs[1])));
            }
        }

        /**
         * Peform validation for the rule "not_equal"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("not_equal")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateNotEqual($inputName, $ruleName, array $ruleArgs) {
            $canNotEqual = explode(',', $ruleArgs[1]);
            foreach ($canNotEqual as $doNotEqual) {
                $inputVal = $this->post($inputName);
                if (preg_match('/post:(.*)/', $doNotEqual)) {
                    if ($inputVal == $this->data[str_replace('post:', '', $doNotEqual)]) {
                        $this->_setError($inputName, $ruleName . ',post:key', array($this->_getLabel($inputName), $this->_getLabel(str_replace('post:', '', $doNotEqual))));
                        continue;
                    }
                } else {
                    if ($inputVal == $doNotEqual) {
                        $this->_setError($inputName, $ruleName . ',string', array($this->_getLabel($inputName), $doNotEqual));
                        continue;
                    }
                }
            }
        }

        /**
         * Peform validation for the rule "matches"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("matches")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateMatches($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if ($inputVal != $this->data[$ruleArgs[1]]) {
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName), $this->_getLabel($ruleArgs[1])));
            }
        }

        /**
         * Peform validation for the rule "valid_email"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("valid_email")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateValidEmail($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if (!preg_match("/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i", $inputVal)) {
                if (!$this->_fieldIsRequired($inputName) && strlen($this->data[$inputName]) <= 0) {
                    return;
                }
                $this->_setError($inputName, $ruleName, $this->_getLabel($inputName));
            }
        }

        /**
         * Peform validation for the rule "exact_length"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("exact_length")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateExactLength($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if (strlen($inputVal) != $ruleArgs[1]) { // $ruleArgs[0] is [length] $rulesArgs[1] is just length
                if (!$this->_fieldIsRequired($inputName) && strlen($this->data[$inputName]) <= 0) {
                    return;
                }
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName), $this->_getLabel($ruleArgs[1])));
            }
        }

        /**
         * Peform validation for the rule "max_length"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("max_length")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateMaxLength($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if (strlen($inputVal) > $ruleArgs[1]) { // $ruleArgs[0] is [length] $rulesArgs[1] is just length
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName), $this->_getLabel($ruleArgs[1])));
            }
        }

        /**
         * Peform validation for the rule "min_length"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("min_length")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateMinLength($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if (strlen($inputVal) < $ruleArgs[1]) { // $ruleArgs[0] is [length] $rulesArgs[1] is just length
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName), $this->_getLabel($ruleArgs[1])));
            }
        }
    	
        /**
         * Peform validation for the rule "less_than"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("less_than")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateLessThan($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if ($inputVal >= $ruleArgs[1]) { 
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName), $this->_getLabel($ruleArgs[1])));
            }
        }
    	
        /**
         * Peform validation for the rule "greater_than"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("greater_than")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateGreaterThan($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if ($inputVal <= $ruleArgs[1]) {
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName), $this->_getLabel($ruleArgs[1])));
            }
        }
    	
        /**
         * Peform validation for the rule "numeric"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("numeric")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateNumeric($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if (!is_numeric($inputVal)) {
                if (!$this->_fieldIsRequired($inputName) && strlen($this->data[$inputName]) <= 0) {
                    return;
                }
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName)));
            }
        }

        /**
         * Peform validation for the rule "in_list"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("in_list")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateInList($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            $list = explode(',', $ruleArgs[1]);
            $list = array_map('trim', $list);
            if (!in_array($inputVal, $list)) {
                if (!$this->_fieldIsRequired($inputName) && strlen($this->data[$inputName]) <= 0) {
                    return;
                }
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName), $this->_getLabel($ruleArgs[1])));
            }
        }

        /**
         * Peform validation for the rule "regex"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("regex")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateRegex($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            $regex = $ruleArgs[1];
            if (!preg_match($regex, $inputVal)) {
                if (!$this->_fieldIsRequired($inputName) && strlen($this->data[$inputName]) <= 0) {
                    return;
                }
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName)));
            }
        }
		
        /**
         * Peform validation for the rule "exists"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("exists")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateExists($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if (!is_object($this->databaseInstance)) {
                $obj = & get_instance();
                if (isset($obj->database)) {
                    $this->databaseInstance = $obj->database;
                } 
            }
            list($table, $column) = explode('.', $ruleArgs[1]);
            $this->databaseInstance->getQueryBuilder()->from($table)
                                                        ->where($column, $inputVal);
            $this->databaseInstance->get();
            if ($this->databaseInstance->numRows() <= 0) {
                if (!$this->_fieldIsRequired($inputName) && strlen($this->data[$inputName]) <= 0) {
                    return;
                }
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName)));
            }
        }

        /**
         * Peform validation for the rule "is_unique"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("is_unique")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateIsUnique($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if (!is_object($this->databaseInstance)) {
                $obj = & get_instance();
                if (isset($obj->database)) {
                    $this->databaseInstance = $obj->database;
                } 
            }
            list($table, $column) = explode('.', $ruleArgs[1]);
            $this->databaseInstance->getQueryBuilder()->from($table)
                                                        ->where($column, $inputVal);
            $this->databaseInstance->get();
            if ($this->databaseInstance->numRows() > 0) {
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName)));
            }
        }
    	
        /**
         * Peform validation for the rule "is_unique_update"
         * @param  string $inputName the form field or data key name used
         * @param  string $ruleName  the rule name for this validation ("is_unique_update")
         * @param  array  $ruleArgs  the rules argument
         */
        protected function _validateIsUniqueUpdate($inputName, $ruleName, array $ruleArgs) {
            $inputVal = $this->post($inputName);
            if (!is_object($this->databaseInstance)) {
                $obj = & get_instance();
                if (isset($obj->database)) {
                    $this->databaseInstance = $obj->database;
                } 
            }
            $data = explode(',', $ruleArgs[1]);
            if (count($data) < 2) {
                return;
            }
            list($table, $column) = explode('.', $data[0]);
            list($field, $val)    = explode('=', $data[1]);
            $this->databaseInstance->getQueryBuilder()->from($table)
                                                        ->where($column, $inputVal)
                                                        ->where($field, '!=', trim($val));
            $this->databaseInstance->get();
            if ($this->databaseInstance->numRows() > 0) {
                $this->_setError($inputName, $ruleName, array($this->_getLabel($inputName)));
            }
        }
        
    }
