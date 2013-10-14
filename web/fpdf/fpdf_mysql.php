<?php
require('fpdf.php');

class PDF_MySQL_Table extends FPDF
{
var $ProcessingTable=false;
var $aCols=array();
var $TableX;
var $HeaderColor;
var $RowColors;
var $ColorIndex;

var $numCols;
var $curCol;
var $ColsWidth;
var $headY;

function Header()
{
    //Print the table header if necessary
    if($this->ProcessingTable)
        $this->TableHeader();
}

function Footer()
{
    //Print the table footer if necessary
    if($this->ProcessingTable)
    {
    	//$this->Ln();
    		global $CONFIG;
		$this->SetFont('Arial','',8);
		$this->SetTextColor(0);
		$str = 'Стр. '.$this->PageNo().'.      Цены, выделенные серым, необходимо уточнять! Наш интернет-магазин: ';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$this->SetY($this->GetY()+2);
		$this->Write(4,$str,'');
		$this->SetTextColor(0,0,255);
		$this->SetFont('','U');
		$this->Write(4,'http://'.$CONFIG['site']['name'],'http://'.$CONFIG['site']['name']);
    }
}

function TableHeader()
{
    $this->TableX=$this->lMargin;
    $this->SetFont('Arial','',9);
    $this->SetTextColor(0);
    $this->SetX($this->TableX);
    $fill=!empty($this->HeaderColor);
    if($fill)
        $this->SetFillColor($this->HeaderColor[0],$this->HeaderColor[1],$this->HeaderColor[2]);
    for($i=0;$i<$this->numCols;$i++)
    {
		foreach($this->aCols as $col)
		{
			//$str = iconv('UTF-8', 'windows-1251', $col['c']);
			$this->Cell($col['w'],5,$col['c'],1,0,'C',$fill);
		}
		$this->SetX($this->x+2);
		if($i==0)
			$this->ColsWidth=$this->x-$this->lMargin;
	}
    $this->Ln();
    $this->headY=$this->GetY();
    $this->curCol=0;
}

function Row($data, $divider=0, $cost_id=1)
{
    $this->SetX($this->TableX);
    $ci=$this->ColorIndex;
    $fill=!empty($this->RowColors[$ci]);

    if(!$divider)
    {
	$cost = getCostPos($data['pos_id'], $cost_id);
	if($cost==0)	return;
		if($fill)
			$this->SetFillColor($this->RowColors[$ci][0],$this->RowColors[$ci][1],$this->RowColors[$ci][2]);
		foreach($this->aCols as $col)
		{
			$str=@$data[$col['f']];
			if(($col['f']=='name')&&($data['proizv']!='')) $str.=' ('.$data['proizv'].')';

			if($col['f']=='cost')
			{
				$dcc=strtotime($data['cost_date']);
				if( ($dcc<(time()-60*60*24*30*6))|| ($cost==0) ) $cce=128;
				else $cce=0;
				if(!$cost) $cost='Звоните!';
				else	$cost.=" за ".$data['units_name'];
				$str=$cost;
			} else $cce=0;

			$str = iconv('UTF-8', 'windows-1251', $str);
			$this->SetTextColor($cce);
			$this->Cell($col['w'],4,$str,1,0,$col['a'],$fill);
		}
    }
    else
    {
    	if($fill)
			$this->SetFillColor($this->HeaderColor[0],$this->HeaderColor[1],$this->HeaderColor[2]);
		$str = iconv('UTF-8', 'windows-1251', $data);
		$this->SetTextColor(0);
		$this->Cell($this->aCols[0]['w']+$this->aCols[1]['w']+@$this->aCols[2]['w'],4,$str,1,0,'C',$fill);
    }

    $this->Ln();
    if($this->y+5>$this->PageBreakTrigger)
    {
    	if($this->curCol<($this->numCols-1))
    	{
			$this->curCol++;
			$this->SetY($this->headY);
			$this->TableX=$this->lMargin+($this->ColsWidth*$this->curCol);
		}
		else $this->AddPage();
    }

    $this->ColorIndex=1-$ci;
}

function CalcWidths($width,$align)
{
    //Compute the widths of the columns
    $TableWidth=0;
    foreach($this->aCols as $i=>$col)
    {
        $w=$col['w'];
        if($w==-1)
            $w=$width/count($this->aCols);
        elseif(substr($w,-1)=='%')
            $w=$w/100*$width;
        $this->aCols[$i]['w']=$w;
        $TableWidth+=$w;
    }
    //Compute the abscissa of the table
    if($align=='C')
        $this->TableX=max(($this->w-$TableWidth)/2,0);
    elseif($align=='R')
        $this->TableX=max($this->w-$this->rMargin-$TableWidth,0);
    else
        $this->TableX=$this->lMargin;
}

function AddCol($field=-1,$width=-1,$caption='',$align='L')
{
    //Add a column to the table
    if($field==-1)
        $field=count($this->aCols);
    $this->aCols[]=array('f'=>$field,'c'=>$caption,'w'=>$width,'a'=>$align);
}

function Table($query,$prop=array())
{
	//Retrieve column names when not specified
	foreach($this->aCols as $i=>$col)
	{
		if($col['c']=='')	{
			$this->aCols[$i]['c']=ucfirst($col['f']);
		}
	}
	//Handle properties
	if(!isset($prop['width']))
	$prop['width']=0;
	if($prop['width']==0)
	$prop['width']=$this->w-$this->lMargin-$this->rMargin;
	if(!isset($prop['align']))
	$prop['align']='C';
	if(!isset($prop['padding']))
	$prop['padding']=$this->cMargin;
	$cMargin=$this->cMargin;
	$this->cMargin=$prop['padding'];
	if(!isset($prop['HeaderColor']))
	$prop['HeaderColor']=array();
	$this->HeaderColor=$prop['HeaderColor'];
	if(!isset($prop['color1']))
	$prop['color1']=array();
	if(!isset($prop['color2']))
	$prop['color2']=array();
	$this->RowColors=array($prop['color1'],$prop['color2']);
	//Compute column widths
	$this->CalcWidths($prop['width'],$prop['align']);
	//Print header
	$this->TableHeader();
	//Print rows
	$this->SetFont('Arial','',7);
	$this->ColorIndex=0;
	$this->ProcessingTable=true;

	$this->draw_groups_tree(0, $query, $prop);
	$this->ProcessingTable=false;
	$this->cMargin=$cMargin;
	$this->aCols=array();
}

function draw_groups_tree($pid, $query, $prop)
{
	global $db;
	$res=$db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`='$pid' AND `hidelevel`='0' ORDER BY `id`");
	while($nxt=$res->fetch_row())
	{
		if($nxt[0]==0) continue;
		if(isset($prop['groups']) )
			if( ! in_array($nxt[0],$prop['groups']) )	continue;
		$this->Row($nxt[1],1,$prop['cost_id']);
		$res2=$db->query($query."WHERE `group`='$nxt[0]' AND `doc_base`.`hidden`='0' ORDER BY `name`");
		while($row=$res2->fetch_array())
		{
			$this->Row($row,0,$prop['cost_id']);
		}
		$this->draw_groups_tree($nxt[0], $query, $prop);
	}
}
}
?>