<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
//
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU Affero General Public License as
//	published by the Free Software Foundation, either version 3 of the
//	License, or (at your option) any later version.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU Affero General Public License for more details.
//
//	You should have received a copy of the GNU Affero General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

include_once("core.php");
include_once("include/imgresizer.php");

// URI: i={$this->id}&amp;s={$this->storage}&amp;x={$this->dim_x}&amp;y={$this->dim_y}&amp;q={$this->quality}&amp;t={$this->type}&f={$this->fix_aspect}&n={$this->no_enlarge}

$i=@$_GET['i'];
$s=@$_GET['s'];
$x=@$_GET['x'];
$y=@$_GET['y'];
$q=@$_GET['q'];
$t=@$_GET['t'];
$n=@$_GET['n'];
$f=@$_GET['f'];

settype($i,"integer");
settype($x,"integer");
settype($y,"integer");
settype($q,"integer");

$img=new ImageProductor($i,$s,$t);
$img->SetX($x);
$img->SetY($y);
$img->SetQuality($q);
$img->SetNoEnlarge($n);
$img->SetFixAspect($f);
$img->MakeAndStore();

?>