<?php 

 class lisp{
   
   private $pointer = -1;
   private $tokens = array();
   private $ast = array();
   private $cb_ast = array();
   private $libs = null;
   private $scope = null;


   function __construct(){
   	$this->libs = new Libs($this);
   	$this->scope = new Scope();
   } 

   function get_scope(){
   	return $this->scope;
   }

   function set_lib($lib){
     $this->libs = $lib;
   }

   function set_code($v){

   	$interlockers = array(" "=>"","(",")","\n","\r");
   	$code = $v;

   	$code = trim($code);


    //-->space
   		$code = explode(' ', $code);
   		$code = implode("_r_", $code);

   //-->(
   		$code = explode('(', $code);
   		$code = implode("_r_(_r_", $code);


   //-->)
   		$code = explode(')', $code);
   		$code = implode("_r_)_r_", $code);

   //-->\n
   		$code = explode("\n", $code);
   		$code = implode("_r_", $code);

   //-->\r
   		$code = explode("\r", $code);
   		$code = implode("_r_", $code);

   
        $code = explode("_r_", $code);

    foreach ($code as $k=>$v_){
      if (!empty(trim($v_)) && trim($v_) != ''){
        $this->tokens[] = $v_;
      }
    }

   }

   function set_tokens($tokens=array()){
     $this->tokens = $tokens;
   }

   function reset(){
   	$this->pointer = -1;
   }

   function run($code){
     
     //echo $code . '<br />';

     $ast = array();
     $this->reset();
     //$this->set_tokens($code);
     $this->set_code($code);
     $this->parse($ast,null);
     //print_r($ast);
     $this->eval_($ast,0);

   }

   function set_cb_ast(&$ast,$i){
     $this->cb_ast[$i] =& $ast;
   }

   function parse(&$ast,$cb=null){
   	++$this->pointer;

   	if (count($this->tokens) <= $this->pointer){
      return;
   	}

   	//echo 'PC' . $this->pointer . ',' . $this->tokens[$this->pointer] . '<br />';

   	if ($this->tokens[$this->pointer] == '('){
      $new_ast = array();
      $ast[] =& $new_ast;
      //$this->set_cb_ast($ast);

      $cb_ = function() use (&$ast,$cb){
      	
        $this->parse($ast,$cb);

      };

      //echo $cb_;

      $this->parse($new_ast,$cb_);

      //print_r($new_ast);

   	}else if ($this->tokens[$this->pointer] == ')'){
   	  //++$this->pointer;	
   		
      //$this->parse($this->cb_ast);
   		//echo 'Calling<br />';
   		//echo $cb;
      $cb();

   	}else{
   	  
   	  $ast[] = $this->tokens[$this->pointer];
   	  $this->parse($ast,$cb);

   	}
     
     

   }

   function eval_(&$ast=array(),$pos=0){
     
     if (is_array($ast[$pos])){
       return $this->eval_($ast[$pos]);
     }else{
        
        $cmd = $ast[$pos];
        //echo $ast . '<br />';
        $args = array_slice($ast, 1);
        // echo $cmd;
        // echo $ast;

        if ($this->libs->has_cmd($cmd)){//check for native functions here
        	return call_user_func_array(array($this->libs,$cmd), $args);		          
        }else{
        	if ($this->libs->is_user_defined($cmd)){ //check for user defined functions here
              $code = $this->libs->get_user_defined($cmd);
              //print_r($code);
              return $this->eval_($code['block'],0);
        	}else{
             
        	//interpolate ast to p
        	if (is_array($ast)){
              $p = $ast[$pos];
        	}else{
              $p = $ast;
        	}

	        	if ($this->scope->has_key($p)){
	               return $this->scope->get($p); //return interpreted variable
	        	}else{
	               return $p; //return constant here ...
	        	}


        	}          
        }

     }

   }



 }

 class Scope{

   private $data = array();



   function set($name,$v){
     $this->data[$name] = $v;
   }

   function get($name){
     return $this->data[$name];
   }

   function has_key($name){
     return isset($this->data[$name]);
   }

 }


 class Libs{

    private $lisp = null;
    // private $scope = null;
    private $user_defined = array();


    function __construct($lisp_){
      $this->lisp = $lisp_;
      // $this->scope = new Scope();
    }

    function has_cmd($cmd){
      return method_exists($this, $cmd);
    }

    function is_user_defined($cmd){
      return isset($this->user_defined[$cmd]);
    }

    function add_user_defined($cmd,$args,$ast){
     $this->user_defined[$cmd] = array("args"=>$args,"block"=>$ast);
    }

    function get_user_defined($cmd){
      return $this->user_defined[$cmd];
    }


    function add(){

    	$args = func_get_args();

    	//print_r($args);

    	$sum = 0;

    	foreach ($args as $k=>$v){
    		//echo $this->lisp->eval_($v,0) . '<br />'; 
          $sum+=$this->lisp->eval_($v,0);
    	}

    	return $sum;

    }


    function mult(){

    	$args = func_get_args();

    	$mult = 1;

    	foreach ($args as $k=>$v){
          $mult*=$this->lisp->eval_($v,0);
    	}

    	return $mult;
    	
    }



    function assign(){

    	$args = func_get_args();

    	$k = $args[0];
    	$v = $this->lisp->eval_($args[1],0);

    	$this->lisp->get_scope()->set($k,$v);

    	return $this->lisp->get_scope()->get($k);
    	
    }

    function block(){
    	
    	$args = func_get_args();

    	//print_r($args);

    	$r = null;

    	foreach ($args as $k=>$v){

    		$r = $this->lisp->eval_($v,0);

    		//echo $r;

    	}

    	//echo $r;

    	return $r;

    }

    function out(){
    	$args = func_get_args();
    	$r = array();
    	
    	foreach ($args as $k=>$v){
    		//print_r($v);
           $r[] = $this->lisp->eval_($v,0);
    	}
    	
    	//print_r($r);
    	$out = implode(' , ', $r);

    	echo $out;
 
    	return $out;

    }


    function defn(){
    	
    	$args = func_get_args();
    	//print_r($args);
    	$this->add_user_defined($args[0],$args[1],$args[2]);

    }






 }




?>
