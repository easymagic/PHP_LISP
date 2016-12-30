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
     $this->eval_($ast,0,(new Scope()));

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

   function eval_(&$ast=array(),$pos=0,&$scope=null){
     //print_r($scope);
     // print_r($ast);
     if (is_array($ast[$pos]) && !is_object($ast)){
       return $this->eval_($ast[$pos],0,$scope);
     }else if (is_object($ast)){  
       //print_r($ast);
       return $ast;
     }else{
        
        $cmd = $ast[$pos];
        //echo $ast . '<br />';
        // echo $cmd;
        // echo $ast;


        if ($this->libs->has_cmd($cmd)){//check for native functions here
          //echo $cmd . '<br />';
          $args = array_slice($ast, 1);
            array_unshift($args, $scope);
            $args[0] = & $scope;
          return call_user_func_array(array($this->libs,$cmd), $args);              
        }else{
          if ($this->libs->is_user_defined($cmd)){ //check for user defined functions here
          
            $args = array_slice($ast, 1);
            //print_r($args);
              //array_unshift($args, $scope);           

              $code = $this->libs->get_user_defined($cmd);
              $sub_args = $code['args'];
              $sub_scope = new Scope($scope);
              

              foreach ($sub_args as $k=>$v){
                 $sub_scope->set($v,$this->eval_($args,$k,$scope)); //interprete variables at the root scope being passed as parameter to the function.
              }
              //print_r($code);
              $rr_ = $this->eval_($code['block'],0,$sub_scope);

              if ($scope->is_classic()){//let the top level scope have a sychronized copy with the local scope.
                $scope->_copy($sub_scope); 
              }

              return $rr_;

          }else if ($this->libs->is_class_user_defined($cmd)){

            //
                
          }else{
             
          //interpolate ast to p
          if (is_array($ast)){
              $p = $ast[$pos];
          }else{
              $p = $ast;
          }

            if ($scope->has_key($p)){
                 return $scope->get($p); //return interpreted variable
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
   private $scope_type = 'global';

   function __construct($scp=null){
     if ($scp != null){
       $this->_copy($scp);
     }
   }

   function set_global(){
     $this->scope_type = 'global';
   }

   function set_classic(){
     $this->scope_type = 'classic';
   }

   function is_global(){
    return ($this->scope_type == 'global');
   }

   function is_classic(){
    return !$this->is_global();
   }


   function _copy($scp){
    $prp = $scp->all();
     foreach ($prp as $k=>$v){
       $this->data[$k] = $v;
     }
   }


   function set($name,$v){
     $this->data[$name] = $v;
   }

   function get($name){
     return $this->data[$name];
   }

   function all(){
    return $this->data;
   }

   function has_key($name){
     return isset($this->data[$name]);
   }

 }


 class Libs{

    private $lisp = null;
    // private $scope = null;
    private $user_defined = array();
    private $class_user_defined = array();


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

    function add_class_user_defined($cmd,$ast){
       $this->class_user_defined[$cmd] = array(
         "block"=>$ast
       );
    }

    function get_class_user_defined($cmd){
      return $this->class_user_defined[$cmd];
    }

    function is_class_user_defined($cmd){
     return isset($this->class_user_defined[$cmd]);
    }


    function add(){

      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);     

      //print_r($args);

      $sum = 0;

      foreach ($args as $k=>$v){
        //echo $this->lisp->eval_($v,0) . '<br />'; 
          $sum+=$this->lisp->eval_($v,0,$scope);
      }

      return $sum;

    }

    function dot(){

      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);     

      //print_r($args);

      $sum = '';

      foreach ($args as $k=>$v){
        //echo $this->lisp->eval_($v,0) . '<br />'; 
          $sum.=$this->lisp->eval_($v,0,$scope);
      }

      return $sum;

    }



    function minus(){

      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);     

      //print_r($args);


      $sum = $this->lisp->eval_($args,0,$scope) * 2;

      foreach ($args as $k=>$v){
        //echo $this->lisp->eval_($v,0) . '<br />'; 
          $sum-=$this->lisp->eval_($v,0,$scope);
      }



      return $sum;

    }



    function mult(){

      $args = func_get_args();
      $scope = $args[0];
      $args = array_splice($args, 1);

      //print_r($args);

      $mult = 1;

      foreach ($args as $k=>$v){
        //echo $this->lisp->eval_($v,0,$scope) . '<br />';
          $mult*=$this->lisp->eval_($v,0,$scope);
      }

      return $mult;
      
    }



    function assign(){

      $args = func_get_args();

      $scope =& $args[0];
      $args = array_splice($args, 1);

      $k = $args[0];
      $v = $this->lisp->eval_($args[1],0,$scope);

      // echo '<br />';
      // print_r($v);
      // echo $k;
      // echo '<br />';


      $scope->set($k,$v);

      //print_r($scope);

      //print_r($scope->get($k));
      return $scope->get($k);
      
    }

    function block(){
      
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);


      //print_r($args);

      $r = null;

      foreach ($args as $k=>$v){

        $r = $this->lisp->eval_($v,0,$scope);

        //echo $r;

      }

      //echo $r;

      return $r;

    }

    function dump(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);

      $r = array();
      
      foreach ($args as $k=>$v){
        //print_r($v);
           $r[] = $this->lisp->eval_($v,0,$scope);
      }
      
      print_r($r);
    
 
      
    }

    function out(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);

      $r = array();
      
      foreach ($args as $k=>$v){
        //print_r($v);
           $r[] = $this->lisp->eval_($v,0,$scope);
      }
      
      //print_r($r);
      $out =  implode(' , ', $r) . '<br />';

      echo $out;
 
      return $out;

    }


    function defn(){      
      
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);

      //print_r($args);
      $this->add_user_defined($args[0],$args[1],$args[2]);

    }

    function iff(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);

      //print_r($args);

      if ($this->lisp->eval_($args,0,$scope)){
          return $this->lisp->eval_($args,1,$scope);
      }else{
          return $this->lisp->eval_($args,2,$scope);
      }

    }


    function _eq_(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);
      // print_r($args);
      // echo $this->lisp->eval_($args,0,$scope) . '<br />';
      // echo $this->lisp->eval_($args,1,$scope) . '<br />';
      return ($this->lisp->eval_($args,0,$scope) == $this->lisp->eval_($args,1,$scope));

    }

    function _neq_(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);
      return ($this->lisp->eval_($args,0,$scope) != $this->lisp->eval_($args,1,$scope));

    }


    function _lt_(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);
      return ($this->lisp->eval_($args,0,$scope) < $this->lisp->eval_($args,1,$scope));

    }


    function _gt_(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);
      return ($this->lisp->eval_($args,0,$scope) > $this->lisp->eval_($args,1,$scope));

    }



    function _ge_(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);
      return ($this->lisp->eval_($args,0,$scope) >= $this->lisp->eval_($args,1,$scope));

    }


    function _le_(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);
      return ($this->lisp->eval_($args,0,$scope) <= $this->lisp->eval_($args,1,$scope));

    }




    function str(){

      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);

      $r = array();
      
      foreach ($args as $k=>$v){
        //print_r($v);
           $r[] = $this->lisp->eval_($v,0,$scope);
      }
      
      //print_r($r);
      $out =  implode(' ', $r);
 
      return $out;

    }

    function defclass(){
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);

      $this->add_class_user_defined($args[0],$args[1]);

    }

    function create_obj(){
      
      $args = func_get_args();

      $scope = $args[0];
      $args = array_splice($args, 1);

      $new_scope = new Scope();
      $new_scope->set_classic();
  
      
      $this->lisp->eval_($args,0,$new_scope);
  
      return $new_scope;

    }


    function call_prop(){

      $args = func_get_args();

      $scope =& $args[0];
      $args = array_splice($args, 1);
      //print_r($scope);


      $target_scope =& $this->lisp->eval_($args[0], 0,$scope);

      //print_r($target_scope);

      return $this->lisp->eval_($args, 1, $target_scope); 

    }




 }




?>
