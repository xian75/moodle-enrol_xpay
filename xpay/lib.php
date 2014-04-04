<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Enrolment using the xPay credit card payment gateway.
 *
 * This plugin handles credit card payments by conducting PxPay transactions
 * through the xPay Payment Express gateway. A successful payment results in the
 * enrolment of the user. We use PxPay because it does not require handling the
 * credit card details in Moodle. A truncated form of the credit card number is
 * returned in the PxPay response and is stored for reference only.
 *
 * Details of the xPay API are online:
 * http://www.paymentexpress.com/technical_resources/ecommerce_hosted/pxpay.html
 *
 * @package    enrol
 * @subpackage xpay
 * @copyright  2013 Cristian Natale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die();


/**
* xPay enrolment plugin for IT xPay Payment Express.
* Developed by Catalyst IT Limited for The Open Polytechnic of New Zealand.
* Uses the xPay method (redirect and return).
*/
class enrol_xpay_plugin extends enrol_plugin {

    /**
     * Constructor.
     * Fetches configuration from the database and sets up language strings.
     */
    function __construct() {

        // set up the configuration
        $this->load_config();
        $this->recognised_currencies = array(
            'AUD',
            'CAD',
            'CHF',
            'EUR',
            'FJD',
            'FRF',
            'GBP',
            'HKD',
            'JPY',
            'KWD',
            'MYR',
            'NZD',
            'PNG',
            'SBD',
            'SGD',
            'TOP',
            'USD',
            'VUV',
            'WST',
            'ZAR',
        );
        //$this->xpay_url = 'https://sec.paymentexpress.com/pxpay/pxaccess.aspx';
        $this->xpay_url = 'https://ecommerce.keyclient.it/ecomm/ecomm/DispatcherServlet';
    }

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        return array(new pix_icon('icon', get_string('pluginname', 'enrol_xpay'), 'enrol_xpay'));
    }

    public function roles_protected() {
        // users with role assign cap may tweak the roles later
        return false;
    }

    public function allow_unenrol(stdClass $instance) {
        // users with unenrol cap may unenrol other users manually - requires enrol/xpay:unenrol
        return true;
    }

    public function allow_manage(stdClass $instance) {
        // users with manage cap may tweak period and status - requires enrol/xpay:manage
        return true;
    }

    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Sets up navigation entries.
     *
     * @param object $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'xpay') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = get_context_instance(CONTEXT_COURSE, $instance->courseid);
        if (has_capability('enrol/xpay:config', $context)) {
            $managelink = new moodle_url('/enrol/xpay/edit.php', array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'xpay') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = get_context_instance(CONTEXT_COURSE, $instance->courseid);

        $icons = array();

        if (has_capability('enrol/xpay:config', $context)) {
            $editlink = new moodle_url("/enrol/xpay/edit.php", array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('i/edit', get_string('edit'), 'core', array('class'=>'icon')));
        }

        return $icons;
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = get_context_instance(CONTEXT_COURSE, $courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/xpay:config', $context)) {
            return NULL;
        }

        // multiple instances supported - different cost for different roles
        return new moodle_url('/enrol/xpay/edit.php', array('courseid'=>$courseid));
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    function enrol_page_hook(stdClass $instance) {
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;

        ob_start();

        if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
            return ob_get_clean();
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return ob_get_clean();
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return ob_get_clean();
        }

        $course = $DB->get_record('course', array('id'=>$instance->courseid));
        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        $shortname = format_string($course->shortname, true, array('context' => $context));
        $strloginto = get_string("loginto", "", $shortname);
        $strcourses = get_string("courses");

        // Pass $view=true to filter hidden caps if the user cannot see them
        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                             '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }

        if ( (float) $instance->cost <= 0 ) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if (abs($cost) < 0.01) { // no cost, other enrolment methods (instances) should be used
            echo '<p>'.get_string('nocost', 'enrol_xpay').'</p>';
        } else {

            if (isguestuser()) { // force login only for guest user, not real users with guest role
                if (empty($CFG->loginhttps)) {
                    $wwwroot = $CFG->wwwroot;
                } else {
                    // This actually is not so secure ;-), 'cause we're
                    // in unencrypted connection...
                    $wwwroot = str_replace("http://", "https://", $CFG->wwwroot);
                }
                echo '<div class="mdl-align"><p>'.get_string('paymentrequired').'</p>';
                echo '<p><b>'.get_string('cost').": $instance->currency $cost".'</b></p>';
                echo '<p><a href="'.$wwwroot.'/login/">'.get_string('loginsite').'</a></p>';
                echo '</div>';
            } else {
                //Sanitise some fields before building the xpay form
                $coursefullname  = format_string($course->fullname, true, array('context'=>$context));
                $courseshortname = $shortname;
                $userfullname    = fullname($USER);
                $userfirstname   = $USER->firstname;
                $userlastname    = $USER->lastname;
                $useraddress     = $USER->address;
                $usercity        = $USER->city;
                $instancename    = $this->get_instance_name($instance);

                include($CFG->dirroot.'/enrol/xpay/enrol.html');
            }
        }

        return $OUTPUT->box(ob_get_clean());
    }

    /**
     * Start the xPay transaction by storing a record in the transactions table
     * and returning the GenerateRequest XML message.
     *
     * @param object $instance The course to be enroled.
     * @param object $user
     * @return string
     * @access public
     */
    function begin_transaction($instance, $user) {
        global $CFG, $DB;

        if (!$course = $DB->get_record('course', array('id' => $instance->courseid))) {
            print_error('coursenotfound', 'enrol_xpay');
        }
        if (empty($course) or empty($user)) {
            print_error('error_usercourseempty', 'enrol_xpay');
        }

        if (!in_array($instance->currency, $this->recognised_currencies)) {
            print_error('error_xpaycurrency', 'enrol_xpay');
        }

        // log the transaction
        $fullname = fullname($user);
        $xpaytx->courseid = $course->id;
        $xpaytx->userid = $user->id;
        $xpaytx->instanceid = $instance->id;
        $xpaytx->cost = str_replace(",","",clean_param(format_float((float)$instance->cost, 2), PARAM_CLEAN));
        $xpaytx->currency = clean_param($instance->currency, PARAM_CLEAN);
        $xpaytx->date_created = time();
        $site = get_site();
        $sitepart   = substr($site->shortname, 0, 20);
        $coursepart = substr("{$course->id}:{$course->shortname}", 0, 20);
        $userpart   = substr("{$user->id}:{$user->lastname} {$user->firstname}", 0, 20);
        $xpaytx->merchantreference = clean_param(strtoupper("$sitepart:{$coursepart}:{$userpart}"), PARAM_CLEAN);
        $xpaytx->email = clean_param($user->email, PARAM_CLEAN);
        $xpaytx->txndata1 = clean_param("{$xpaytx->courseid}: {$course->fullname}", PARAM_CLEAN);
        $xpaytx->txndata2 = clean_param("{$xpaytx->userid}: {$fullname}", PARAM_CLEAN);

		$codTrans = time().'-'.$xpaytx->courseid.'-'.$xpaytx->userid;
		
        $xpaytx->txndata3 = $codTrans;

        if (!$xpaytx->id = $DB->insert_record('enrol_xpay_transactions', $xpaytx)) {
            print_error('error_txdatabase', 'enrol_xpay');
        }

        // create the "Generate Request" XML message
        /*$xmlrequest = "<GenerateRequest>
            <PxPayUserId>{$this->config->userid}</PxPayUserId>
            <PxPayKey>{$this->config->key}</PxPayKey>
            <AmountInput>{$xpaytx->cost}</AmountInput>
            <CurrencyInput>{$xpaytx->currency}</CurrencyInput>
            <MerchantReference>{$xpaytx->merchantreference}</MerchantReference>
            <EmailAddress>{$xpaytx->email}</EmailAddress>
            <TxnData1>{$xpaytx->txndata1}</TxnData1>
            <TxnData2>{$xpaytx->txndata2}</TxnData2>
            <TxnData3>{$xpaytx->txndata3}</TxnData3>
            <TxnType>Purchase</TxnType>
            <TxnId>{$xpaytx->id}</TxnId>
            <BillingId></BillingId>
            <EnableAddBillCard>0</EnableAddBillCard>
            <UrlSuccess>{$CFG->wwwroot}/enrol/xpay/confirm.php</UrlSuccess>
            <UrlFail>{$CFG->wwwroot}/enrol/xpay/fail.php</UrlFail>
            <Opt></Opt>\n</GenerateRequest>";

        //return $this->queryxpay($xmlrequest);*/
		
		//$codTrans = str_replace("0.","",microtime()).' '.$xpaytx->userid;
		$string2mac = 'codTrans='.$codTrans.'divisa='.$xpaytx->currency.'importo='.$xpaytx->cost.$this->config->key;
		$mac = sha1($string2mac);		
		
		$urlfields='alias='.$this->config->userid.'&importo='.$xpaytx->cost.'&divisa='.$xpaytx->currency.'&codTrans='.$codTrans.'&mail='.$xpaytx->email.'&url='.$CFG->wwwroot.'/enrol/xpay/check.php&session_id='.$SESSION->id.'&url_back='.$CFG->wwwroot.'/enrol/index.php?id='.$xpaytx->courseid.'&mac='.$mac.'&languageId=ITA';

		//return $urlfields;
		return $this->queryxpay($urlfields);
    }

    /**
     * Start the xPay transaction by storing a record in the transactions table
     * and returning the GenerateRequest XML message.
     *
     * @param object $course The course to be enroled.
     * @param object $result
     * @return string
     * @access public
     */
    function confirm_transaction($responseParam) {
        global $USER, $SESSION, $CFG, $DB;

        /*$xmlrequest = "<ProcessResponse>
            <PxPayUserId>{$this->config->userid}</PxPayUserId>
            <PxPayKey>{$this->config->key}</PxPayKey>
            <Response>{$result}</Response>\n</ProcessResponse>";
        $xmlreply = $this->queryxpay($xmlrequest);
        $response = $this->getdom($xmlreply);

        // abort if invalid
        if ($response === false or $response->attributes()->valid != '1') {
            print_error('error_txinvalid', 'enrol_xpay');
        }*/

		$string2mac = 'codTrans='.$responseParam["codTrans"].'esito='.$responseParam["esito"].'importo='.$responseParam["importo"].'divisa='.$responseParam["divisa"].'data='.$responseParam["data"].'orario='.$responseParam["orario"].'codAut='.$responseParam["codAut"].$this->config->key;
		$calculatedMac = sha1($string2mac);
		if ($calculatedMac != $responseParam["mac"]) {
/**/            print_error('error_txinvalid', 'enrol_xpay');
		}

        if (!$xpaytx = $DB->get_record('enrol_xpay_transactions', array('txndata3' => $responseParam["codTrans"]))) {
            print_error('error_txnotfound', 'enrol_xpay');
        }

        // abort if already processed
        if (!empty($xpaytx->txnmac)) {
/**/            print_error('error_txalreadyprocessed', 'enrol_xpay');
        }

        $xpaytx->success    = clean_param("1", PARAM_CLEAN);
        $xpaytx->authcode   = clean_param($responseParam["codAut"], PARAM_CLEAN);
        $xpaytx->cardtype   = clean_param($responseParam["brand"], PARAM_CLEAN);
        $xpaytx->cardholder = clean_param($responseParam["nome"]." ".$responseParam["cognome"], PARAM_CLEAN);
        if (isset($responseParam["pan"]))$xpaytx->cardnumber = clean_param($responseParam["pan"], PARAM_CLEAN);
        if (isset($responseParam["scadenza_pan"]))$xpaytx->cardexpiry = clean_param($responseParam["scadenza_pan"], PARAM_CLEAN);
        if (isset($responseParam["nazionalita"])) $xpaytx->clientinfo = clean_param($responseParam["nazionalita"], PARAM_CLEAN);
        $xpaytx->xpaytxnref  = clean_param($responseParam["session_id"], PARAM_CLEAN);
        $xpaytx->txnmac     = clean_param($responseParam["mac"], PARAM_CLEAN);
        $xpaytx->response   = clean_param($responseParam["esito"], PARAM_CLEAN);
		
        // update transaction
/**/        if (!$DB->update_record('enrol_xpay_transactions', $xpaytx)) {
/**/            print_error('error_txnotfound', 'enrol_xpay');
/**/        }

        // recover the course
        list($courseid, $coursename) = explode(":", $xpaytx->txndata1);
        $course = $DB->get_record('course', array('id' => $courseid));

        // enrol and continue if xPay returns "OK"
/**/        if ($xpaytx->success == 1 and $xpaytx->response == "OK") {

            // enrol the student and continue
            // TODO: ASSUMES the currently logged in user. Does not check the user in $xpaytx, but they should be the same!
            if (!$plugin_instance = $DB->get_record("enrol", array("id"=>$xpaytx->instanceid, "status"=>0))) {
                print_error('Not a valid instance id');
            }
            if ($plugin_instance->enrolperiod) {
                $timestart = time();
                $timeend   = $timestart + $plugin_instance->enrolperiod;
            } else {
                $timestart = 0;
                $timeend   = 0;
            }
            // Enrol the user!
            $this->enrol_user($plugin_instance, $xpaytx->userid, $plugin_instance->roleid, $timestart, $timeend);

            // force a refresh of mycourses
            unset($USER->mycourses);

            // redirect to course view
            if ($SESSION->wantsurl) {
                $destination = $SESSION->wantsurl;
                unset($SESSION->wantsurl);
            } else {
                $destination = "{$CFG->wwwroot}/course/view.php?id={$course->id}";
            }
            //redirect($destination);
        } else {
            // abort
            print_error('error_paymentunsucessful', 'enrol_xpay');
        }/**/
		return $coursename;
    }

    /**
     * Roll back the xPay transaction by updating the record in the transactions
     * table.
     *
     * @param object $course The course to be enroled.
     * @param object $result
     * @return string
     * @access public
     */
    function abort_transaction($responseParam) {
        global $USER, $SESSION, $CFG, $DB;

        /*$xmlrequest = "<ProcessResponse>
            <PxPayUserId>{$this->config->userid}</PxPayUserId>
            <PxPayKey>{$this->config->key}</PxPayKey>
            <Response>{$result}</Response>\n</ProcessResponse>";
        $xmlreply = $this->queryxpay($xmlrequest);
        $response = $this->getdom($xmlreply);*/

        // abort if invalid
        /*if ($response === false or $response->attributes()->valid != '1') {
            print_error('error_txinvalid', 'enrol_xpay');
        }*/
		
		$string2mac = 'codTrans='.$responseParam["codTrans"].'esito='.$responseParam["esito"].'importo='.$responseParam["importo"].'divisa='.$responseParam["divisa"].'data='.$responseParam["data"].'orario='.$responseParam["orario"].'codAut='.$responseParam["codAut"].$this->config->key;
		$calculatedMac = sha1($string2mac);
		if ($calculatedMac != $responseParam["mac"]) {
            print_error('error_txinvalid', 'enrol_xpay');
		}
		
        if (!$xpaytx = $DB->get_record('enrol_xpay_transactions', array('txndata3' => $responseParam["codTrans"]))) {
            print_error('error_txnotfound', 'enrol_xpay');
        }

        // abort if already processed
        if (!empty($xpaytx->txnmac)) {
            print_error('error_txalreadyprocessed', 'enrol_xpay');
        }

        $xpaytx->success    = clean_param("-1", PARAM_CLEAN);
        $xpaytx->authcode   = clean_param($responseParam["codAut"], PARAM_CLEAN);
        $xpaytx->cardtype   = clean_param($responseParam["brand"], PARAM_CLEAN);
        $xpaytx->cardholder = clean_param($responseParam["nome"]." ".$responseParam["cognome"], PARAM_CLEAN);
        if (isset($responseParam["pan"]))$xpaytx->cardnumber = clean_param($responseParam["pan"], PARAM_CLEAN);
        if (isset($responseParam["scadenza_pan"]))$xpaytx->cardexpiry = clean_param($responseParam["scadenza_pan"], PARAM_CLEAN);
        if (isset($responseParam["nazionalita"])) $xpaytx->clientinfo = clean_param($responseParam["nazionalita"], PARAM_CLEAN);
        $xpaytx->xpaytxnref  = clean_param($responseParam["session_id"], PARAM_CLEAN);
        $xpaytx->txnmac     = clean_param($responseParam["mac"], PARAM_CLEAN);
        $xpaytx->response   = clean_param($responseParam["esito"], PARAM_CLEAN);

        // update transaction
        if (!$DB->update_record('enrol_xpay_transactions', $xpaytx)) {
            print_error('error_txnotfound', 'enrol_xpay');
        }

        print_error('error_paymentfailure', 'enrol_xpay', '', $xpaytx->response);
		
    }

    /**
    * Cron method.
    * @return void
    */
    function cron() {
    }

    /**
     * Turn an XML string into a DOM object.
     *
     * @param string $xml An XML string
     * @return object The SimpleXMLElement object representing the root element.
     * @access public
     */
    function getdom($xml) {
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);
        return simplexml_import_dom($dom);
    }

    /**
     * Send an XML message to the xPay service and return the XML response.
     *
     * @param string $xml The XML request to send.
     * @return string The XML response from xPay.
     * @access public
     */
	function queryxpay($xml){
        if (!extension_loaded('curl') or ($curl = curl_init($this->xpay_url)) === false) {
            print_error('curlrequired', 'enrol_xpay');
        }

		curl_setopt($curl, CURLOPT_URL, $this->xpay_url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

		// TODO: fix up curl proxy stuffs, c.f. lib/filelib.php
		//curl_setopt($ch,CURLOPT_PROXY , "{$CFG->proxyhost}:{$CFG->proxyport}");
		//curl_setopt($ch,CURLOPT_PROXYUSERPWD,"{$CFG->proxyuser}:{$CFG->proxypassword}");

		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}
}

