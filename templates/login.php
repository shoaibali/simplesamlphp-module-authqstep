<?php

$this->includeAtTemplateBase('includes/header.php');
$this->data['header'] = $this->t('{authqstep:login:authentication}');

?>

<link rel="stylesheet" href="keyboard.css" />
<script src="jquery.min.js"></script>
<script src="jquery-ui.min.js"></script>
<script type="text/javascript" src="jquery.keyboard.min.js"></script>
<script type="text/javascript">
	$(document).ready(function () {
	  $("input[type='text']").keyboard({
	    autoAccept: true,
	    layout: 'custom',
	    lockInput: true,
	    customLayout: {
	      'default': ['0 1 2 3 4 5 6 7 8 9', 'a b c d e f g h i j k l m', 'n o p q r s t u v w x y z','{accept} {space} {cancel}']
	    }
	  });
	  $("input[type='text']").getkeyboard().reveal();
	});
</script>


<?php if ($this->data['errorcode'] !== NULL) :?>
	<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		<p><b><?php echo $this->t('{authqstep:errors:title_' . $this->data['errorcode'] . '}'); ?></b></p>
		<p><?php echo $this->t('{authqstep:errors:descr_' . $this->data['errorcode'] . '}'); ?></p>
	</div>
<?php endif; ?>


<form action="?" method="post" name="f" id="form">
<?php if ( $this->data['todo'] == 'selectanswers' ) : ?>
	<h2><?php echo $this->t('{authqstep:login:2step_title}')?></h2>
	<div class="loginbox">
		<p class="logintitle"><?php echo $this->t('{authqstep:login:chooseANSWERS}')?></p>
        <p>
        	<?php
        		
        		if(!empty($this->data['questions'])) {
        			for($i=1;$i <=3; $i++){

                $answer_value = "";
                
                if(isset($_POST["answers"]) && isset($_POST["questions"])){
                  $answer_value = $_POST["answers"][$i-1];
                  $selected_qid = $_POST["questions"][$i-1];
                }

        				echo '<select name="questions[]" required="requred">';
        				echo '<option value="0">--- select question ---</option>';
        				foreach($this->data['questions'] as $question => $q) {
        				
                  if(isset($_POST["questions"])){
                    if($selected_qid == $q["question_id"]){
                      $selected = " selected";
                    }
                  }
                  echo ('<option value="'.$q['question_id'].'"'. $selected . '>'.$q['question_text'].'</option>');
                  
        				}
        				echo '</select>';
        				echo 'Answer: <input name="answers[]" value="" type="text" pattern=".{'.$this->data['minAnswerLength'].',}"';
					      echo 'title="Answers must be at least '.$this->data['minAnswerLength'].' characters long" required="requred">';
        				echo '<br/><br/>';
        			}
        		}
        	?>
		<?php if ( $this->data['minAnswerLength'] > 0 ) : ?>
		<p>Answers must be at least <?php echo $this->data['minAnswerLength'] ?> characters long</p>
		<?php endif; ?>
        	<input class="submitbutton" type="submit" tabindex="2" name="submit" value="<?php echo $this->t('{authqstep:login:next}')?>" />
        </p>
	</div>

<?php elseif ( $this->data['todo'] == 'loginANSWER' ) : ?>
	<h2><?php echo $this->t('{authqstep:login:2step_login}')?></h2>
	<div class="loginbox">
		<p class="logintitle">
			<?php echo $this->t('{authqstep:login:verficiationanswer}')?>
			<br/> 
			<strong><?php echo $this->data["random_question"]["question_text"]; ?>?</strong>
			<br/>
			<input type="hidden" value="<?php echo $this->data['random_question']['question_id'];?>" name="question_id">
			<input id="answer" class="yubifield" type="text" tabindex="1" name="answer" />
			<input id="submit" class="submitbutton" type="submit" tabindex="2" name="submit" value="<?php echo $this->t('{authqstep:login:next}')?>"/>
		</p>
	</div>

<?php endif ; ?>

<?php
foreach ($this->data['stateparams'] as $name => $value) {
	echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
?>

</form>

<div id="keyboard"></div>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>
