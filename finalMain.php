<?php 
require_once 'finalLogin.php';
require_once 'finalHelper.php';
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

//Preventing session fixation
session_start();
hijack_security();
fixation_security();
$working = FALSE;

//try establishing a database connection 
try{
    $conn = new mysqli($hn, $un, $pw, $db);
}catch(Exception $e){
    // shows a generic message if fails 
    $errorMsg =  "What a sadge day. Come back later :((((((((((((( sadge";
    echoError($errorMsg);
    $working = TRUE;
}
if(!$working){
    //create user sign in input
    //uses javascript validation to ensure inputs are correct
    echo <<< _END
    <form method='post' action='finalMain.php' enctype='multipart/form-data' onSubmit="return validate(this)"><pre>
        <strong style='font-size: 20px;'>Signup</strong>
        Name:      <input type="text" name="name">   
        Username:  <input type='text' name='username'>
        Email:     <input type='text' name='email'>
        Password:  <input type="password" name="password">
        <input type='submit' name='signup' value='Signup'>
    </pre></form>
    <form method='post' action='finalMain.php' enctype='multipart/form-data'><pre>
        <strong style='font-size: 20px;'>Login</strong>
        Username:  <input type='text' name='usernameLog'>
        Password:  <input type="password" name="passwordLog">
        <input type='submit' name='login' value='Login'>
    </pre></form>
    <script>
    function validate(form){
        var fail = "";
        fail += validateName(form.name.value);
        fail += validateUsername(form.username.value);
        fail += validateEmail(form.email.value);
        fail += validatePassword(form.password.value);
        if(fail == ""){
            return true;
        }else{
            alert(fail);
            return false;
        }
    }
    function validateName(field){
        return (field == "") ? "No name entered.\\n" : "";
    }
    function validateUsername(field) {
        var USER_MIN = 3;
        var USER_MAX = 15;
    
        if (field === '') { // if username is empty
            return "No username entered.<br>";
        } else if (field.length < USER_MIN || field.length > USER_MAX) { // if username length is not between USER_MIN and USER_MAX characters
            return "Username must be between 3 and 15 characters.\\n";
        } else if (!/^[a-zA-Z0-9_]+$/.test(field)) { // if username contains anything other than allowed characters
            return "Username must contain only letters, numbers, and underscores.\\n";
        }
        return "";
    }
    function validateEmail(field){
        if (field == "") {
            return "No Email entered.\\n";
        } else if (!/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/.test(field)) {
            return "Invalid email format.\\n";
        }
        return "";
    }
    function validatePassword(field){
        var passwordLength = 6;
        if(field == ""){
            return "No password entered.\\n";
        }else if (field.length <= passwordLength){
            return "Password must be more than 6 characters.\\n";
        }else if(!/[a-z]/.test(field) || !/[A-Z]/.test(field)){
            return "Password must have at least one Uppercase and one Lowercase.\\n";
        }
        return "";
    }
    </script>
    _END;
    // if users press to signup
    if(isset($_POST["signup"])){
        //initialize student info
        $name = $username = $email = $password = "";
        if(isset($_POST["name"])){
            $name = sanitizeString($_POST["name"]);
        }
        if(isset($_POST["username"])){
            $username = sanitizeString($_POST["username"]);
        }
        if(isset($_POST["email"])){
            $email = sanitizeString($_POST["email"]);
        }
        if(isset($_POST["password"])){
            $password = sanitizeString($_POST["password"]);
        }
        //ensures every test case passes 
        $fail = validate_Name($name);
        $fail .= validate_username($username);
        $fail .= validate_Email($email);
        $fail .= validate_Password($password);
        if($fail == ""){ //if all validation cases pass
            echo "Form has been validated<br>";
            $password = password_hash(sanitizeString($_POST["password"]), PASSWORD_DEFAULT);
            enterSignup($conn,$name,$username,$email,$password);
            exit;
        }else{ //print out cases that fail
            echo $fail."<br>";
        }

    }
    //if users log in
    if(isset($_POST["login"])){
        if((sanitizeString($_POST["usernameLog"])) && (sanitizeString($_POST["passwordLog"]))){
            $username = sanitizeString($_POST["usernameLog"]);
            $password = sanitizeString($_POST["passwordLog"]);
            validateLogin($conn, $username, $password); //checks if login credential are correct
        }
    }
}
?>