<?php
/**
 * sanitizes the string from inputs 
 * 
 * @string the string read from input
 * @string the sanitized string from input
 */
const MONTH = 2592000;
const LAST_TWO_DIGITS = 2;
const LAST_TWO_PLACEMENTS = 2;
const PASSWORD_MIN = 6;
const FOUR_DAYS = 86400;
const DUPLICATE_ENTRY = 1062;
const EMAIL_MIN = 1;
const IF_QUESTION = -1;
const USER_MIN = 3;
const USER_MAX = 15;


/**
 * sanitizes the string from inputs 
 * 
 * @string the string read from input
 * @string the sanitized string from input
 */
function sanitizeString($string){
    $string = stripslashes($string);
	$string = strip_tags($string);
	$string = htmlentities($string);
	return $string;
}

/**
 * sanitizes a string before querying 
 * 
 * @param $conn the connection for database
 * @param $string the string ready for querying
 * @return the string for querying
 */
function stringInputSQL($conn,$string){
    $string = stripslashes($string);
    $string = strip_tags($string);
    return $conn->real_escape_string(htmlentities($string));
}

/**
 * if there is session hijiacking, information destroyed
 */
function different_user() {
    destroy_session_and_data();
    echo "sadge...there's an error, please log in again"; //tells user to log in again 
}

/**
 * destroys sessions when there is hijacking 
 */
function destroy_session_and_data() {
    $_SESSION = array();
    setcookie(session_name(), '', time() - MONTH, '/');
    session_destroy(); 
}

/**
 * checks if there is a hijack 
 */
function hijack_security() {
    $_SESSION["check"] = hash("ripemd128", $_SERVER["REMOTE_ADDR"] . $_SERVER["HTTP_USER_AGENT"]);
    if ($_SESSION["check"] != hash("ripemd128", $_SERVER["REMOTE_ADDR"] . $_SERVER["HTTP_USER_AGENT"])) {
        different_user(); // change user if jijack
    }
}

/**
 * checks if there is fixiation 
 */
function fixation_security() {
    if(!isset($_SESSION['initiated'])){
        session_regenerate_id();
        $_SESSION['initiated'] = 1;
    }
}

/**
 * prints the error message
 * 
 * @param $message the message to print
 */
function echoError($msg) {
    echo "<strong>$msg</strong>";
}

/**
 * add user information into DB to signup
 * @param $conn database connection
 * @param $name user's name
 * @param $username user's username
 * @param $email user's email
 * @param $password user's password
 */
function enterSignup($conn,$name,$username,$email,$password){
    //add information about the student into DB
    $query = stringInputSQL($conn,"INSERT INTO Users (name,username,email,password) VALUES (?,?,?,?)");
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ssss", $name, $username, $email, $password);
        try{
            if(!$stmt->execute()){ //if sql failed
                echoError("SQL Failed");
            }
            echo "Sign up completed";

        } catch (Exception $e) {
            if ($conn->errno == DUPLICATE_ENTRY) { // Error code for duplicate entry
                echo "Username already exists. Please choose a different username.";
            } 
        }
    }else {
        echo "Failed to prepare the statement.";
    }
    $stmt->close();
}

/**
 * checks if the the user's credentials to login are valid
 * 
 * @param $conn database connection
 * @param $username the username
 * @param $passwordLog the password inputted
 */
function validateLogin($conn,$username,$passwordLog){
    //query the username and password
    $getLoginInfo = stringInputSQL($conn, "SELECT id,username,password FROM Users WHERE username = ?");
    if ($stmt = $conn->prepare($getLoginInfo)) {
        $stmt->bind_param("s", $username);
        if(!$stmt->execute()){
            echoError("SQL Failed");
        }
        $stmt->bind_result($retrieved_auto, $username, $retrieved_password);
        //if the username and password exists
        if($stmt->fetch()){
            //if the password is correct
            if(password_verify($passwordLog,$retrieved_password)){
                echo "Login successful.";
                // id is auto incremented and different from student ID
                $_SESSION['id'] = stringInputSQL($conn, $retrieved_auto);
                hijack_security();
                fixation_security();
                ini_set('session.gc_maxlifetime', FOUR_DAYS);
                header("Location: finalHomePage.php"); //change to first page
                exit;
            }else{ // if either the username or password is incorrect
                echo "<strong>Unable to login!</strong><br>";
            }
        }else{// if either the username or password is incorrect
            echo "<strong>Unable to login!</strong><br>";
        }
    }
    $stmt->close();
}


/**
 * checks whether there is a name inputed
 * 
 * @param $field name to validated
 */
function validate_Name($field) {
    return ($field == "") ? "No name entered.<br>" : "";
}

/**
 * checks whether username does only includes uppercase,lowercase,numbers, and _
 * 
 * @param $field username of user to be validated
 */
function validate_username($field){
    if ($field == ''){ // if username is empty
        return "No username entered.<br>";
    }else if(strlen($field) < USER_MIN || strlen($field) > USER_MAX) { // if username is not between 3 and 15 characters
        return "Username must be between 3 and 15 characters.<br>";
    }else if(!preg_match('/^[a-zA-Z0-9_]+$/', $field)){ // username must be only uppercase, lowercase, numbers, and _
        return "Username must contain only letters, numbers, and underscores.<br>";
    }
    return "";
}

/**
 * checks whether email contains a domain and is valid
 * 
 * @param $field email to validated 
 */
function validate_Email($field){
    if ($field == "") { //if email is empty
        return "No Email entered.<br>";
    } else if (!preg_match('/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/', $field)) { //if email contains more than one @
        return "Invalid email format.<br>";
    }
    return "";
}

/**
 * checks if email is more than 6 characters and contains at least one Upper and Lower
 * 
 * @param $field password to validated
 */
function validate_Password($field){
    if($field == ""){ 
        return "No password entered.<br>";
    }else if (strlen($field) <= PASSWORD_MIN){ //if password is not more than 6 characters
        return "Password must be more than 6 characters.<br>";
    }else if(!preg_match('/[a-z]/', $field) || !preg_match('/[A-Z]/', $field)){ //if password does not have at least one upper and lowercase
        return "Password must have at least one Uppercase and one Lowercase.<br>";
    }
    return "";
}

/**
 * adds valid questions to DB
 * 
 * @param $conn the connection
 * @param $username the username 
 * @param $filename name of file 
 */

function check_file($conn,$user_id,$filename){
    $fh = fopen($filename, 'r');
    while(!feof($fh)){
        $line = htmlentities(fgets($fh));
        $line = trim($line);
        if(substr($line, IF_QUESTION) === '?'){//if line ends with a ?, add question
            $query = stringInputSQL($conn,"INSERT INTO files (user_id,questions) VALUES (?,?)");
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("ss", $user_id,$line);
                $stmt->execute();
            }else {
                echo "Failed to prepare the statement.";
            }
        }
    }
    $stmt->close();
    fclose($fh);
}

/**
 * gets all questions that belong to the users
 * 
 * @param $conn the DB connection
 * @param $user_id the user's auto incremented id
 */
function getQuestions($conn, $user_id) {
    $questions = array(); 
    $query = "SELECT questions FROM files WHERE user_id = ?"; //query all questions
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $allQuestions = $stmt->get_result();
        while ($row = $allQuestions->fetch_assoc()) {
            $questions[] = $row['questions']; //add all questions to an array
        }
        $stmt->close();
    } else {
        echo "Failed to prepare the statement.";
    }
    return $questions; //return all questions into an array
}


?>