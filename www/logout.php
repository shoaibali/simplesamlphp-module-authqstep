<?php
/**
 * @author Shoaib Ali, Catalyst IT
 * @package simpleSAMLphp
 * @version $Id$
 */

$as = SimpleSAML_Configuration::getConfig('authsources.php')->getValue('authqstep');

// Get session object
$session = SimpleSAML_Session::getInstance();

// Get the auth source so we can retrieve the URL we are ment to redirect to
$qaLogin = SimpleSAML_Auth_Source::getById('authqstep');

// Trigger logout for the main auth source
if ($session->isValid( $as['mainAuthSource'] )) {
   SimpleSAML_Auth_Default::initLogout( $qaLogin->getLogoutURL(), $as['mainAuthSource'] );
} 

?>