<?php 
 
 require_once('lisp.php');

 $lisp = new Lisp();

 // $lisp->run(array("(","add",1,13,"(","mult",2,3,")","(", "mult",3,7,")",")"));
//  $lisp->run('
 	// (block 
 	//   (defn my_add (x y)
 	//                (block (add 230 1)) )
  //     (add 1 3)
  //     (mult 11 3)
  //     (assign w (add 2 3))      
  //     (assign w (add w 2 1))
  //     (assign w (add (w) w 3))
  //     (out (my_add 90))
  //     )

// ');

 // (add 1 13 (mult 2 63) (mult 3 7))

 //(add 1 13 (mult 2 63) (mult 3 7))

?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>

<form method="post">
<h1>PHP POWERED LISP INTERPRETER</h1>
	
	<textarea style="width: 415px;height: 310px;" cols="20" rows="7" name="code"><?php 
      if (isset($_REQUEST['code'])){
        echo $_REQUEST['code'];
      }
	?></textarea><br />
	<input type="submit" name="run_code" value="RUN CODE" />

</form>
<div>
<?php 
	if (isset($_REQUEST['run_code'])){
	  $lisp->run($_REQUEST['code']);
	} 
?>	
</div>

</body>
</html>
