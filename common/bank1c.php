<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

class Bank1CPasrser
{
	var $raw_data;		// Массив строк выписки
	var $parsed_data;	// Обработанные данные
		
	function __construct($data)
	{
		$this->raw_data=$data;
		$parsed_data=array();
	}

	function Parse()
	{
		$params=array();
		$parsing=0;
		foreach($this->raw_data as $line)
		{
			$line=iconv( 'windows-1251','UTF-8', $line);	
			$line=trim($line);
			$pl = explode("=", $line, 2);
			if($pl[0]=='СекцияДокумент')
			{
				$params=array();
				if($pl[1]=="Платёжное поручение")
				{
					$parsing=1;
					$params['type']='pp';
					//echo"Новый $pl[1]\n";
				}
				else
				{
					//echo"Неопознанный документ: $pl[1]\n";
					$parsing=0;
				}
			}
			else if($pl[0]=='КонецДокумента')
			{
				if($parsing)	$this->parsed_data[]=$params;
				$parsing=0;
				$params=array();
			}
			else if($parsing) switch($pl[0])
			{
				case 'Номер':
					$params['docnum']=$pl[1];
				break;
				case 'УникальныйНомерДокумента':
					$params['unique']=$pl[1];
				break;
				case 'ДатаПроведения':
					$params['date']=$pl[1];
				break;	
				case 'БИК':
					$params['bik']=$pl[1];
				break;	
				case 'Счет':
					$params['schet']=$pl[1];
				break;
				case 'КорреспондентБИК':
					$params['kbik']=$pl[1];
				break;
				case 'КорреспондентСчет':
					$params['kschet']=$pl[1];
				break;	
				case 'ДебетСумма':
					$params['debet']=$pl[1];
				break;
				case 'КредитСумма':
					$params['kredit']=$pl[1];
				break;
				case 'НазначениеПлатежа':
					$params['desc']=$pl[1];
				break;
			}
		}
	}
};



?>