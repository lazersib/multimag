#!/usr/bin/php
<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

$c = explode('/', __FILE__);
$base_path = '';
for ($i = 0; $i < (count($c) - 2); $i++)
	$base_path.=$c[$i] . '/';
require_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

$archiv_dir = $CONFIG['backup']['archiv_dir'];
$zip_level = $CONFIG['backup']['ziplevel'];
$minspace = $CONFIG['backup']['min_free_space'];


// Check free disk space
if ($minspace && $archiv_dir)
	if (disk_free_space($archiv_dir) <= ($minspace * 1024 * 1024)) {
		echo"Need cleaning!\n";

		if ($handle = opendir($archiv_dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file[0] == '.')
					continue;
				if (is_dir($archiv_dir . '/' . $file))
					dir_clean($archiv_dir . '/' . $file);
			}
			closedir($handle);
		}
		else
			echo">Dir $archiv_dir ERROR!\n";
	}

$yy = date("Y");
$tm = date("Y.m.d_H.i");
@mkdir("$archiv_dir/$yy/", 0700);
@mkdir("$archiv_dir/$yy/$tm", 0700);

if (@$CONFIG['backup']['mysql']) {
	@mkdir("/tmp/mysql_dump", 0700);
	$res = $db->query("SHOW DATABASES");
	while ($nxt = $res->fetch_row()) {
		if ($nxt[0] == 'mysql')	continue;
		if ($nxt[0] == 'information_schema')	continue;
		echo"Dumping $nxt[0]...";
		`mysqldump -u {$CONFIG['mysql']['login']} -p{$CONFIG['mysql']['pass']} -R -l --hex-blob -q -Q $nxt[0] > /tmp/mysql_dump/$nxt[0].dump`;
		echo"Done!\n";
		echo"Zipping mysql dump $nxt[0]...";
		if (@$CONFIG['backup']['archiver'] == '7z')
			`7zr a -ssc -mx=$zip_level $archiv_dir/$yy/$tm/mysql_$nxt[0].7z /tmp/mysql_dump/$nxt[0].dump`;
		else
			`zip $archiv_dir/$yy/$tm/mysql_$nxt[0].zip -r /tmp/mysql_dump/$nxt[0].dump -$zip_level -j`;
		echo"Done!\n";
	}
}

if (@is_array($CONFIG['backup']['dirs'])) {
    foreach ($CONFIG['backup']['dirs'] as $arch_name => $info) {
        $archiever = $CONFIG['backup']['archiver'];
        $compress_level = $CONFIG['backup']['ziplevel'];
        if(is_array($info)) {
            $path = $info['path'];
            if(isset($info['arch'])) {
                $archiever = $info['arch'];
            }
            if(isset($info['level'])) {
                $compress_level = $info['level'];
            }
        } else {
            $path = $info;
        }
        echo"Packing $path => $arch_name...";
        $fname = "$archiv_dir/$yy/$tm/$arch_name";
        switch ($archiever) {
            case '7z':
                `7zr a -ssc -mx=$compress_level $fname.7z $path`;
                break;
            case 'tar':
                `tar -cpS --ignore-failed-read --one-file-system --file=$fname.tar.gz $path`;
                break;
            case 'tgz':
                `tar -cpSz --ignore-failed-read --one-file-system --file=$fname.tar.gz $path`;
                break;
            case 'tbz':
                `tar -cpSj --ignore-failed-read --one-file-system --file=$fname.tar.bz2 $path`;
                break;
            default:
                `zip $fname.zip -r $path -$compress_level`;
        }
        echo"Done!\n";
    }
}

function dir_clean($dir_name) {
	$i = 0;
	if ($handle = opendir($dir_name)) {
		echo "Directory handle: $handle\n";
		echo "Files:\n";
		while (false !== ($file = readdir($handle))) {
			if ($file[0] == '.')
				continue;
			if ($i == 1) {
				$fn = $dir_name . '/' . $file;
				deleteDirectory($fn);
				echo "Delete $fn\n";
			}
			$i++;
                        if($i>3) {
                            $i = 0;
                        }
		}
		closedir($handle);
	}
	else
		echo">Dir $dir_name ERROR!\n";
}

function deleteDirectory($dir) {
	if (!file_exists($dir))
		return true;
	if (!is_dir($dir) || is_link($dir))
		return unlink($dir);
	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..')
			continue;
		if (!deleteDirectory($dir . "/" . $item)) {
			chmod($dir . "/" . $item, 0777);
			if (!deleteDirectory($dir . "/" . $item))
				return false;
		}
	}
	return rmdir($dir);
}

if (@$CONFIG['backup']['ftp_host']) {
	$conn_id = @ftp_connect($CONFIG['backup']['ftp_host']);
	$login_result = @ftp_login($conn_id, $CONFIG['backup']['ftp_login'], $CONFIG['backup']['ftp_pass']);

	if ((!$conn_id) || (!$login_result)) {
		echo "FTP connection has failed!\n";
		echo "Attempted to connect to {$CONFIG['backup']['ftp_host']}\n";
	} else {
		echo "Connected to {$CONFIG['backup']['ftp_host']}\n";
		if ($handle = opendir("$archiv_dir/$yy/$tm")) {
			@ftp_mkdir($conn_id, "/{$CONFIG['site']['name']}");
			@ftp_mkdir($conn_id, "/{$CONFIG['site']['name']}/$yy");
			$res = ftp_mkdir($conn_id, "/{$CONFIG['site']['name']}/$yy/$tm");
			if (!$res)
				echo"MkDir ERROR!\n";
			while (false !== ($file = readdir($handle))) {
				if ($file[0] == '.')
					continue;
				$sfn = "$archiv_dir/$yy/$tm/$file";
				$rfn = "/{$CONFIG['site']['name']}/$yy/$tm/$file";

				$upload = ftp_put($conn_id, $rfn, $sfn, FTP_BINARY);

				if (!$upload)
					echo "FTP upload has failed!\n";
				else
					echo "Uploaded $sfn to ftpserver\n";
			}
			closedir($handle);
		}
		else
			echo">Dir reading ERROR!\n";
	}
	@ftp_close($conn_id);
}
