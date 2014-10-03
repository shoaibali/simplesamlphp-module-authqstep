simplesamlphp-module-authqstep
==============================

SimpleSAMLphp module that challenges users to register answers for pre-defined questions with secret answers. Upon successfull registration and/or subsequent re-authentication attempts user is asked to answer one of their selected question using on-screen keyboard. The question is randomly generated everytime.

Two-step authentication module for simpleSAMLphp using questions and answers.

 Configure it by adding an entry to config/authsources.php such as this:
 
 <pre><code>
       'authqstep' => array(
        'authqstep:authqstep',
           'db.dsn' => 'mysql:host=db.example.com;port=3306;dbname=idpauthqstep',
           'db.username' => 'simplesaml',
           'db.password' => 'password',
           'db.answers_salt' => 'secretsalt',
           'mainAuthSource' => 'ldap',
           'minAnswerLength' => 5,
          'uidField' => 'uid',
           'initSecretQuestions' => array('Question 1', 'Question 2', 'Question 3')
         ),
  </pre></code>

 Once user provides their ldap credentials, if they have pre-existing answers registered in the database
 they are randomly asked one of their chosen question. The answers are provided using on-screen keyboard
 for security reasons and to avoid key logging.

 If the user is new, they go through the process of chosing their questions and their respective answers.
 In order to complete the login process user must provide a correct answer to its question. 

 All answers are normalized to lowercase to accomodate for hashing.

 Please note that in a high availibility scenario the secret salt set in config.php for all instances of
 SimpleSAMLphp need to be the same in order for this to succesfully match user's answer.

 PS: Database schema for answers and questions is automagically generated once the module is enabled.

