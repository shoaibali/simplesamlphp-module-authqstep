<?php

/**
 * @author Shoaib Ali, Catalyst IT
 * @package simpleSAMLphp
 * @version $Id$
 */

$as = SimpleSAML_Configuration::getConfig('authsources.php')->getValue('authqstep');

// Get session object
$session = SimpleSAML_Session::getInstance();

// Get the authetication state
$authStateId = $_REQUEST['AuthState'];
$state = SimpleSAML_Auth_State::loadState($authStateId,'authqstep.step');

// Use 2 step authentication class
$qaLogin = SimpleSAML_Auth_Source::getById('authqstep');

// Init template
$template = 'authqstep:login.php';
$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, $template);

$errorCode = NULL;

$questions = $qaLogin->getQuestions();

if(!$questions){
	$errorCode = 'EMPTYQUESTIONS';
	$t->data['todo'] = 'loginANSWER';		
} 

$t->data['questions'] = $questions;

//If user doesn't have session, force to use the main authentication method
if (!$session->isValid( $as['mainAuthSource'] )) {
	SimpleSAML_Auth_Default::initLogin( $as['mainAuthSource'], SimpleSAML_Utilities::selfURL());
} 

$attributes = $session->getAttributes();
$state['Attributes'] = $attributes;


$uid = $attributes[ $as['uidField'] ][0];
$state['UserID'] = $uid;
$isRegistered = $qaLogin->isRegistered($uid);

if ( !$isRegistered ) {
	//If the user has not set his preference of 2 factor authentication, redirect to settings page
	if ( isset($_POST['answers']) && isset($_POST['questions']) ){
		// Save answers	
		$answers = $_POST["answers"];
		$questions = $_POST["questions"];

		// verify answers are not duplicates
		if( (sizeof(array_unique($answers)) != sizeof($answers)) || (sizeof(array_unique($questions)) != sizeof($questions)) ){
			$errorCode = 'INVALIDQUESTIONANSWERS';
			$t->data['todo'] = 'selectanswers';
		} elseif (in_array(0, $questions) || sizeof($answers) < 3) {
		  $errorCode = 'INCOMPLETEQUESTIONS';
			$t->data['todo'] = 'selectanswers';
		} else {
			$result = $qaLogin->registerAnswers($uid, $answers, $questions);		
			if ( ! $result) {
			  // Failed to register answers for some reason. This is probably because one or more answer is too short
			  $errorCode = 'SHORTQUESTIONANSWERS';
			  $t->data['todo'] = 'selectanswers';
			} else {
			  // redirect user back to be quized
			  SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::selfURL(), array('AuthState' => $authStateId,
			  																																			'Quesetions' => $_POST['questions'],
			  																																			'Answers' => $_POST['answers']));
			}
		}

	} else {
		$t->data['todo'] = 'selectanswers';	
	}	
} 

if ( $isRegistered ){
	// get a random question
	$random_question = $qaLogin->getRandomQuestion($uid);
	$t->data['random_question'] = array("question_text" => $random_question["question_text"],
								 "question_id" => $random_question["question_id"]);
	//Ask the user for answer to a randomly selected question
	if ( isset( $_POST['answer'] ) ){
		 
		$loggedIn = $qaLogin->verifyAnswer($uid, $_POST['question_id'], $_POST['answer']);		
		if ($loggedIn){
			$state['saml:AuthnContextClassRef'] = $qaLogin->tfa_authencontextclassref;
			SimpleSAML_Auth_Source::completeAuth($state);	
		} else {
			$errorCode = 'WRONGANSWER';
			$t->data['todo'] = 'loginANSWER';		
		}

	} else {
		$t->data['autofocus'] = 'answer';
		$t->data['todo'] = 'loginANSWER';	
		
	}
}

$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['errorcode'] = $errorCode;
$t->data['minAnswerLength'] = $qaLogin->getMinAnswerLength();
$t->show();
//exit();

?>
