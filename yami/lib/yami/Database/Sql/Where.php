<?php
namespace yami\Database\Sql;
use yami\Database\Sql\ConditionBlock;

class Where extends ConditionBlock {
	
	public function __construct($expression) {
		echo $this->parse($expression);
	}
	
	
	protected function parse($string, $layer = 0) {
		preg_match_all("/\((([^()]*|(?R))*)\)/",$string,$matches);
		echo "\n{$layer}--------------\n";
		print_r($matches);
		if (count($matches) > 1) {
			$match = false;
			for ($i = 0; $i < count($matches[1]); $i++) {
				$match = true;
				if (is_string($matches[1][$i]) && (strlen($matches[1][$i]) > 0)) {
						
					if(($return = $this->parse($matches[1][$i], $layer + 1)) === false) {
						echo $matches[1][$i];
					}
					//  					if(($return = recursiveSplit($matches[1][$i], $layer + 1)) === false) {
					//  						return $matches[1][$i];
					//  					} else {
					//  						echo "\n{$layer}--------------\n";
					//  						echo "\n{$return}\n";
					//  						echo str_replace($return, '*', $matches[1][$i]);
					//  					}
				} else {
					echo "\n{$layer}:No Matches on ".$string;
				}
			}
		} else {
			echo "\n{$layer}:No Matches on ".$string;
			if(!$match) {
				return false;
			}
		}
		echo $this->parsex($string);
	}
	
	protected function parsex($string) {
		$andBlock = new ConditionBlock('AND');		
		$ands = preg_split('/\s+AND\s+/i', $string);
		foreach($ands as $and) {			
			$xors = preg_split('/\s+xor\s+/i', $and);
			foreach($xors as $xor) {
				$ors = preg_split('/\s+or\s+/i', $xor);
				foreach($ors as $or) {
					
				}
			}
		}
		print_r($ands);
	}
	
// 	protected function parse($expression, $layer = 0) {
// 		if(preg_match_all("/\((([^()]*|(?R))*)\)/", $expression, $matches)) {
// 			echo "\n{$layer}--------------\n";
// 			print_r($matches);
// 			if(count($matches) > 1) {
// 				for ($i = 0; $i < count($matches[1]); $i++) {
// 					if (is_string($matches[1][$i]) && (strlen($matches[1][$i]) > 0)) {
// 						if(($return = $this->parse($matches[1][$i], $layer + 1)) === false) {
// //							print_r($matches[1][$i]);
// // 							$and = preg_split('/\s+AND\s+/i', $matches[1][$i]);
// // 							foreach($and as $p) {
// // 								$xor = preg_split('/\s+XOR\s+/i', $p);
// // 								foreach($xor as $q) {
									
// // 									$or = preg_split('/\s+OR\s+/i', $q);
// // 									if(count($or)) {
// // 										$orBlock = new ConditionBlock();									
// // 										foreach($or as $w) {
// // 											$orBlock->add(new Condition($w));
// // 										}
// // 									}
// // 								}
// // 							}							
// //  							if(preg_match('/(and|or)/',$matches[1][$i], $m)) {
// //  								echo "\n---".print_r($m);exit;
// //  							}
// 							//$block = new ConditionBlock($matches[1][$i]);
							
// 							//echo $matches[1][$i];exit;
// 							return $return;
// 						}
// 					}
// 				}
// 			} else {
// 				return false;
// 			}
// 		} else {
// 			return false;
// 		}
// 	}
	
	
	protected function splitConditions($string, $operator = "AND") {
		$out = preg_split('/\s+'.$operator.'\s+/i', $matches[1][$i]);
		if(count($out) > 0) {
			
		} else {
			
		}
	}
}