<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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

/// Герератор изображений. Используется для централизованного получения изображений из хранилища
class ImageProductor {
	protected $id;
	protected $storage;
	protected $type;
	protected $storages=array('p'=>'pos', 'w'=>'wikiphoto', 'f'=>'galery','g'=>'group','n'=>'news', 'a'=>'article');
	protected $types=array('jpg','png', 'gif');
	protected $source_file=null;
	protected $source_exist=null;
	protected $cached=null;
	protected $cache_fclosure='';
	protected $quality=70;		//< Требуемое качество
	protected $dim_x=0;		//< Требуемый размер по x
	protected $dim_y=0;		//< Требуемый размер по y
	protected $fix_aspect=1;	//< Не изменять соотношение сторон (иначе - дополнять фоном)
	protected $no_enlarge=0;	//< Не увеличивать изображение
	protected $show_watermark=1;	//< Показывать наименование магазина поверх изображения
	protected $font_watermark='ttf-dejavu/DejaVuSansCondensed-Bold.ttf';	//< Путь к шрифту для отображения наименования

	public function __construct($img_id, $img_storage, $type='jpg')	{
		global $CONFIG;
		if(!$img_id)	throw new ImageException('ID изображения не задан!');
		$this->id=$img_id;
		if(!array_key_exists($img_storage,$this->storages))	throw new ImageException($img_storage.'Хранилище изображения не задано, либо не существует!');
		$this->storage=$img_storage;
		if(!in_array($type,$this->types))	throw new ImageException('Запрошенный тип изображений не поддерживается');
		$this->type=$type;
		//$this->source_file="{$CONFIG['site']['var_data_fs']}/{$this->storages[$this->storage]}/{$this->id}.{$this->type}";
		//$this->source_exist=file_exists($this->source_file);
		if(@$CONFIG['images']['quality'])	$this->quality=$CONFIG['images']['quality'];
	}

	public function SetX($x) {
		$this->dim_x = $x;
	}

	public function SetY($y) {
		$this->dim_y = $y;
	}

	public function SetQuality($quality) {
		if ($quality > 0)
			$this->quality = $quality;
	}

	public function SetNoEnlarge($flag) {
		$this->no_enlarge = $flag;
	}

	public function SetFixAspect($flag) {
		$this->fix_aspect = $flag;
	}

	/// Возвращает URI изображения. Если изображение есть в кеше - возвращает его. Иначе - возвращает адрес скрипта конвертирования
	public function GetURI($no_encode=false) {
		global $CONFIG;
		if($this->cached==null)	$this->CacheProbe();
		if($this->cached)	return "{$CONFIG['site']['var_data_web']}/{$this->cache_fclosure}";
		else {
			if($no_encode)
				return "/images.php?i={$this->id}&s={$this->storage}&x={$this->dim_x}&y={$this->dim_y}&q={$this->quality}&t={$this->type}&f={$this->fix_aspect}&n={$this->no_enlarge}";
			else
				return "/images.php?i={$this->id}&amp;s={$this->storage}&amp;x={$this->dim_x}&amp;y={$this->dim_y}&amp;q={$this->quality}&amp;t={$this->type}&amp;f={$this->fix_aspect}&amp;n={$this->no_enlarge}";
		}
	}
	
	/// Обёртка над getimagesize для исходника данного изображения
	public function getRealImageSize() {
		global $CONFIG;
		$fn = "{$CONFIG['site']['var_data_fs']}/{$this->storages[$this->storage]}/{$this->id}.{$this->type}";
		return @getimagesize($fn);
	}

	// Есть ли изображение в кеше
	protected function CacheProbe() {
		global $CONFIG;
		$this->cache_fclosure = "cache/{$this->storages[$this->storage]}/{$this->id}-{$this->dim_x}-{$this->dim_y}-{$this->quality}.{$this->type}";
		return $this->cached = file_exists($CONFIG['site']['var_data_fs'] . '/' . $this->cache_fclosure);
	}
	// Сделать изображение и сохранить в кеш
	public function MakeAndStore() {
		global $CONFIG;
		if(@isset($CONFIG['images']['watermark']))
		{
			if(is_array($CONFIG['images']['watermark'])) {
				if(@isset($CONFIG['images']['watermark'][$img_stroage]))	$this->show_watermark=$CONFIG['images']['watermark'][$img_stroage];
			}
			else	$this->show_watermark=$CONFIG['images']['watermark'];
		}
		if(@$CONFIG['images']['font_watermark'])	$this->font_watermark=$CONFIG['images']['font_watermark'];

		$rs=0;
		$this->cache_fclosure="cache/{$this->storages[$this->storage]}/{$this->id}-{$this->dim_x}-{$this->dim_y}-{$this->quality}.{$this->type}";
		$cname="{$CONFIG['site']['var_data_fs']}/{$this->cache_fclosure}";
		$icname="{$CONFIG['site']['var_data_web']}/{$this->cache_fclosure}";

		$this->source_file="{$CONFIG['site']['var_data_fs']}/{$this->storages[$this->storage]}/{$this->id}.{$this->type}";

		@mkdir("{$CONFIG['site']['var_data_fs']}/cache/{$this->storages[$this->storage]}/",0755);

		$dx=$dy=0;

		$sz=getimagesize($this->source_file);
		$sx=$sz[0];
		$sy=$sz[1];
		$stype=$sz[2];

		if($this->dim_x || $this->dim_y) {
		// Жёстко заданные размеры
			$aspect=$sx/$sy;
			if($this->dim_y&&(!$this->dim_x))
			{
				if($this->dim_y>$sy)	$this->dim_y=$sy;
				$this->dim_x=round($aspect*$this->dim_y);
			}
			else if($this->dim_x&&(!$this->dim_y))
			{
				if($this->dim_x>$sx)	$this->dim_x=$sx;
				$this->dim_y=round($this->dim_x/$aspect);
			}
			$naspect=$this->dim_x/$this->dim_y;
			$nx=$this->dim_x;
			$ny=$this->dim_y;
			if($aspect<$naspect)	$nx=round($aspect*$this->dim_y);
			else			$ny=round($this->dim_x/$aspect);
			$lx=($this->dim_x-$nx)/2;
			$ly=($this->dim_y-$ny)/2;

			$rs=1;
		}
		else {
			$this->dim_x=$sz[0];
			$this->dim_y=$sz[1];
		}

		if( ($this->dim_x>$sx || $this->dim_y>$sy) && $this->no_enlarge)	$rs=0;

		if($this->type=='jpg') {
			if(function_exists('imagecreatefromjpeg'))	$im=imagecreatefromjpeg($this->source_file);
			else		throw new ImageException($this->type.' не поддерживается вашей версией PHP!');
		}
		else if($this->type=='png') {
			if(function_exists('imagecreatefrompng'))	$im=imagecreatefrompng($this->source_file);
			else		throw new ImageException($this->type.' не поддерживается вашей версией PHP!');
		}
		else if($this->type=='gif') {
			if(function_exists('imagecreatefromgif'))	$im=imagecreatefromgif($this->source_file);
			else		throw new ImageException($this->type.' не поддерживается вашей версией PHP!');
		}
		else throw new ImageException($this->type.' не поддерживается обработчиком!');

		if($rs)	{
			$im2 = imagecreatetruecolor($this->dim_x,$this->dim_y);
			imagefill($im2, 0, 0, imagecolorallocate($im2, 255, 255, 255));
			imagecopyresampled($im2, $im, $lx, $ly, 0, 0, $nx, $ny, $sx, $sy);
			imagedestroy($im);
			$im=$im2;
		}
		// Оптимизировать большие изображения
		if( $this->dim_x>=300 || $this->dim_y>=300)	imageinterlace($im, 1);

		if($this->show_watermark) {
			$bg_c = imagecolorallocatealpha ($im, 64,64, 64, 96);
			$text_c = imagecolorallocatealpha ($im, 192,192, 192, 96);
			if($this->dim_x<$this->dim_y)	$font_size=$this->dim_x/10;
			else				$font_size=$this->dim_y/10;
			$text_bbox=imageftbbox ( $font_size , 45 , $this->font_watermark , $CONFIG['site']['name'] );

			$min_x=$max_x=$text_bbox[0];
			$min_y=$max_y=$text_bbox[0];
			for($i=0;$i<8;$i+=2)
			{
				if($text_bbox[$i]<$min_x)	$min_x=$text_bbox[$i];
				if($text_bbox[$i]>$max_x)	$max_x=$text_bbox[$i];
				if($text_bbox[$i+1]<$min_y)	$min_y=$text_bbox[$i+1];
				if($text_bbox[$i+1]>$max_y)	$max_y=$text_bbox[$i+1];
			}
			$delta_x = $this->dim_x - $max_x + $min_x;
			$delta_y = $this->dim_y - $min_y + $max_y;

			imagefttext($im, $font_size, 45, $delta_x / 1.9, $delta_y / 2, $bg_c, $this->font_watermark, $CONFIG['site']['name']);
			imagefttext($im, $font_size, 45, $delta_x / 1.9 + 2, $delta_y / 2 + 2, $text_c, $this->font_watermark, $CONFIG['site']['name']);
		}
// 		header("Content-type: image/jpg");
// 		imagejpeg($im,"",$this->quality);
		
		imageinterlace($im ,1);	// Progressive JPEG
		if($this->type == 'jpg')	imagejpeg($im, $cname, $this->quality);
		else if($this->type == 'gif')	imagegif($im, $cname, $this->quality);
		else 				imagepng($im, $cname, 9);
		header("Location: $icname", true, 301);
		//exit();

	}
};


class ImageException extends AutoLoggedException {};



?>