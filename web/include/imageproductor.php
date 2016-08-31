<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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
    protected $storages = array('p' => 'pos', 'w' => 'wikiphoto', 'f' => 'galery', 'g' => 'category', 'n' => 'news', 'a' => 'article');
    protected $types = array('jpg', 'png', 'gif');
    protected $source_file = null;
    protected $source_exist = null;
    protected $cached = null;
    protected $cache_fclosure = '';
    protected $quality = 70;  //< Требуемое качество
    protected $dim_x = 0;  //< Требуемый размер по x
    protected $dim_y = 0;  //< Требуемый размер по y
    protected $fix_aspect = 1; //< Не изменять соотношение сторон (иначе - дополнять фоном)
    protected $no_enlarge = 0; //< Не увеличивать изображение
    protected $show_watermark = 1; //< Показывать наименование магазина поверх изображения
    protected $font_watermark = 'dejavu/DejaVuSansCondensed-Bold.ttf'; //< Путь к шрифту для отображения наименования

    public function __construct($img_id, $img_storage, $type = 'jpg') {
        global $CONFIG;
        if (!$img_id) {
            throw new ImageException('ID изображения не задан!');
        }
        $this->id = $img_id;
        if (!array_key_exists($img_storage, $this->storages)) {
            throw new ImageException($img_storage . 'Хранилище изображения не задано, либо не существует!');
        }
        $this->storage = $img_storage;
        if (!in_array($type, $this->types)) {
            throw new ImageException('Запрошенный тип изображений не поддерживается');
        }
        $this->type = $type;
        //$this->source_file="{$CONFIG['site']['var_data_fs']}/{$this->storages[$this->storage]}/{$this->id}.{$this->type}";
        //$this->source_exist=file_exists($this->source_file);
        if (@$CONFIG['images']['quality']) {
            $this->quality = $CONFIG['images']['quality'];
        }
    }

    public function SetX($x) {
        $this->dim_x = $x;
    }

    ///
    public function SetY($y) {
        $this->dim_y = $y;
    }

    /// Задать качество изображения. Определяет уровень JPEG сжатия.
    public function SetQuality($quality) {
        if ($quality > 0) {
            $this->quality = $quality;
        }
    }

    public function SetNoEnlarge($flag) {
        $this->no_enlarge = $flag;
    }

    /// Установка разрешения изменения пропорций изображения. Если изменение пропорций запрещено - будут добавлены поля.
    public function SetFixAspect($flag) {
        $this->fix_aspect = $flag;
    }

    /// Возвращает URI изображения. Если изображение есть в кеше - возвращает его. Иначе - возвращает адрес скрипта конвертирования
    public function GetURI($no_encode = false) {
        global $CONFIG;
        if ($this->cached == null) {
            $this->CacheProbe();
        }
        if ($this->cached) {
            return "{$CONFIG['site']['var_data_web']}/{$this->cache_fclosure}";
        } else {
            if ($no_encode) {
                return "/images.php?i={$this->id}&s={$this->storage}&x={$this->dim_x}&y={$this->dim_y}&q={$this->quality}&t={$this->type}&f={$this->fix_aspect}&n={$this->no_enlarge}";
            } else {
                return "/images.php?i={$this->id}&amp;s={$this->storage}&amp;x={$this->dim_x}&amp;y={$this->dim_y}&amp;q={$this->quality}&amp;t={$this->type}&amp;f={$this->fix_aspect}&amp;n={$this->no_enlarge}";
            }
        }
    }

    /// Обёртка над getimagesize для исходника данного изображения
    public function getRealImageSize() {
        global $CONFIG;
        $fn = "{$CONFIG['site']['var_data_fs']}/{$this->storages[$this->storage]}/{$this->id}.{$this->type}";
        return @getimagesize($fn);
    }

    /// Проверка, существует ли запрошенное хранилище
    /// @return true, если существует, false в ином случае
    //public function isStorageExists
    /// Есть ли изображение в кеше
    protected function CacheProbe() {
        global $CONFIG;
        $this->cache_fclosure = "cache/{$this->storages[$this->storage]}/{$this->id}-{$this->dim_x}-{$this->dim_y}-{$this->quality}.{$this->type}";
        return $this->cached = file_exists($CONFIG['site']['var_data_fs'] . '/' . $this->cache_fclosure);
    }
    
    /// Нанести водяные знаки
    protected function drawWatermark(&$im) {
        global $CONFIG;
        if (@isset($CONFIG['images']['watermark'])) {
            if (is_array($CONFIG['images']['watermark'])) {
                if (isset($CONFIG['images']['watermark'][$this->storage])) {
                    $this->show_watermark = $CONFIG['images']['watermark'][$this->storage];
                } else {
                    $this->show_watermark = 0;
                }
            } else {
                $this->show_watermark = $CONFIG['images']['watermark'];
            }
        } else {
            $this->show_watermark = 1;
        }
        if (@$CONFIG['images']['font_watermark']) {
            $this->font_watermark = $CONFIG['images']['font_watermark'];
        }
        
        if(!$this->show_watermark) {
            return;
        }
        $pref = \pref::getInstance();
        $text = strtoupper($pref->site_name);
        $bg_c = imagecolorallocatealpha($im, 64, 64, 64, 96);
        $text_c = imagecolorallocatealpha($im, 192, 192, 192, 96);
        if ($this->dim_x < $this->dim_y) {
            $font_size = $this->dim_x / 10;
        } else {
            $font_size = $this->dim_y / 10;
        }

        $text_bbox = imageftbbox($font_size, 45, $this->font_watermark, $text);
        $w = $text_bbox[2] - $text_bbox[6];
        $h = $text_bbox[1] - $text_bbox[5];

        if ($this->dim_x < $this->dim_y) {
            $delta = $w / $this->dim_x / 0.9;
        } else {
            $delta = $h / $this->dim_y / 0.9;
        }

        $font_size = round($font_size / $delta);

        $text_bbox = imageftbbox($font_size, 45, $this->font_watermark, $text);

        $width = $text_bbox[2] - $text_bbox[6];
        $height = $text_bbox[1] - $text_bbox[5];
        $offset_x = $text_bbox[0] - $text_bbox[6];

        $bb_x = round(($this->dim_x - $width) / 2);
        $bb_y = round(($this->dim_y - $height) / 2);

        $text_x = $bb_x + $offset_x;
        $text_y = $bb_y + $height;

        imagefttext($im, $font_size, 45, $text_x, $text_y, $bg_c, $this->font_watermark, $text);
        imagefttext($im, $font_size, 45, $text_x + 2, $text_y + 2, $text_c, $this->font_watermark, $text);
    }

    /// Сделать изображение и сохранить в кеш
    public function MakeAndStore() {
        global $CONFIG;       

        $rs = 0;
        $this->cache_fclosure = "cache/{$this->storages[$this->storage]}/{$this->id}-{$this->dim_x}-{$this->dim_y}-{$this->quality}.{$this->type}";
        $cname = "{$CONFIG['site']['var_data_fs']}/{$this->cache_fclosure}";
        $icname = "{$CONFIG['site']['var_data_web']}/{$this->cache_fclosure}";

        $this->source_file = "{$CONFIG['site']['var_data_fs']}/{$this->storages[$this->storage]}/{$this->id}.{$this->type}";
        if (!file_exists($this->source_file)) {
            throw new NotFoundException('Изображение не найдено');
        }
        @mkdir("{$CONFIG['site']['var_data_fs']}/cache/{$this->storages[$this->storage]}/", 0755);

        $sz = getimagesize($this->source_file);

        $sx = $sz[0];
        $sy = $sz[1];


        if ($this->dim_x || $this->dim_y) {
            // Жёстко заданные размеры
            $aspect = $sx / $sy;
            if ($this->dim_y && (!$this->dim_x)) {
                if ($this->dim_y > $sy) {
                    $this->dim_y = $sy;
                }
                $this->dim_x = round($aspect * $this->dim_y);
            }
            else if ($this->dim_x && (!$this->dim_y)) {
                if ($this->dim_x > $sx) {
                    $this->dim_x = $sx;
                }
                $this->dim_y = round($this->dim_x / $aspect);
            }
            $naspect = $this->dim_x / $this->dim_y;
            $nx = $this->dim_x;
            $ny = $this->dim_y;
            if ($aspect < $naspect) {
                $nx = round($aspect * $this->dim_y);
            } else {
                $ny = round($this->dim_x / $aspect);
            }
            $lx = ($this->dim_x - $nx) / 2;
            $ly = ($this->dim_y - $ny) / 2;

            $rs = 1;
        }
        else {
            $this->dim_x = $sz[0];
            $this->dim_y = $sz[1];
        }

        if (($this->dim_x > $sx || $this->dim_y > $sy) && $this->no_enlarge) {
            $rs = 0;
        }

        if ($this->type == 'jpg') {
            if (function_exists('imagecreatefromjpeg')) {
                $im = imagecreatefromjpeg($this->source_file);
            } else {
                throw new \ImageException($this->type . ' не поддерживается вашей версией PHP!');
            }
        }
        else if ($this->type == 'png') {
            if (function_exists('imagecreatefrompng')) {
                $im = imagecreatefrompng($this->source_file);
            } else {
                throw new \ImageException($this->type . ' не поддерживается вашей версией PHP!');
            }
        }
        else if ($this->type == 'gif') {
            if (function_exists('imagecreatefromgif')) {
                $im = imagecreatefromgif($this->source_file);
            } else {
                throw new \ImageException($this->type . ' не поддерживается вашей версией PHP!');
            }
        } else {
            throw new \ImageException($this->type . ' не поддерживается обработчиком!');
        }

        if ($rs) {
            $im2 = imagecreatetruecolor($this->dim_x, $this->dim_y);
            imagefill($im2, 0, 0, imagecolorallocate($im2, 255, 255, 255));
            imagecopyresampled($im2, $im, $lx, $ly, 0, 0, $nx, $ny, $sx, $sy);
            imagedestroy($im);
            $im = $im2;
        }
        // Оптимизировать большие изображения
        if ($this->dim_x >= 300 || $this->dim_y >= 300) {
            imageinterlace($im, 1);
        }
        $this->drawWaterMark($im);

        imageinterlace($im, 1); // Progressive JPEG
        if ($this->type == 'jpg') {
            imagejpeg($im, $cname, $this->quality);
        } else if ($this->type == 'gif') {
            imagegif($im, $cname, $this->quality);
        } else {
            imagepng($im, $cname, 9);
        }
        header("Location: $icname", true, 301);
        //exit();
    }

}


class ImageException extends AutoLoggedException {
    
}
