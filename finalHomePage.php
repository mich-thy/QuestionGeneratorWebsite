<?php 
require "finalHelper.php";
require "finalLogin.php";
// error_reporting(E_ALL);
// ini_set('display_errors', 1); 
$working = FALSE;
//try establishing a database connection 
ini_set('session.gc_maxlifetime', FOUR_DAYS);
session_start();
hijack_security();
fixation_security();
CONST START = 0;
try{
    $conn = new mysqli($hn, $un, $pw, $db);
}catch(Exception $e){
    // shows a generic message if fails 
    $errorMsg =  "What a sadge day. Come back later :((((((((((((( sadge";
    echoError($errorMsg);
    $working = TRUE;
}
//if user is not logged in, direct to sign up/ login page
if(!isset($_SESSION['id'])){
    destroy_session_and_data();
    hijack_security();
    fixation_security();
    header("Location: finalMain.php");
    exit;
}
if(!$working){
    //html to insert thread and file
    echo <<< _END
    <form method='post' action='finalHomePage.php' enctype='multipart/form-data'><pre>
    <strong style='font-size: 20px;'>Upload File with Questions:</strong>
    Upload File: <input type='file' name='filename' size='10'>

    <input type='submit' name='upload' value='Upload file'>
    <input type='submit' name='randomize' value='Randomize Questions'>
    <input type='submit' name='logout' value='Logout'>
    </pre></form>
    _END;


    //if user wants to upload a file 
    if(isset($_POST["upload"]) && $_FILES){
        $tmp_name = htmlentities($_FILES['filename']['tmp_name']);
        switch($_FILES['filename']['type']) {
            case 'text/plain':  // right here is where only accept text file!
                $ext = 'txt'; 
                break;
            default: 
                $ext = ''; 
                break; 
        }
        if (!$ext) {
            echo "Filetype not accepted. Upload a text file.";  
        }else{
            echo "Uploaded!!!!<br>";
            check_file($conn,$_SESSION['id'],$tmp_name); //check if file are all questions
            hijack_security();
            fixation_security();
        }
    }

    //if users want tp randomize all the questions
    if(isset($_POST['randomize'])){
        $questions = getQuestions($conn, $_SESSION['id']);
        $numQuestions = count($questions); //get count of questions
        if($numQuestions != START){
            $randomIndex = rand(START, $numQuestions - 1); //-1 is for index
            echo $questions[$randomIndex]."<br>"; //choose a random question
        }
    }
    //if the user logs out 
    if(isset($_POST["logout"])){
        //close sessions and clear information 
        session_unset(); 
        session_destroy(); 
        ini_set('session.gc_maxlifetime', -FOUR_DAYS);
        hijack_security();
        fixation_security();
        header("Location: finalMain.php");
        exit;
    }
    $conn->close();
}
?>