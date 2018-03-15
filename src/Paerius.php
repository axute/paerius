<?php

namespace Paerius;

use FilesystemIterator;
use Phar;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class Paerius {

    public const DEFAULT_BASENAME = 'vendor.phar';

    /** @var string */
    protected $baseName;

    public static $toDeleteBasenames = ['LICENSE', '.styleci.yml', '.travis.yml', '.gitattributes', 'phpunit.xml.dist', 'README.rst', 'CHANGELOG', 'CHANGES', 'README', 'VERSION', '.php_cs.dist', '.php_cs', '.editorconfig', 'php_cs.xml', 'phpunit.xml', 'phpcs.xml', '.codecov.yml', 'AUTHORS', 'Makefile', '.gitignore'];

    public static $toDeleteDirectoryNames = ['.git', 'tests', 'Tests', 'test', 'doc', 'testing', 'test_old', '.svn', '.cvs', '.idea', '.DS_Store', '.hg'];

    public static $toDeleteExtensions = ['md', 'gitignore', 'markdown', 'hprof', 'pyc'];

    /** @var string */
    protected $vendorPath;

    /** @var string */
    protected $workingPath;

    /**
     * Aerius constructor.
     *
     * @param string $workingPath
     * @param null|string $baseName
     * @throws \RuntimeException
     */
    public function __construct(string $workingPath, ?string $baseName = self::DEFAULT_BASENAME) {
        $workingPath = rtrim($workingPath, DIRECTORY_SEPARATOR);
        $this->setWorkingPath($workingPath);
        $this->setVendorPath($workingPath . DIRECTORY_SEPARATOR . 'vendor');
        $this->setBaseName($baseName);
    }

    /**
     * @param string|null $forceCompression gz*|bz*|none, null = automatic detection bz < gz < none
     * @return bool
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     */
    public function build(?string $forceCompression): bool {
        $pharFilePath = $this->getWorkingPath() . DIRECTORY_SEPARATOR . $this->getBaseName();
        if (is_file($pharFilePath)) {
            unlink($pharFilePath);
        }
        $this->cleanupVendorDir();
        $phar = new Phar($pharFilePath, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, $this->baseName);
        $phar->setMetadata(
            [
                'datetime' => date('c'),
            ]
        );
        $phar->setSignatureAlgorithm(Phar::SHA1);
        $phar->buildFromDirectory($this->getVendorPath());
        $supportedCompression = Phar::getSupportedCompression();
        if (($forceCompression !== null && stripos($forceCompression, 'bz') === 0) || ($forceCompression === null && \in_array('BZIP2', $supportedCompression, true))) {
            $phar->compressFiles(Phar::BZ2);
        }
        else if (($forceCompression !== null && stripos($forceCompression, 'gz') === 0) || ($forceCompression === null && \in_array('GZ', $supportedCompression, true))) {
            $phar->compressFiles(Phar::GZ);
        }

        return $phar->setStub("<?php\n\\Phar::mapPhar();\nreturn require 'phar://{$this->baseName}/autoload.php';\n__HALT_COMPILER();");
    }

    /**
     * remove all files from directory
     *
     * @return $this
     */
    protected function cleanupVendorDir(): self {
        $file_del = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($this->vendorPath), [$this, 'filter'])
        );

        foreach ($file_del as $toDlete) {
            /** @var \SplFileInfo $toDlete */
            self::remove($toDlete->getPath() . DIRECTORY_SEPARATOR . $toDlete->getFilename());
        }

        return $this;
    }

    public function filter( SplFileInfo $current, $key, RecursiveIterator $iterator ): bool {
        // Allow recursion
        if ($iterator->hasChildren()) {
            return true;
        }
        $entry = $current->getFilename();
        if (\in_array($entry, ['.', '..'], true) === false) {
            $path = $current->getPath();
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            $dirnames = explode(DIRECTORY_SEPARATOR, trim(substr($path, \strlen($this->getVendorPath())), DIRECTORY_SEPARATOR));
            if (\count(array_intersect($dirnames, self::$toDeleteDirectoryNames)) > 0) {
                return true;
            }

            return \in_array($ext, self::$toDeleteExtensions, true) || \in_array($entry, self::$toDeleteBasenames, true);
        }

        return false;
    }

    public function getBaseName(): string {
        return $this->baseName;
    }

    public function getVendorPath(): string {
        return $this->vendorPath;
    }

    public function getWorkingPath(): string {
        return $this->workingPath;
    }

    protected static function remove(string $src): void {
        if (is_dir($src)) {
            $dir = opendir($src);
            while (false !== ($file = readdir($dir))) {
                if (($file !== '.') && ($file !== '..')) {
                    self::remove($src . '/' . $file);
                }
            }
            closedir($dir);
            rmdir($src);
        }
        else if (is_file($src)) {
            unlink($src);
        }
    }

    protected function setBaseName(string $baseName): self {
        $this->baseName = $baseName;

        return $this;
    }

    /**
     * @param string $vendorPath
     * @return Paerius
     * @throws \RuntimeException
     */
    protected function setVendorPath(string $vendorPath): self {
        if (!is_dir($vendorPath)) {

            throw new RuntimeException("$vendorPath is not a directory!");
        }
        $this->vendorPath = $vendorPath;

        return $this;
    }

    /**
     * @param string $workingPath
     * @return Paerius
     * @throws \RuntimeException
     */
    protected function setWorkingPath(string $workingPath): self {
        if (!is_dir($workingPath)) {
            throw new RuntimeException("{$workingPath} is not a directory");
        }
        $this->workingPath = $workingPath;

        return $this;
    }
}
