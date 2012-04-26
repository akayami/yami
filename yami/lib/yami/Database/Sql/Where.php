<?php
namespace yami\Database\Sql;
use yami\Database\Sql\ConditionBlock;

class Where extends Expression {
	
	protected $operators = array('OR', 'XOR', 'AND');
	
	public function __construct($expression) {
		$this->block = $this->splitLevel($expression);
//		$this->block = $this->parse($expression);
	}
	
	public function __toString() {
		return $this->block->__toString();
	}
	
	
	protected function parse($string, $layer = 0) {
		preg_match_all("/\((([^()]*|(?R))*)\)/",$string,$matches);	
		print_r($matches);	
		if (count($matches[1]) > 0) {
			for ($i = 0; $i < count($matches[1]); $i++) {
				if (is_string($matches[1][$i]) && (strlen($matches[1][$i]) > 0)) {

					echo "\n".$matches[0][$i];
					if(($return = $this->parse($matches[1][$i], $layer + 1)) == false) {
						print_r($this->splitLevel($matches[1][$i]));	
					}						
// 					if(($return = $this->parse($matches[1][$i], $layer + 1)) === false) {
// 						$output = $this->splitLevel($matches[1][$i]);
// 						return array('matched' => $matches, 'parsed' => $output);
// 					} else {
// 						print_r($return);
// 						$reference_rand = mt_rand(1, 10000).microtime(true); 
// 						$ref = '[ref] = '.$reference_rand; 						
//  						return $this->splitLevel(str_replace($return['matched'][0], $ref, $matches[1][$i]), array($ref => $return['parsed']));
// 					}
				}
			}
		} else {
			return false;
		}
	}
	
	protected function splitLevel($string, $binding = array(), $level = 0) {
		$operator = $this->operators[$level];		
		$split = preg_split('/\s+'.$operator.'\s+/i', $string);
		if(count($split) > 1) {
			$out = new ConditionBlock($operator);
			foreach($split as $cond) {
				if(isset($binding[$cond])) {
					$out->add($binding[$cond]);
				} else {
					if(($level) < count($this->operators) - 1) {
						$out->add($this->splitLevel($cond, $binding, $level + 1));
					} else {
						$out->add(new Condition($cond));
					}
				}
			}
		} else {
			if(($level) < count($this->operators) - 1) {
				return $this->splitLevel($string, $binding, $level + 1);
			} else {
				return new Condition($string);
			}
		}
		return $out;
	}
}