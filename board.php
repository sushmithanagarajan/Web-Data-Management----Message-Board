<html>
<head>
  <script type="text/javascript">
    function getPostData(id){
      var mes = document.getElementById('newpost').value;
      document.getElementById('post_'+id).value = mes;
    }
  </script>
</head>
<body>
  <h1><center>Message board to submit new messages or reply to old posts.</center></h1>

<form method="get" action="board.php">
<center><input type="submit" name="logout" value="logout"/>
</center>
<br>
<br>
</form>
<form action =board.php method=POST>
<input type="text" cols="40" 
       rows="5" 
       style="width:750px; height:150px;" id="newpost" name="newpost"/><br>
<input type="submit" name="submit" value="Type a New Post"/>
<br>
<br>
<br>
</form>
<?php
 session_start();
error_reporting(E_ALL);
ini_set('display_errors','On');
//try connecting the phpmyadmin DB

try {
  $dbh = new PDO("mysql:host=127.0.0.1:3306;dbname=board","root","",array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
  if(isset($_GET['logout'])){
    session_destroy();
    //for a logout we have to destroy the session
    header('Location: login.php');
    exit();
  }
  //check if the username exsists or not in the DB
  //all the passwords are md5 encoded
  if(isset($_POST['username']) && isset($_POST['password'])){
    $get = 'SELECT username,password from USERS where username="'.$_POST['username'].'"';
    $res = $dbh->query($get,PDO::FETCH_ASSOC);
    $res = $res->fetchAll();
    if($res[0]['password']== md5($_POST['password'])){
     //the session gets username and password and validates the inputs for them here
      $_SESSION["posted"] = $res[0]['username'];
    }
    else{
      //this leads to page redirection
      header('Location: login.php');
    exit();
    }  
  }
  //these are functionality for a new post function
  if(isset( $_SESSION['posted'])){
  if(isset($_POST["newpost"]) && !isset($_GET["messageid"])){
  $insert = 'INSERT INTO POSTS VALUES(:id,:replyto,:postedby,now(),:message)';
  $sth = $dbh->prepare($insert, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
  $sth->execute(array(':id' => uniqid(), ':replyto' => null, ':postedby'=> $_SESSION['posted'],':message'=> $_POST['newpost']));
  }
  
   if(isset($_GET["messageid"]) && isset($_GET["replyPostData"])){
  // replyto the messsage functionality
    if($_GET["replyPostData"]!=null){
      $uuid = uniqid();
      $replyMsgId = $_GET["messageid"];
      $replyMessage = $_GET["replyPostData"];
      $insert = 'INSERT INTO POSTS VALUES(:id,:replyto,:postedby,now(),:message)';
      $sth = $dbh->prepare($insert, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      $sth->execute(array(':id' => $uuid, ':replyto' => $replyMsgId, ':postedby'=> $_SESSION['posted'],':message'=>$replyMessage));
      }
    }

   //perform inner join to get values from two tables
   $sql = 'select * from posts inner join users where posts.postedby = users.username order by datetime DESC';
   print "<pre>";
   foreach ($dbh->query($sql) as $row)  { 
     echo '<form name=\'form_'.$row['id'].'action=\'board.php\' method=\'get\'>';
     //echo '<input type=hidden name="replyto" value="'.$row['id'].'"/>';
     print'Message Id: '.$row['id']."\n";
     print'Username: '.$row['username']."\n".'<b>Full Name: </b>'.$row['fullname']."\n";
     print'Date&Time: '.$row['datetime']."\n";
     if($row['replyto']!=null)
      print'Replied to Message with message Id: '.$row['replyto']."\n";
     print'Message: '.$row['message']."\n";
     echo '<input type="hidden" id="messageid" name="messageid" value='.$row['id'].'>';
     echo '<input type="hidden" id="post_'.$row['id'].'"name="replyPostData">';
     ?>
    <input type="submit" value="Reply" onclick="getPostData('<?php echo $row['id'] ?>')">
    <?php
     echo '</form>';
  }  
 }
 else{
  //redirect to login page
  header('Location: login.php');
    exit();
 }
 } 
 // catch the exception if db does not get connected
catch (PDOException $e) {
  print "Error!: " . $e->getMessage() . "<br/>";
  die();
}
?>
</body>
</html>