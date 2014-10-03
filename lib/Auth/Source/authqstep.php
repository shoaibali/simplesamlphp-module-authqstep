<?php

/**
 * @author Shoaib Ali, Catalyst IT
 *
 * 2 Step authentication module.
 * 
 * Configure it by adding an entry to config/authsources.php such as this:
 *
 *      'authqstep' => array(
 *        'authqstep:authqstep',
 *        'db.dsn' => 'mysql:host=db.example.com;port=3306;dbname=idpauthqstep',
 *        'db.username' => 'simplesaml',
 *        'db.password' => 'password',
 *        'mainAuthSource' => 'ldap',
 *        'uidField' => 'uid',
 *        'post_logout_url' => 'http://google.com' // URL to redirect to on logout. Optional
 *        'minAnswerLength' => 10 // Minimum answer length. Defaults to 0
 *        'initSecretQuestions' => array('Question 1', 'Question 2', 'Question 3') // Optional - Initialise the db with secret questions
 *        ),
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

class sspmod_authqstep_Auth_Source_authqstep extends SimpleSAML_Auth_Source {

  /**
   * The string used to identify our step.
   */
  const STEPID = 'authqstep.step';

  /**
   * Default minimum length of secret answer required. Can be overridden in the config
   */
  const ANSWERMINCHARLENGTH = 0;

  /**
   * The key of the AuthId field in the state.
   */
  const AUTHID = 'sspmod_authqstep_Auth_Source_authqstep.AuthId';

    /**
     *   sstc-saml-loa-authncontext-profile-draft.odt
    */

    const TFAAUTHNCONTEXTCLASSREF = 'urn:oasis:names:tc:SAML:2.0:post:ac:classes:nist-800-63:3';

    private $db_dsn;
    private $db_username;
    private $db_password;
    private $site_salt;
    private $logoutURL;
    private $dbh;
    private $minAnswerLength;


    public $tfa_authencontextclassref;


  /**
   * Constructor for this authentication source.
   *
   * @param array $info  Information about this authentication source.
   * @param array $config  Configuration.
   */
  public function __construct($info, $config) {
    assert('is_array($info)');
    assert('is_array($config)');

    /* Call the parent constructor first, as required by the interface. */
    parent::__construct($info, $config);

    if (array_key_exists('db.dsn', $config)) {
      $this->db_dsn = $config['db.dsn'];
    }
    if (array_key_exists('db.username', $config)) {
      $this->db_username = $config['db.username'];
    }
    if (array_key_exists('db.password', $config)) {
      $this->db_password = $config['db.password'];
    }
    if (array_key_exists('post_logout_url', $config)) {
       $this->logoutURL = $config['post_logout_url'];
    } else {
       $this->logoutURL = '/logout';
    }
    if (array_key_exists('minAnswerLength', $config)) {
       $this->minAnswerLength = $config['minAnswerLength'];
    } else {
       $this->minAnswerLength = self::ANSWERMINCHARLENGTH;
    }

    
    $globalConfig = SimpleSAML_Configuration::getInstance();
    if ($globalConfig->hasValue('secretsalt')) {
        $this->site_salt = $globalConfig->getValue('secretsalt');
    } else {
      /* This is probably redundant, as SimpleSAMLPHP will not let you run without a salt */
      die('Authqstep: secretsalt not set in config.php! You should set this immediately!');
    }

    $this->tfa_authencontextclassref = self::TFAAUTHNCONTEXTCLASSREF;
    try {
      $this->dbh = new PDO($this->db_dsn, $this->db_username, $this->db_password);
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit();
    }

    $this->createTables();

    if(array_key_exists('initSecretQuestions', $config)){
      $this->initQuestions($config['initSecretQuestions']);
    }
               
  }

  public function getLogoutURL() {
    return $this->logoutURL;
  }

  public function getMinAnswerLength() {
    return $this->minAnswerLength;       
  } 
  
  public function authenticate(&$state) {
    assert('is_array($state)');

    /* We are going to need the authId in order to retrieve this authentication source later. */
    $state[self::AUTHID] = $this->authId;

    $id = SimpleSAML_Auth_State::saveState($state, self::STEPID);

    $url = SimpleSAML_Module::getModuleURL('authqstep/login.php');
    SimpleSAML_Utilities::redirect($url, array('AuthState' => $id));
  }

  public function logout(&$state) {
         assert('is_array($state)');
         $state[self::AUTHID] = $this->authId;

         $id = SimpleSAML_Auth_State::saveState($state, self::STEPID);

         $url = SimpleSAML_Module::getModuleURL('authqstep/logout.php');
         SimpleSAML_Utilities::redirect($url, array('AuthState' => $id));
  }

  //Generate a random string of a given length. Used to produce the per-question salt
  private function generateRandomString($length=15) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
    for ($i = 0; $i < $length; $i++) {
              $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
    return $randomString;
  }

  private function createTables()
  {
        /* Create table to hold questions */
        $q = "CREATE TABLE IF NOT EXISTS ssp_questions (
                  question_id INT (11) NOT NULL AUTO_INCREMENT,
                  PRIMARY KEY(question_id),
                  question_text VARCHAR(255) NOT NULL
                 );";

        $result = $this->dbh->query($q);

        /* Create table to hold answers */
    $q = "CREATE TABLE IF NOT EXISTS ssp_answers (
              answer_id INT(11) NOT NULL AUTO_INCREMENT,
              PRIMARY KEY(answer_id),
              answer_hash VARCHAR(128) NOT NULL,
        answer_salt VARCHAR(15) NOT NULL,
                  question_id INT(11) NOT NULL,
                  uid VARCHAR(60) NULL
             );";
    $result = $this->dbh->query($q);     
  }

  private function initQuestions($questions){
    // Not sure if this is the correct way to use assert
    if(assert('is_array($questions)')){
      // make sure table is empty
      if($this->emptyTable("ssp_questions")) {
        foreach($questions as $q){
          $q = "INSERT INTO ssp_questions(question_text) VALUES ('".addslashes($q)."');";
          $this->dbh->query($q);
        }
      }
    }
  }


  private function emptyTable($table){
    $q = "SELECT COUNT(*) as records_count FROM $table";
    $result = $this->dbh->query($q);      
    $row = $result->fetch();
    $records_count =  $row["records_count"];
    return ($records_count == 0)? TRUE : FALSE;   
  }

  /**
   * This method determines if the user's answers are registered 
   *
   * @param int $uid
   * @return bool
   */

  public function isRegistered($uid)
  {
    if (strlen($uid) > 0) {
      $q = "SELECT COUNT(*) as registered_count FROM ssp_answers WHERE uid='$uid'";
      $result = $this->dbh->query($q);      
      $row = $result->fetch();
      $registered =  $row["registered_count"];
        return ($registered >= 3)? TRUE : FALSE;    
    } else {
      return FALSE;
    }
  }

    public function getQuestions(){
      $q = "SELECT * FROM ssp_questions;";
      $result = $this->dbh->query($q);      
      $row = $result->fetchAll();
      
      if(empty($row)){
        return false;
      }
      return $row;
    }

    public function getAnswersFromUID($uid)
    {
      $q = "SELECT * FROM ssp_answers WHERE uid='$uid'";
      $result = $this->dbh->query($q);
      $rows = $result->fetchAll();
      return $rows;
    }

    public function getRandomQuestion($uid){
        $q = "SELECT ssp_answers.question_id, ssp_questions.question_text FROM ssp_answers, ssp_questions WHERE ssp_answers.uid='$uid' AND ssp_answers.question_id = ssp_questions.question_id;";        
        
        $result = $this->dbh->query($q);
        $rows = $result->fetchAll();
        // array_rand is quicker then SQL ORDER BY RAND()
        $random_question = $rows[array_rand($rows)];        
        // TODO this question needs to be made persistent 
        // so that user is challenged for same random question
        return array_unique($random_question);
    }

    private function calculateAnswerHash($answer, $siteSalt, $answerSalt) {
      return hash('sha512', $siteSalt.$answerSalt.strtolower($answer));
    }

    /**
     * Saves user submitted answers in to database
     *
     * @param int $uid
     * @param array $answers
     * @param array $questions
     * @return bool
     */

    public function registerAnswers($uid,$answers, $questions)
    {
      // This check is probably not needed
      if (empty($answers) || empty($questions) || empty($uid)) return FALSE;
        $question_answers = array_combine($answers, $questions);

      // if any of the answers don't meeting requirement then go back
      foreach($question_answers as $answer => $question){
        // Check that the answer meets the length requirements
        if ((strlen($answer) < $this->minAnswerLength) || ($question < 0)) {
          return FALSE;
        }

      }

      $result = TRUE;
      foreach ($question_answers as $answer => $question) {
         $answer_salt = $this->generateRandomString();
         $answer_hash = $this->calculateAnswerHash($answer, $this->site_salt, $answer_salt);
          $q = "INSERT INTO ssp_answers (answer_salt, answer_hash, question_id, uid) 
                VALUES (\"".$answer_salt."\",
                        \"".$answer_hash."\",
                        \"".$question."\",
                        \"".$uid."\");";

          $result = $result && $this->dbh->query($q);
          if($result){
            SimpleSAML_Logger::info('authqstep: ' . $uid . ' registered his answer: for question_id:' . $question);
          } else {
            SimpleSAML_Logger::info('authqstep: ' . $uid . ' FAILED TO INSERT for question_id:' . $question);
          }
      }
      
      return $result;
    }
    


    /**
     * Verify user submitted answer against their question
     *
     * @param int $uid
     * @param int $question_id
     * @param string $answer
     * @return bool
     */

    public function verifyAnswer($uid, $question_id, $answer){
        $answers = self::getAnswersFromUID($uid);
        $match = FALSE;
        
        foreach($answers as $a){
          if ($question_id == $a["question_id"]) {
             $answer_salt = $a['answer_salt'];
             $submitted_hash = $this->calculateAnswerHash($answer, $this->site_salt, $answer_salt);
             if($submitted_hash == $a["answer_hash"]) {
                $match = TRUE;
          break;
             }
          }
        }
        return $match;
    }

}

?>
