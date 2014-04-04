<?php

// xPay service should redirect here on success/fail.

require dirname(dirname(dirname(__FILE__))) . "/config.php";
require_once "{$CFG->dirroot}/lib/enrollib.php";

require_login();

// fetch the response XML from xPay
//$result = required_param('result', PARAM_CLEAN);
$xpayenrol = enrol_get_plugin('xpay');
if (!isset($_GET["esito"]) || $_GET["esito"] != 'OK')
	$xpayenrol->abort_transaction($_GET);
else {
	$coursename = $xpayenrol->confirm_transaction($_GET);
	$coursename = trim($coursename);
	$alias = $_GET["alias"];
	$importo = $_GET["importo"];
	if (strlen($importo) == 1) $importo = "0,0".$importo;
	else if (strlen($importo) == 2) $importo = "0,".$importo;
	else $importo = substr($importo,0,strlen($importo) - 2).",".substr($importo,strlen($importo) - 2, 2);
	$divisa = $_GET["divisa"];
	$codTrans = $_GET["codTrans"];
	$course_user = preg_split("/-/",$codTrans);
	//echo $course_user[1];
	$esito = $_GET["esito"];
	$codAut = $_GET["codAut"];
	$alias = $_GET["alias"];
	$session_id = $_GET["session_id"];
	$data = $_GET["data"];
	$data = substr($data,6, 2)."/".substr($data,4, 2)."/".substr($data,0, 4);
	$orario = $_GET["orario"];
	$orario = substr($orario,0, 2).":".substr($orario,2, 2).":".substr($orario,4, 2);
	$brand = $_GET["brand"];
	$nome = $_GET["nome"];
	$cognome = $_GET["cognome"];
	$email = $_GET["email"];
	$mac = $_GET["mac"];
	$nazionalita = $_GET["nazionalita"];
	$pan = $_GET["pan"];
	$scadenza_pan = $_GET["scadenza_pan"];

?>

<div align="center">
	<div>Ricevuta di pagamento online</div>
	<div><img alt="<?php print_string('pluginname', 'enrol_xpay') ?>" src="<?php echo $CFG->wwwroot.'/enrol/xpay/xpaycartasi.png'; ?>" /></div>
	<div>per l'iscrizione al corso:</div>
	<h3><strong>&quot;<?php echo "{$coursename}"; ?>&quot;</strong></h3>
	<table>
		<tr>
			<td>Importo:</td>
			<td><strong><?php echo "{$importo}"; ?> <?php echo "{$divisa}"; ?></strong></td>
		</tr>
		<tr>
			<td>ID Transazione:</td>
			<td><strong><?php echo "{$codTrans}"; ?></strong></td>
		</tr>
		<tr>
			<td>Esito Transazione:</td>
			<td><strong><?php echo "{$esito}"; ?></strong></td>
		</tr>
		<tr>
			<td>Codice Autorizzazione:</td>
			<td><strong><?php echo "{$codAut}"; ?></strong></td>
		</tr>
		<tr>
			<td>ID Sessione:</td>
			<td><strong><?php echo "{$session_id}"; ?></strong></td>
		</tr>
		<tr>
			<td>Codice Sicurezza (MAC):&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td><strong><?php echo "{$mac}"; ?></strong></td>
		</tr>
		<tr>
			<td>Intestatario Conto:</td>
			<td><strong><?php echo "{$alias}"; ?></strong></td>
		</tr>
		<tr>
			<td>Data:</td>
			<td><strong><?php echo "{$data}"; ?></strong></td>
		</tr>
		<tr>
			<td>Ora:</td>
			<td><strong><?php echo "{$orario}"; ?></strong></td>
		</tr>
		<tr>
			<td>Carta di credito:</td>
			<td><strong><?php echo "{$brand}"; ?></strong></td>
		</tr>
		<tr>
			<td>Intestatario Carta:</td>
			<td><strong><?php echo "{$nome}"; ?> <?php echo "{$cognome}"; ?></strong></td>
		</tr>
		<tr>
			<td>Email Intestatario Carta:</td>
			<td><strong><?php echo "{$email}"; ?></strong></td>
		</tr>
	</table>
	<!--p><?php echo "{$CFG->wwwroot}/course/view.php?id={$course_user[1]}"; ?></p-->
	<p><a href='<?php echo "{$CFG->wwwroot}/course/view.php?id={$course_user[1]}"; ?>' title="Accedi al corso">Accedi al corso</a></p>
</div>

<?php
//echo '<br />';
//echo $CFG->wwwroot.'/course/view.php?id='.$xpaytx->courseid;*/

}