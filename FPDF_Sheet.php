<?php

require_once('fpdf/fpdf.php');

class FPDF_Sheet extends FPDF {

	var $ProcessingTable=false;
	var $aCols = array();
	var $TableX;
	var $HeaderColor;
	var $RowColors;
	var $ColorIndex;
	var $FooterText;
	var $Title;
	var $TitleFont = 'Arial';
	var $TitleSize = '18';
	var $TitleStyle = 'B';
	var $FooterFont = 'Arial';
	var $FooterSize = '10';
	var $FooterStyle = 'I';
	var $TextFont = 'Arial';
	var $TextSize = '11';
	var $TextStyle = '';
	var $Logo;
	
	function SetTitle($title, $isUTF8=false) {
		parent::SetTitle($title, $isUTF8);
		$this->Title = $title;
	}
	
	function SetTitleFont($font, $style, $size) {
		$this->TitleSize = $size;
		$this->TitleFont = $font;
		$this->TitleStyle = $style;
	}
	
	function SetFooterFont($font, $style, $size) {
		$this->FooterSize = $size;
		$this->FooterFont = $font;
		$this->FooterStyle = $style;
	}
	
	function SetTextFont($font, $style, $size) {
		$this->TextSize = $size;
		$this->TextFont = $font;
		$this->TextStyle = $style;
	}
	
	function SetLogo($logo) {
		$this->Logo = $logo;
	}
	
	function SetFooterText($footer) {
		$this->FooterText = $footer;
		$this->AliasNbPages();
	}

	function Header() {
		//Logo
		if (isset($this->Logo)) {
    		$this->Image($this->Logo,10,8,33);
		}
		if (isset($this->Title)) {
			$this->SetFont($this->TitleFont, $this->TitleStyle, $this->TitleSize);
		    //Move to the right
		    $this->Cell(80);
		    //Title
		    $this->Cell(35,20,$this->Title,0,1,'C');
		    //Line break
		    $this->Ln(5);
		}
		
		//Print the table header if necessary
		if($this->ProcessingTable) {
			$this->TableHeader();
		}
	}

	function TableHeader() {
		$this->SetFont($this->TextFont,'B', $this->TextSize);
		$this->SetX($this->TableX);
		$fill=!empty($this->HeaderColor);
		if($fill) {
			$this->SetFillColor($this->HeaderColor[0],$this->HeaderColor[1],$this->HeaderColor[2]);
		}
		foreach($this->aCols as $col) {
			$this->Cell($col['w'],6,$col['c'],1,0,'C',$fill);
		}
		$this->Ln();
	}

	function Row($data, $vpadding) {
		$this->SetX($this->TableX);
		$ci=$this->ColorIndex;
		$fill=!empty($this->RowColors[$ci]);
		if($fill) {
			$this->SetFillColor($this->RowColors[$ci][0],$this->RowColors[$ci][1],$this->RowColors[$ci][2]);
		}
		
		// Extract from http://www.fpdf.de/downloads/addons/3/
		//Calculate the height of the row
	    $nb = 0;
	    $i = 0;
	    foreach($this->aCols as $col) {
	        $nb=max($nb, $this->NbLines($this->aCols[$i]['w'], $data[$col['f']]));
	        $i++;
	    }
	    $h=$vpadding*$nb;
	    //Issue a page break first if needed
	    $this->CheckPageBreak($h);
		
		foreach($this->aCols as $col) {
			$w=$col['w'];
			//Save the current position
	        $x=$this->GetX();
	        $y=$this->GetY();
	        //Draw the border
	        $this->Rect($x, $y, $w, $h);
	        //Print the text
	        $this->MultiCell($w, $vpadding, $data[$col['f']], 0, $col['a'], $fill);
	        //Put the position to the right of the cell
	        $this->SetXY($x+$w, $y);
		}
		$this->Ln($h);
		$this->ColorIndex=1-$ci;
	}
	
	function CheckPageBreak($h) {
	    //If the height h would cause an overflow, add a new page immediately
	    if($this->GetY()+$h>$this->PageBreakTrigger)
	        $this->AddPage($this->CurOrientation);
	}
	
	/**
	 * Computes the number of lines a MultiCell of width w will take
	 */
	function NbLines($w, $txt) {
	    $cw=&$this->CurrentFont['cw'];
	    if($w==0) {
	        $w=$this->w-$this->rMargin-$this->x;
	    }
	    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	    $s=str_replace("\r", '', $txt);
	    $nb=strlen($s);
	    if($nb>0 and $s[$nb-1]=="\n") {
	        $nb--;
	    }
	    $sep=-1;
	    $i=0;
	    $j=0;
	    $l=0;
	    $nl=1;
	    while($i<$nb) {
	        $c=$s[$i];
	        if($c=="\n") {
	            $i++;
	            $sep=-1;
	            $j=$i;
	            $l=0;
	            $nl++;
	            continue;
	        }
	        if($c==' ')
	            $sep=$i;
	        $l+=$cw[$c];
	        if($l>$wmax) {
	            if($sep==-1) {
	                if($i==$j)
	                    $i++;
	            } else {
	                $i=$sep+1;
	            }
	            $sep=-1;
	            $j=$i;
	            $l=0;
	            $nl++;
	        } else {
	            $i++;
	        }
	    }
	    return $nl;
	}

	/**
	 * Compute the widths of the columns
	 */
	function CalcWidths($width,$align) {
		$TableWidth=0;
		foreach($this->aCols as $i=>$col) {
			$w=$col['w'];
			if($w==-1) {
				$w=$width/count($this->aCols);
			} elseif(substr($w,-1)=='%') {
				$w=$w/100*$width;
			}
			$this->aCols[$i]['w']=$w;
			$TableWidth+=$w;
		}
		//Compute the abscissa of the table
		if($align=='C') {
			$this->TableX=max(($this->w-$TableWidth)/2,0);
		} elseif($align=='R') {
			$this->TableX=max($this->w-$this->rMargin-$TableWidth,0);
		} else {
			$this->TableX=$this->lMargin;
		}
	}

	/**
	 * Add a column to the table
	 */
	function AddCol($field=-1,$width=-1,$caption='',$align='L') {
		if($field==-1) {
			$field=count($this->aCols);
		}
		$this->aCols[]=array('f'=>$field,'c'=>$caption,'w'=>$width,'a'=>$align);
	}
	
	/**
	 * Page footer
	 */
	function Footer() {
		if (isset($this->FooterText)) {
		    //Position at 1.5 cm from bottom
		    $this->SetY(-15);
		    //Arial italic 8
		    $this->SetFont($this->FooterFont, $this->FooterStyle, $this->FooterSize);
		    //Page number
		    $footer = $this->FooterText;
		    $footer = str_replace('{page_num}', $this->PageNo(), $footer);
		    $footer = str_replace('{total_pages}', '{nb}', $footer);
		    $this->Cell(0, 10, $footer, 0, 0, 'C');
		}
	}
	
	function InitializeColumns() {
		//Add all columns if none was specified
		if(count($this->aCols)==0) {
			foreach ($keys as $key) {
				$this->AddCol($key); 
			}
		}
		
		//Retrieve column names when not specified
		foreach($this->aCols as $i=>$col) {
			if($col['c']=='') {
				if(is_string($col['f'])) {
					$this->aCols[$i]['c']=ucfirst($col['f']);
				} else {
					$this->aCols[$i]['c']=ucfirst($keys[$col['f']]);
				}
			}
		}
	}
	
	function HandleProps($prop) {
		if(!isset($prop['width'])) {
			$prop['width']=0;
		}
		if($prop['width']==0) {
			$prop['width']=$this->w-$this->lMargin-$this->rMargin;
		}
		if(!isset($prop['align'])) {
			$prop['align']='C';
		}
		if(!isset($prop['padding'])) {
			$prop['padding']=$this->cMargin;
		}
		if(!isset($prop['HeaderColor'])) {
			$prop['HeaderColor']=array();
		}
		$this->HeaderColor=$prop['HeaderColor'];
		if(!isset($prop['color1'])) {
			$prop['color1']=array();
		}
		if(!isset($prop['color2'])) {
			$prop['color2']=array();
		}
		if(!isset($prop['vert_padding'])) {
			$prop['vert_padding'] = 5;
		}
		return $prop;
	}

	function Table($list, $prop=array()) {
		
		if (count($list) == 0) { // List must have at least one element
			throw new Exception('List must have at least one element');
		}
		$firstElement = $list[0]; // Assume that all element has the same amount of properties
		$keys = array_keys($firstElement);
	
		$this->InitializeColumns();

		$prop = $this->HandleProps($prop);
		$cMargin=$this->cMargin;
		$this->cMargin=$prop['padding'];
		$this->RowColors=array($prop['color1'],$prop['color2']);
		
		//Compute column widths
		$this->CalcWidths($prop['width'],$prop['align']);
		
		//Print header
		$this->TableHeader();
		//Print rows
		$this->SetFont($this->TextFont, $this->TextStyle, $this->TextSize);
		$this->ColorIndex=0;
		$this->ProcessingTable=true;
		foreach ($list as $row) {
			$this->Row($row, $prop['vert_padding']);
		}
		$this->ProcessingTable=false;
		$this->cMargin=$cMargin;
		$this->aCols=array();
	}

}

?>