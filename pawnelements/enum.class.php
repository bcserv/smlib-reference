<?php

define('PAWNENUM_TYPE_NORMAL',	0);
define('PAWNENUM_TYPE_FUNC',	1);

class PawnEnum extends PawnElement
{
	static $types = array(
		'enum',
		'funcenum'
	);
	
	protected $elements = array();
	
	static function IsPawnElement($pawnParser)
	{
		if (in_array($pawnParser->GetCurrentWord(), PawnEnum::$types)) {
			return true;
		}

		return false;
	}
	
	public function Parse()
	{
		parent::Parse();

		$this->ParseType();
		$this->ParseName();
		$this->ParseBody();

		$this->pawnParser->Jump(1);
	}
	
	protected function ParseType()
	{
		$word = $this->pawnParser->GetWord();
		$pos = array_search($word, PawnEnum::$types);
		
		if ($pos !== false) {
			$this->type = $pos;
		}
	}
	
	protected function ParseName()
	{
		$pp = $this->pawnParser;

		$pp->SkipWhiteSpace();

		if ($pp->ReadChar(true, true) != '{') {
			$this->name = $pp->GetWord();
		}
	}
	
	protected function ParseBody()
	{
		$pp = $this->pawnParser;

		$body = "";
		$inBody = false;

		while (($char = $pp->ReadChar(true, false)) !== false) {
			
			if ($char == '{') {
				$inBody = true;
				continue;
			}
			else if ($char == '}') {
				break;
			}
			
			if ($inBody) {
				$body .= $char;
			}
		}
		
		$body = trim($body);
		
		if ($this->type == PAWNENUM_TYPE_NORMAL) {
			$lines = explode(',', $body);
		}
		else {
			$lines = explode('),', $body);
		}

		$n = 0;
		foreach ($lines as $line) {
			
			$line = trim($line);
			
			if (empty($line)) {
				continue;
			}
			
			if ($this->type == PAWNENUM_TYPE_NORMAL) {
				$n = $this->ParseNormalEnumLine($line, $n);
				$n++;
			}
			else {
				$this->ParseFuncEnumLine($line);
			}
		}
		
		$this->body = $body;
        $this->lineEnd = $pp->GetLine();
	}
	
	protected function ParseNormalEnumLine($line, $n)
	{
		$element = array();
			
		$pos = strpos($line, '=');
		
		if ($pos !== false) {
			$first = substr($line, 0, $pos);
			$element['value'] = trim(substr($line, $pos+1));

			if (is_numeric($element['value'])) {
				$n = $element['value'];
			}
		}
		else {
			$first = $line;
			$element['value'] = $n;
		}
		
		$pos = strpos($first, ':');
		
		if ($pos !== false) {
			$element['type'] = substr($first, 0, $pos);
			$element['name'] = substr($first, $pos);
		}
		else {
			$element['type'] = '';
			$element['name'] = $first;
		}
		
		$this->elements[] = $element;
		
		return $n;
	}
	
	protected function ParseFuncEnumLine($line)
	{
		$pawnFunction = new PawnFunction($this->pawnParser);

		$toks = explode('(', $line);
		
		$pawnFunction->ParseReturnType($toks[0]);
		$pawnFunction->ParseArguments($toks[1]);
		
		$this->elements[] = $pawnFunction;
	}
	
	public function __toString()
	{
        return 'Enum (' . $this->GetName() . ')';
    }
}
