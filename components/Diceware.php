<?php

/**
 * @author Jan Was <jwas@nets.com.pl>
 * @link https://github.com/nineinchnick/diceware
 */
class Diceware {
  private $rolls;
  private $char_table, $special_table, $pos_table;
  private $word_list;

  private $char_str = '[["~","&","+",":","?","4"],["!","*","[",";","/","5"],["#","(","]","\"","0","6"],["$",")","\\\\","\'","1","7"],["%","-","{","<","2","8"],["^","=","}",">","3","9"]]';
  private $special_str = '[ ["!","@","#","$","%","^"], ["&","*","(",")","-","="], ["+","[","]","{","}","\\\\"], ["|","`",";",":","\'","\""], ["<",">","/","?",".",","], ["~","_","3","5","7","9"]]';
  private $pos_str = '[ ["1","2","0","1","2","0"], ["1","2","3","0","*","*"], ["1","2","3","4","0","*"], ["1","2","3","4","5","0"], ["1","2","3","4","5","6"]]';

  public function get_integers($num, $min, $max) {
	  $buffer = '';
	  $buffer_valid = false;
	  if (function_exists('mcrypt_create_iv') && !defined('PHALANGER')) {
		  $buffer = mcrypt_create_iv($num, MCRYPT_DEV_URANDOM);
		  if ($buffer) {
			  $buffer_valid = true;
		  }
	  }
	  if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes')) {
		  $buffer = openssl_random_pseudo_bytes($num);
		  if ($buffer) {
			  $buffer_valid = true;
		  }
	  }
	  if (!$buffer_valid && is_readable('/dev/urandom')) {
		  $f = fopen('/dev/urandom', 'r');
		  $read = strlen($buffer);
		  while ($read < $num) {
			  $buffer .= fread($f, $num - $read);
			  $read = strlen($buffer);
		  }
		  fclose($f);
		  if ($read >= $num) {
			  $buffer_valid = true;
		  }
	  }
	  if (!$buffer_valid || strlen($buffer) < $num) {
		  $bl = strlen($buffer);
		  for ($i = 0; $i < $num; $i++) {
			  if ($i < $bl) {
				  $buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
			  } else {
				  $buffer .= chr(mt_rand(0, 255));
			  }
		  }
	  }
	  $output = array();
	  $bl = strlen($buffer);
	  for ($i = 0; $i < $bl; $i++) {
		  $output[] = (ord($buffer[$i]) % $max) + $min;
	  }
	  return $output;
  }

  private function get_rolls($num) {
    // can we fulfill the request?
    if($num > count($this->rolls)) {
      // if not, get more dice rolls
      // make sure we get at least as many rolls as we need. Otherwise,
      // use 90 (if it's bigger), to save on API calls
      $req = (90 > $num) ? 90 : $num;
      $this->rolls = $this->get_integers($req, 1, 6);
    }
    // peel off the requested number of rolls and return them
    return array_splice($this->rolls,0,$num);
  }

  public function get_number($count) {
    $result = array();
    for($i=0; $i<$count; $i++) {
      $res = 0;
      do {
        $first_dice = $this->get_rolls(1);
      } while ($first_dice[0]==6);
      $second_dice = $this->get_rolls(1);
      $first_dice = $first_dice[0];
      $second_dice = $second_dice[0];
      if($second_dice%2) {
        $res = $first_dice;
      } else {
        $res = 5+$first_dice;
        if($res==10) {
          $res = 0;
        }
      }
      $result[$i] = $res;
    }
    return $result;
  }

  public function get_phrase_array($length,$extraDigit=false,$extraChar=false) {
    $rolls = 5*$length;
    $input = $this->get_rolls($rolls);
    $output = array();
    $extraDigitWord = "";
    $extraCharWord = "";
    
    if($extraChar) {
      // pick a word
      do {
        $roll = $this->get_rolls(1);
        $extraCharWord = $roll[0];
      } while ($extraCharWord>$length);
      $extraCharWord = $extraCharWord-1;
	  // pick a char
      $rollc = $this->get_rolls(2);
      $char = array($this->char_table[$rollc[0]-1][$rollc[1]-1]);
    }

    if($extraDigit) {
      // pick a word
      do {
        $roll = $this->get_rolls(1);
        $extraDigitWord = $roll[0];
      } while ($extraDigitWord>$length);
      $extraDigitWord = $extraDigitWord-1;
	  // pick a digit
	  $rolld = $this->get_integers(1,0,9);
      $digit = $rolld[0];
    }

    // loop and handle every 5 numbers
    for($i=0; $i<$length; $i++) {
      // get 5 numbers and convert them to a string
      $dice = $this->get_rolls(5);
      $num = implode("",$dice);
      // look up the word in the word list
      $word = $this->word_list[$num];
      // get the right word
      if($extraChar&&($i==$extraCharWord)) {
        do {
          $roll = $this->get_rolls(1);
          $roll = $roll[0];
          // get the right character
          $pos = $this->pos_table[strlen($word) % 5][$roll-1];
        } while ($pos=="*");
        // insert the character in the specified word
        $str = str_split($word);
        array_splice($str,$pos,0,$char);
        $word = implode("",$str);
      }
      if($extraDigit&&($i==$extraDigitWord)) {
        $word .= $digit;
      }
      // add the word to an output array
      array_push($output,$word);
    }
    // return the result
    return $output;
  }

  public function get_phrase($length,$extraDigit=false,$extraChar=false,$separator='',$map='ucfirst') {
	  $parts=$this->get_phrase_array($length,$extraDigit,$extraChar);
	  if ($map!==null)
		  $parts=array_map($map,$parts);
	  return implode($separator,$parts);
  }

  public function get_character() {
    $rolls = $this->get_rolls(2);
    return $this->special_table[$rolls[0]][$rolls[1]];
  }

  private function get_words($file) {
	  $output = array();
	  $handle = @fopen($file, "r");
	  if ($handle) {
		  while (($buffer = fgets($handle, 4096)) !== false) {
			  $parts = explode(' ',str_replace("\t",' ',trim($buffer)),2);
			  if (count($parts)>1) {
				  $output[$parts[0]] = $parts[1];
			  }
		  }
		  if (!feof($handle)) {
			  // pass
		  }
		  fclose($handle);
	  }
	  return $output;
  }

  function __construct() {
    $this->rolls = array();
    $this->char_table = json_decode($this->char_str);
    $this->special_table = json_decode($this->special_str);
    $this->pos_table = json_decode($this->pos_str);
	$path = dirname(__FILE__).DIRECTORY_SEPARATOR;
	if (file_exists($path."diceware.wordlist.".Yii::app()->language)) {
		$wordlist = $path."diceware.wordlist.".Yii::app()->language;
	} else {
		$wordlist = $path."diceware.wordlist";
	}
    $this->word_list = $this->get_words($wordlist);
  }
}

