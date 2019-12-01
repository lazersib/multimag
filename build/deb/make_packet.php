#!/usr/bin/php
<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2019, BlackLight, TND Team, http://tndproject.org
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

// Скрипт сборки deb пакета multimag
class MultimagPacketBuilder {
    const PKG_NAME = 'multimag';

    private $cfg;
    private $runDir;
    private $revision;
    private $repoDir;
    private $repoType;
    private $repoUrl;
    private $repoBranch;

    private $outArchivePath;
    private $outArchiveTargetPath;
    private $outDocPath;
    private $outPkgDir;
    private $outPkgTargetDir;
    private $outDebRepositoryDir;
    private $outSignId;

    private $dataSize;

    function __construct($cfg) {
        $this->runDir = getcwd();
        $this->cfg = $cfg;
        $this->repoDir = $this->cfg['repository']['local_dir'] or die("Repository local_dir is not defined.\n");
        $this->repoType = $this->cfg['repository']['type'] or die("Repository type is not defined.\n");
        $this->repoUrl = $this->cfg['repository']['url'] or die("Repository url is not defined.\n");
        $this->repoBranch = $this->cfg['repository']['branch'] or die("Repository branch is not defined.\n");

        $this->outArchivePath = $this->cfg['output']['archive_store_path'] or die("Output archive_store_path is not defined.\n");
        $this->outArchiveTargetPath = $this->cfg['output']['archive_target_path'] or die("Output archive_target_path is not defined.\n");
        $this->outDocPath = $this->cfg['output']['doc_path'] or die("Output doc_path is not defined.\n");
        $this->outPkgDir = $this->cfg['output']['package_dir'] or die("Output package_dir is not defined.\n");
        $this->outPkgTargetDir = $this->cfg['output']['package_target_dir'] or die("Output package_target_dir is not defined.\n");
        $this->outDebRepositoryDir = $this->cfg['output']['deb_repository_dir'] or die("Output deb_repository_dir is not defined.\n");
        $this->outSignId = $this->cfg['output']['sign_id'] or die("Output sign_id is not defined.\n");

        if($this->repoDir[0]!='/')
            $this->repoDir = $this->runDir.'/'.$this->repoDir;
        if($this->outPkgDir[0]!='/')
            $this->outPkgDir = $this->runDir.'/'.$this->outPkgDir;
        if($this->outPkgTargetDir[0]!='/')
            $this->outPkgTargetDir = $this->runDir.'/'.$this->outPkgTargetDir;
    }

    private function getRevision() {
        return "0.2.x-".$this->revision;
    }

    private function preClean() {
        echo `rm -Rf {$this->repoDir}`;
        echo `rm -Rf pkg`;
    }

    private function getSource() {
        switch ($this->repoType) {
            case 'svn':
                echo `svn co {$this->repoUrl} {$this->repoDir}`;
                break;
            case 'git':
                echo `git clone {$this->repoUrl} {$this->repoDir}`;
                break;
            default:
                die("Repository type ({$this->repoType}) is wrong.");
        }
    }

    private function saveGitRevLog($log) {
        $xml = new XMLWriter();
        $xml->openUri("changelog.xml");
        $xml->startDocument('1.0','UTF-8');
        $xml->startElement('log');
        $logs = explode("\n", $log);
        $revnum = 1;
        foreach ($logs as $line) {
            $info = explode(":::", trim($line));
            $xml->startElement('logentry');
            $xml->writeAttribute('revision', $revnum);
            $xml->writeElement('author', $info[0]);
            $xml->writeElement('date', $info[1]);
            $xml->writeElement('msg', $info[2]);
            $xml->endElement();
            $revnum++;
        }
        $xml->endElement();
        $xml->endDocument();
    }

    private function loadMetaGit() {
        chdir($this->repoDir);
        $this->revision = trim(`git rev-list {$this->repoBranch} --count`);
        $log = `git log --pretty=format:"%an:::%ad:::%s" --date=short --reverse`;
        echo $this->saveGitRevLog($log);
        chdir($this->runDir);
    }

    private function loadMetaSvn() {
        $svn_info = `svn info {$this->repoDir}`;
        $svn_info = explode("\n", $svn_info);
        foreach ($svn_info as $i) {
            $i = explode(":", $i, 2);
            switch ($i[0]) {
                case 'Revision':
                    $this->revision = $i[1];
                    break;
            }
        }
        echo `svn log -r 1:head --xml {$this->repoDir} >> {$this->repoDir}/changelog.xml`;
    }

    private function loadMeta() {
        switch ($this->repoType) {
            case 'svn':
                $this->loadMetaSvn();
                break;
            case 'git':
                $this->loadMetaGit();
                break;
            default:
                die("Repository type ({$this->repoType}) is wrong.");
        }
        //settype($this->revision, 'int');
        echo "Current revision: {$this->revision}\n";
    }

    private function getDataSize($dir) {
        $size = 0;
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '..' || $file == '.') continue;
                if (is_dir($dir . '/' . $file)) {
                    $size += $this->getDataSize($dir . '/' . $file);
                } else $size += filesize($dir . '/' . $file);
            }
            closedir($dh);
        }
        return $size;
    }

    private function calculateDataSize() {
        $this->dataSize = $this->getDataSize($this->repoDir);
        echo "Data size: {$this->dataSize}\n";
    }

    private function deleteVCSDataIn($dir, $clean = 0) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '..' || $file == '.')
                    continue;
                if (is_dir($dir . '/' . $file)) {
                    if ($file === '.svn' || $file === '.git')
                        $this->deleteVCSDataIn($dir . '/' . $file, 1);
                    else
                        $this->deleteVCSDataIn($dir . '/' . $file, $clean);
                } else if ($clean)
                    unlink($dir . '/' . $file);
            }
            closedir($dh);
        }
        if ($clean)
            rmdir($dir);
    }

    private function deleteServiceData() {
        $this->deleteVCSDataIn($this->repoDir);
    }

    private function createArchive() {
        `tar --one-file-system --preserve-permissions -cf {$this->outArchivePath} {$this->repoDir}`;
        copy($this->outArchivePath, $this->outArchiveTargetPath);
    }

    private function replaceVars($text) {
        $search = array("{VERSION}", "{INPUT}", "{OUTPUT}", "{PACKAGE_NAME}", "{PACKAGE_SIZE}");
        $replace = array($this->getRevision(), $this->runDir.'/'.$this->repoDir, $this->outDocPath, self::PKG_NAME, round($this->dataSize / 1024));
        return str_replace($search, $replace, $text);
    }

    private function generateDoc() {
        $DOXYFILE = "Doxyfile";
        $doxy_template = file_get_contents("Doxyfile_template");
        $fd = fopen($DOXYFILE, "w");
        fwrite($fd, $this->replaceVars($doxy_template));
        fclose($fd);
        exec("doxygen {$DOXYFILE}");
    }

    private function makeDeb() {
        $APP_DIR = $this->outPkgDir.'/usr/share/'.self::PKG_NAME;
        $CFG_DIR = $this->outPkgDir.'/etc/'.self::PKG_NAME;
        $DOC_DIR = $this->outPkgDir.'/usr/share/doc';
        $PKG_DOC_DIR = $this->outPkgDir.'/usr/share/doc/'.self::PKG_NAME;
        $PKG_DEB_DIR = $this->outPkgDir.'/DEBIAN';
        $CRON_DIR = $this->outPkgDir.'/etc/cron.d';
        $PKG_TARGET_PATH = $this->outPkgTargetDir."/".self::PKG_NAME."_".$this->getRevision()."_all.deb";

        mkdir($this->outPkgDir.'/usr/share', 0755, true);
        rename($this->repoDir, $APP_DIR);

        mkdir($CFG_DIR, 0755, true);
        rename($APP_DIR.'/config_all.sample.php', $CFG_DIR.'/config_all.php');
        rename($APP_DIR.'/config_cli.sample.php', $CFG_DIR.'/config_cli.php');
        rename($APP_DIR.'/config_site.sample.php', $CFG_DIR.'/config_site.php');
        // Абсолютный путь для нужен для правильной ссылки в пакете
        exec("ln -s /etc/".self::PKG_NAME."/config_all.php $APP_DIR/config_all.php");
        exec("ln -s /etc/".self::PKG_NAME."/config_cli.php $APP_DIR/config_cli.php");
        exec("ln -s /etc/".self::PKG_NAME."/config_site.php $APP_DIR/config_site.php");

        mkdir($DOC_DIR, 0755, true);
        rename($APP_DIR.'/examples', $PKG_DOC_DIR);
        rename($APP_DIR.'/db_struct.sql', $PKG_DOC_DIR.'/db_struct.sql');
        rename($APP_DIR.'/license.txt', $PKG_DOC_DIR.'/license.txt');

        mkdir($PKG_DEB_DIR, 0777);

        $pkgfiles = ['control', 'copyright', 'conffiles', 'postinst'];
        foreach ($pkgfiles as $pkgfile) {
            $content = file_get_contents($APP_DIR.'/build/deb/'.$pkgfile);
            $content = $this->replaceVars($content);
            file_put_contents($PKG_DEB_DIR.'/'.$pkgfile, $content);
        }
        chmod($PKG_DEB_DIR.'/postinst', 0755);

        mkdir($CRON_DIR, 0755, true);
        $content = file_get_contents($APP_DIR.'/build/deb/crontab');
        $content = $this->replaceVars($content);
        file_put_contents($CRON_DIR.'/multimag', $content);

        $garbadge = ['art_source', 'tests', 'build'];
        foreach ($garbadge as $item) {
            exec("rm -r {$APP_DIR}/{$item}");
        }


        system("fakeroot dpkg-deb --build pkg {$PKG_TARGET_PATH}");

        if($this->cfg['lintian'] && $this->cfg['lintian']['enabled'])
            system("lintian {$PKG_TARGET_PATH}");

        chdir($this->outDebRepositoryDir);
        system("reprepro -C main remove testing ".self::PKG_NAME);
        system("reprepro --ask-passphrase -C main includedeb testing {$PKG_TARGET_PATH}");
        system("gpg -u {$this->outSignId} -a --detach-sign -o {$this->outDebRepositoryDir}/dists/testing/Release.gpg {$this->outDebRepositoryDir}/dists/testing/Release");

    }

    public function run() {
        $this->preClean();
        $this->getSource();
        $this->loadMeta();
        $this->deleteServiceData();
        $this->calculateDataSize();
        $this->createArchive();
        //$this->generateDoc();
        $this->makeDeb();
    }
}

$cfg = parse_ini_file('build.conf', true);
if ($cfg === false) {
    die('Config ./build.conf not loaded!');
}

$app = new MultimagPacketBuilder($cfg);
$app->run();

